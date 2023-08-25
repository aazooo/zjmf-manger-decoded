<?php

namespace app\home\controller;

define("VIEW_TEMPLATE_DIRECTORY", "clientarea");
define("VIEW_TEMPLATE_WEBSITE", true);
define("VIEW_TEMPLATE_RETURN_ARRAY", true);
define("VIEW_TEMPLATE_SUFFIX", "tpl");
class ViewClientsController extends ViewBaseController
{
	public function page(\think\Request $request)
	{
		return $this->view($request->tpl);
	}
	public function login(\think\Request $request)
	{
		$param = $request->param();
		$action = isset($param["action"]) ? $param["action"] : "";
		$Login = controller("Login");
		if ($request->isPost()) {
			if ($action == "phone_code") {
				$result = $Login->mobileLoginVerify();
			} elseif ($action == "phone") {
				$result = $Login->phonePassLogin();
			} else {
				$result = $Login->emailLogin();
			}
			if ($result["status"] == 200) {
				userSetCookie($result["jwt"]);
				\think\facade\Cookie::set("login_error_log", null);
				header("location:/clientarea");
				exit;
			} else {
				$error_num = \think\facade\Cookie::has("login_error_log") ? \think\facade\Cookie::get("login_error_log") : 0;
				\think\facade\Cookie::set("login_error_log", ++$error_num, 900);
				$data["ErrorMsg"] = $result["msg"];
			}
		}
		$mobileLoginVerifyPage = $Login->mobileLoginVerifyPage();
		$LoginRegisterIndex = $Login->LoginRegisterIndex();
		$dataLogin["allow_login_register_sms_global"] = $LoginRegisterIndex["data"]["allow_login_register_sms_global"];
		$dataLogin["allow_login_email"] = $LoginRegisterIndex["data"]["allow_login_email"];
		$dataLogin["allow_login_phone"] = $LoginRegisterIndex["data"]["allow_login_phone"];
		$dataLogin["allow_id"] = $LoginRegisterIndex["data"]["allow_id"];
		$dataLogin["is_captcha"] = intval(configuration("is_captcha"));
		if (configuration("login_error_switch")) {
			$login_error_max_num = configuration("login_error_max_num");
			if ($login_error_max_num) {
				$dataLogin["is_captcha"] = $login_error_max_num < intval(\think\facade\Cookie::get("login_error_log")) ? 1 : $dataLogin["is_captcha"];
			}
		}
		if (!empty($dataLogin["is_captcha"])) {
			$dataLogin["allow_login_phone_captcha"] = intval(configuration("allow_login_phone_captcha"));
			$dataLogin["allow_login_email_captcha"] = intval(configuration("allow_login_email_captcha"));
			$dataLogin["allow_login_id_captcha"] = intval(configuration("allow_login_id_captcha"));
			$dataLogin["allow_login_code_captcha"] = intval(configuration("allow_login_code_captcha"));
			if (configuration("login_error_switch") && $login_error_max_num) {
				$dataLogin["allow_login_phone_captcha"] = $login_error_max_num < intval(\think\facade\Cookie::get("login_error_log")) ? 1 : $dataLogin["allow_login_phone_captcha"];
				$dataLogin["allow_login_email_captcha"] = $login_error_max_num < intval(\think\facade\Cookie::get("login_error_log")) ? 1 : $dataLogin["allow_login_email_captcha"];
				$dataLogin["allow_login_id_captcha"] = $login_error_max_num < intval(\think\facade\Cookie::get("login_error_log")) ? 1 : $dataLogin["allow_login_id_captcha"];
				$dataLogin["allow_login_code_captcha"] = $login_error_max_num < intval(\think\facade\Cookie::get("login_error_log")) ? 1 : $dataLogin["allow_login_code_captcha"];
			}
		}
		$dataLogin["second_verify_action_home_login"] = 0;
		if ($LoginRegisterIndex["data"]["allow_second_verify"] == 1) {
			if (in_array("login", $LoginRegisterIndex["data"]["second_verify_action_home"])) {
				$dataLogin["second_verify_action_home_login"] = 1;
			}
		}
		$data["SmsCountry"] = $mobileLoginVerifyPage["data"];
		if ($this->zjmf_authorize()) {
			$Oauth = controller("Oauth");
			$OauthListing = $Oauth->listing();
			$data["Oauth"] = $OauthListing["data"];
		}
		$data["Login"] = $dataLogin;
		$data["Title"] = get_title_lang("title_login");
		$data = array_merge($data, $this->data);
		return $this->view("login", $data);
	}
	public function loginAccessToken(\think\Request $request)
	{
		$param = $request->param();
		$redirect_url = $param["redirect_url"];
		if (empty($redirect_url)) {
			$data["ErrorMsg"] = "redirect_url 未配置！";
		}
		$action = isset($param["action"]) ? $param["action"] : "";
		$Login = controller("Login");
		if ($request->isPost()) {
			if ($action == "phone_code") {
				$result = $Login->mobileLoginVerify();
			} elseif ($action == "phone") {
				$result = $Login->phonePassLogin();
			} else {
				$result = $Login->emailLogin();
			}
			if ($result["status"] == 200) {
				userSetCookie($result["jwt"]);
				\think\facade\Cookie::set("login_error_log", null);
				$token = md5($result["jwt"]);
				$res_check = strpos($redirect_url, "?");
				if ($res_check === false) {
					$redirect_url .= "?access_token=" . $token;
				} else {
					$redirect_url .= "&access_token=" . $token;
				}
				\think\facade\Cache::set("access_token", $token, 3600);
				header("location: {$redirect_url}");
				exit;
			} else {
				$error_num = \think\facade\Cookie::has("login_error_log") ? \think\facade\Cookie::get("login_error_log") : 0;
				\think\facade\Cookie::set("login_error_log", ++$error_num, 900);
				$data["ErrorMsg"] = $result["msg"];
			}
		}
		$mobileLoginVerifyPage = $Login->mobileLoginVerifyPage();
		$LoginRegisterIndex = $Login->LoginRegisterIndex();
		$dataLogin["allow_login_register_sms_global"] = $LoginRegisterIndex["data"]["allow_login_register_sms_global"];
		$dataLogin["allow_login_email"] = $LoginRegisterIndex["data"]["allow_login_email"];
		$dataLogin["allow_login_phone"] = $LoginRegisterIndex["data"]["allow_login_phone"];
		$dataLogin["allow_id"] = $LoginRegisterIndex["data"]["allow_id"];
		$dataLogin["is_captcha"] = intval(configuration("is_captcha"));
		if (configuration("login_error_switch")) {
			$login_error_max_num = configuration("login_error_max_num");
			if ($login_error_max_num) {
				$dataLogin["is_captcha"] = $login_error_max_num < intval(\think\facade\Cookie::get("login_error_log")) ? 1 : $dataLogin["is_captcha"];
			}
		}
		if (!empty($dataLogin["is_captcha"])) {
			$dataLogin["allow_login_phone_captcha"] = intval(configuration("allow_login_phone_captcha"));
			$dataLogin["allow_login_email_captcha"] = intval(configuration("allow_login_email_captcha"));
			$dataLogin["allow_login_id_captcha"] = intval(configuration("allow_login_id_captcha"));
			$dataLogin["allow_login_code_captcha"] = intval(configuration("allow_login_code_captcha"));
			if (configuration("login_error_switch") && $login_error_max_num) {
				$dataLogin["allow_login_phone_captcha"] = $login_error_max_num < intval(\think\facade\Cookie::get("login_error_log")) ? 1 : $dataLogin["allow_login_phone_captcha"];
				$dataLogin["allow_login_email_captcha"] = $login_error_max_num < intval(\think\facade\Cookie::get("login_error_log")) ? 1 : $dataLogin["allow_login_email_captcha"];
				$dataLogin["allow_login_id_captcha"] = $login_error_max_num < intval(\think\facade\Cookie::get("login_error_log")) ? 1 : $dataLogin["allow_login_id_captcha"];
				$dataLogin["allow_login_code_captcha"] = $login_error_max_num < intval(\think\facade\Cookie::get("login_error_log")) ? 1 : $dataLogin["allow_login_code_captcha"];
			}
		}
		$dataLogin["second_verify_action_home_login"] = 0;
		if ($LoginRegisterIndex["data"]["allow_second_verify"] == 1) {
			if (in_array("login", $LoginRegisterIndex["data"]["second_verify_action_home"])) {
				$dataLogin["second_verify_action_home_login"] = 1;
			}
		}
		$data["SmsCountry"] = $mobileLoginVerifyPage["data"];
		$data["Login"] = $dataLogin;
		$data["Title"] = get_title_lang("title_login");
		$data["redirect_url"] = $redirect_url;
		return $this->view("loginaccesstoken", $data);
	}
	public function register(\think\Request $request)
	{
		$param = $request->param();
		$action = isset($param["action"]) ? $param["action"] : "";
		$data = [];
		$Login = controller("Login");
		$Register = controller("Register");
		if ($request->isPost()) {
			if ($action == "phone") {
				$result = $Register->registerPhone();
			} else {
				$result = $Register->registerEmail();
			}
			if ($result["status"] == 200) {
				userSetCookie($result["jwt"]);
				header("location:/clientarea");
				exit;
			} else {
				$data["ErrorMsg"] = $result["msg"];
			}
		}
		$data = $this->setCurousParam($param, $data);
		$mobileLoginVerifyPage = $Login->mobileLoginVerifyPage();
		$LoginRegisterIndex = $Login->LoginRegisterIndex();
		$data["saler"] = $LoginRegisterIndex["data"]["saler"];
		$data["setsaler"] = $LoginRegisterIndex["data"]["setsaler"];
		$dataRegister["allow_login_register_sms_global"] = $LoginRegisterIndex["data"]["allow_login_register_sms_global"];
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
		$data["Title"] = get_title_lang("title_register");
		return $this->view("register", $data);
	}
	protected function setCurousParam($param, $data)
	{
		if ($param["sale"]) {
			cookie("sale_param", $param["sale"]);
			$data["_sale"] = $param["sale"];
		}
		if ($param["fields"]) {
			$data["_fields"] = $param["fields"];
		}
		return $data;
	}
	public function pwreset(\think\Request $request)
	{
		$param = $request->param();
		$action = isset($param["action"]) ? $param["action"] : "";
		$Login = controller("Login");
		$Register = controller("Register");
		if ($request->isPost()) {
			if ($action == "phone") {
				$result = $Register->passPhoneReset();
			} else {
				$result = $Register->passEmailReset();
			}
			if ($result["status"] == 200) {
				$data["SuccessMsg"] = $result["msg"];
			} else {
				$data["ErrorMsg"] = $result["msg"];
			}
		}
		$mobileLoginVerifyPage = $Login->mobileLoginVerifyPage();
		$LoginRegisterIndex = $Login->LoginRegisterIndex();
		$dataLogin["allow_login_register_sms_global"] = $LoginRegisterIndex["data"]["allow_login_register_sms_global"];
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
		$data["SmsCountry"] = $mobileLoginVerifyPage["data"];
		$data["Pwreset"] = $dataLogin;
		$data["Title"] = get_title_lang("title_pwreset");
		return $this->view("pwreset", $data);
	}
	public function bind(\think\Request $request)
	{
		if (!$this->zjmf_authorize()) {
			header("location:/clientarea");
			exit;
		}
		$Oauth = controller("Oauth");
		if ($request->isPost()) {
			$param = $request->param();
			$action = isset($param["action"]) ? $param["action"] : "";
			if ($action == "phone") {
				$result = $Oauth->bindLoginPhone($request);
			} elseif ($action == "email") {
				$result = $Oauth->bindLoginEmail($request);
			}
			if ($result["status"] == 200) {
				userSetCookie($result["jwt"]);
				header("location:/clientarea");
				exit;
			} else {
				$data["ErrorMsg"] = $result["msg"];
			}
		}
		$Login = controller("Login");
		$mobileLoginVerifyPage = $Login->mobileLoginVerifyPage();
		$LoginRegisterIndex = $Login->LoginRegisterIndex();
		$data["SmsCountry"] = $mobileLoginVerifyPage["data"];
		$data["Bind"]["allow_login_register_sms_global"] = $LoginRegisterIndex["data"]["allow_login_register_sms_global"];
		$CallbackInfo = $Oauth->callbackInfo();
		$data["CallbackInfo"] = $CallbackInfo["data"]["callbackBind"];
		$data["Title"] = get_title_lang("title_bind");
		return $this->view("bind", $data);
	}
	public function logout(\think\Request $request)
	{
		userUnsetCookie();
		header("location:{$this->ViewModel->domain}/login");
		exit;
	}
	public function clientarea(\think\Request $request)
	{
		$param = $request->param();
		$page = $param["page"] >= 1 ? intval($param["page"]) : 1;
		$limit = $param["limit"] >= 1 ? intval($param["limit"]) : 5;
		$orderby = strval($param["orderby"]) ? strval($param["orderby"]) : "domainstatus";
		$sort = $param["sort"] ?? "DESC";
		if (!in_array($orderby, ["productname", "domainstatus", "nextduedate"])) {
			$orderby = "domainstatus";
		}
		$request->page = $page;
		$request->limit = $limit;
		$request->orderby = $orderby;
		$request->sort = $sort;
		$param_msg = $this->pushParam($request);
		$Index = controller("Index");
		$index = $Index->index($request);
		$Host = controller("Host");
		$request->type = "index";
		$getList = $Host->getList($request);
		$dataClientarea["index"] = $index["data"];
		$dataClientarea["hostlist"] = $getList["data"]["list"];
		$dataClientarea["Total"] = $getList["data"]["sum"];
		$dataClientarea["Pages"] = $this->ajaxPages($getList["data"]["list"], $limit, $page, $getList["data"]["sum"]);
		$dataClientarea["Limit"] = $limit;
		$dataClientarea["aff_msg"] = $param_msg["aff"];
		$dataClientarea["product_num_day_7"] = $param_msg["createduedate7"];
		$dataClientarea["product_num_day_30"] = $param_msg["createduedate30"];
		$dataClientarea["product_money_day_7"] = $param_msg["accounts7"];
		$dataClientarea["product_money_day_30"] = $param_msg["accounts30"];
		$dataClientarea["nextduedate3"] = $param_msg["nextduedate3"];
		$dataClientarea["nextduedate7"] = $param_msg["nextduedate7"];
		$dataClientarea["accounts"] = $param_msg["accounts"];
		$dataClientarea["user_certtfi"] = $param_msg["user_certtfi"];
		$data["ClientArea"] = $dataClientarea;
		$data["Title"] = get_title_lang("title_clientarea");
		if ($param["action"] == "list") {
			echo $this->view("includes/clientarea-list", $data, ["autoinclude" => false]);
			exit;
		}
		return $this->view("clientarea", $data);
	}
	public function details(\think\Request $request)
	{
		$User = controller("User");
		if ($request->isPost()) {
			$result = $User->update($request);
			if ($result["status"] == 200) {
				$data["SuccessMsg"] = $result["msg"];
			} else {
				$data["ErrorMsg"] = $result["msg"];
			}
		}
		$getAreas = $User->getAreas();
		$dataDetails["areas"]["country"] = $getAreas["data"]["country"];
		$data["Details"] = $dataDetails;
		$data["Title"] = get_title_lang("title_details");
		return $this->view("details", $data);
	}
	public function verified(\think\Request $request)
	{
		if (configuration("certifi_open") == 2) {
			header("location:{$this->ViewModel->domain}/clientarea");
			exit;
		}
		$param = $request->param();
		$action = $param["action"] ?? "";
		$step = $param["step"] ?? "";
		$plugin = $param["plugin"] ?? "";
		$uid = intval($param["uid"]);
		$Certification = controller("Certification");
		$certifi = $Certification->certifi();
		$data["Verified"] = $certifi["data"];
		if (empty($plugin)) {
			$plugin = $data["Verified"]["default"];
		}
		if (empty($action)) {
			if ($data["Verified"]["certifi_message"]["type"] == "certifi_person") {
				$action = "personal";
			} else {
				$action = "enterprises";
			}
		}
		$data["Verified"]["step"] = $step;
		$data["Verified"]["plugin"] = $plugin;
		$data["Verified"]["action"] = $action;
		$data["Title"] = get_title_lang("title_verified");
		$user_info = \think\Db::name("clients")->where("id", $uid)->find();
		$is_phone = isset($param["phone"]);
		if (!$user_info["phonenumber"] && configuration("certifi_isbindphone") == 1 && $is_phone) {
			$data["ErrorMsg"] = "需要先绑定手机号！";
			$error = 1;
		}
		if (configuration("certifi_isbindphone") == 1 && $user_info["phonenumber"]) {
			$data["Verified"]["phonenumber"] = $user_info["phonenumber"];
			$data["Verified"]["disabled"] = "readonly";
		}
		if ($request->isPost() && empty($error)) {
			if ($action == "personal") {
				if ($step == "info") {
					$uploadImage = $this->verifiedUploadImage();
					if ($uploadImage["status"] !== 200) {
						$data["ErrorMsg"] = $uploadImage["msg"];
					} else {
						if ($uploadImage["idimage"]) {
							$request->idimage = $uploadImage["idimage"];
						}
						$result = $Certification->personCertifiPost($request);
						if ($result["status"] == 200) {
							$certifi_type = $param["certifi_type"];
							header("location:{$this->ViewModel->domain}/verified?action={$action}&step=authstart&plugin={$certifi_type}");
							exit;
						} else {
							$data["ErrorMsg"] = $result["msg"];
						}
					}
				}
			} elseif ($action == "enterprises") {
				if ($step == "info") {
					$uploadImage = $this->verifiedUploadImage();
					if ($uploadImage["status"] !== 200) {
						$data["ErrorMsg"] = $uploadImage["msg"];
					} else {
						if ($uploadImage["idimage"]) {
							$request->idimage = $uploadImage["idimage"];
						}
						$result = $Certification->companyCertifiPost($request);
						if ($result["status"] == 200) {
							$certifi_type = $param["certifi_type"];
							header("location:{$this->ViewModel->domain}/verified?action={$action}&step=authstart&plugin={$certifi_type}");
							exit;
						} else {
							$data["ErrorMsg"] = $result["msg"];
						}
					}
				}
			}
		}
		if ($plugin != "artificial") {
			$_config = \think\Db::name("plugin")->where("module", "certification")->where("status", 1)->where("name", $plugin)->value("config");
			$_config = json_decode($_config, true);
			if ($action == "personal") {
				$table = "certifi_person";
			} elseif ($action == "enterprises") {
				$table = "certifi_company";
			}
			$free = intval($_config["free"]);
			$count = \think\Db::name("certifi_log")->where("uid", $uid)->where("status", "<>", 4)->where("card_type", 1)->where("certifi_type", $plugin)->count();
			$pay = false;
			if ($free == 0 || $free > 0 && $free <= $count) {
				$pay = true;
			}
			$flag = true;
			$data["Verified"]["freetimes"] = $free;
			$data["Verified"]["freetimes_use"] = $count <= $free ? $free - $count : 0;
			$data["Verified"]["paid"] = false;
			if (floatval($_config["amount"]) > 0 && $pay) {
				$invoice = \think\Db::name("invoice_items")->alias("a")->field("b.status,b.id,b.url,b.subtotal")->leftJoin("invoices b", "a.invoice_id = b.id")->where("a.uid", $uid)->where("a.rel_id", $uid)->where("a.type", $table)->order("a.id", "desc")->find();
				if ($invoice["status"] == "Unpaid") {
					$invoiceid = $invoice["id"];
					$data["Verified"]["invoiceid"] = $invoiceid;
					$data["Verified"]["total"] = $invoice["subtotal"];
					$flag = false;
				} elseif ($invoice["status"] == "Paid") {
					$data["Verified"]["paid"] = true;
				} else {
					$amount = floatval($_config["amount"]);
					$client = \think\Db::name("clients")->where("id", $uid)->find();
					$invoices_data = ["uid" => $uid, "create_time" => time(), "due_time" => time(), "paid_time" => 0, "last_capture_attempt" => 0, "subtotal" => $amount, "credit" => 0, "tax" => 0, "tax2" => 0, "total" => $amount, "taxrate" => 0, "taxrate2" => 0, "status" => "Unpaid", "payment" => $client["defaultgateway"] ?: "", "notes" => "", "type" => "certifi_person", "url" => "verified?action=personal&step=authstart&type={$plugin}"];
					$invoices_items = ["uid" => $uid, "type" => "certifi_person", "description" => "个人认证", "description2" => "个人认证", "amount" => $amount, "due_time" => time(), "payment" => $client["defaultgateway"] ?: "", "rel_id" => $uid];
					\think\Db::startTrans();
					try {
						$invoiceid = \think\Db::name("invoices")->insertGetId($invoices_data);
						$invoices_items["invoice_id"] = $invoiceid;
						\think\Db::name("invoice_items")->insert($invoices_items);
						\think\Db::commit();
					} catch (\Exception $e) {
						\think\Db::rollback();
					}
					$data["Verified"]["invoiceid"] = $invoiceid;
					$data["Verified"]["total"] = $amount;
					$flag = false;
				}
			}
			if ($request->isGet()) {
				$tmp = \think\Db::name($table)->where("auth_user_id", $uid)->find();
				if ($step == "authstart") {
					if (empty($tmp)) {
						header("location:{$this->ViewModel->domain}/verified?action={$action}&step=info");
						exit;
					}
				}
				if ($tmp["status"] == 4 && $flag) {
					$postdata = ["name" => $tmp["auth_real_name"] ?: "", "card" => $tmp["auth_card_number"] ?: "", "phone" => $tmp["auth_card_number"] ?: "", "bank" => $tmp["bank"] ?: "", "company_name" => $tmp["company_name"] ?: "", "company_organ_code" => $tmp["company_organ_code"] ?: ""];
					$customfields = getCertificationCustomFields($plugin, "certification");
					$customfield_plugin = [];
					if (!empty($customfields[0])) {
						foreach ($customfields as $key => $customfield) {
							if ($customfield["type"] == "file") {
								if (!empty($tmp["custom_fields" . ($key + 1)])) {
									$customfield_plugin[$customfield["field"]] = config("certificate") . $tmp["custom_fields" . ($key + 1)];
								} else {
									$customfield_plugin[$customfield["field"]] = "";
								}
							} else {
								$customfield_plugin[$customfield["field"]] = $tmp["custom_fields" . ($key + 1)] ?: "";
							}
						}
					}
					$postdata = array_merge($postdata, $customfield_plugin);
					if ($action == "personal") {
						$method = "personal";
					} elseif ($action == "enterprises") {
						$method = "company";
					}
					$res_data = zjmfhook($plugin, "certification", $postdata, $method);
					if ($res_data["status"] == 400) {
						$data["ErrorMsg"] = $res_data["msg"];
					} else {
						$data["Url"] = true;
						$data["CertifiPlugin"] = $plugin;
						if (is_array($res_data)) {
							if ($res_data["type"] == "html") {
								$data["QrcodeUrl"] = $res_data["data"];
							}
						} else {
							$data["CertifiHtml"] = $res_data;
						}
					}
				}
			}
		}
		$certifi_person = \think\Db::name("certifi_person")->where("auth_user_id", $uid)->find();
		$certifi_company = \think\Db::name("certifi_company")->where("auth_user_id", $uid)->find();
		if (isset($certifi_person["id"])) {
			if ($certifi_person["status"] == 1 && isset($certifi_company["status"]) && $certifi_company["status"] == 2) {
				$data["PersonalCertifiStatus"] = 1;
			}
		}
		if (isset($param["status"]) && $param["status"] == "closed") {
			if ($data["PersonalCertifiStatus"]) {
				$data["Verified"]["certifi_message"]["status"] = 1;
				$data["Verified"]["certifi_message"]["type"] = "certifi_person";
			} else {
				header("location:{$this->ViewModel->domain}/verified?status=home");
				exit;
			}
		}
		return $this->view("verified", $data);
	}
	public function authorDown()
	{
		try {
			$auth_path = configuration("certifi_business_author_path");
			if (!$auth_path) {
				throw new Exception("文件资源不存在");
			}
			return download(config("author_attachments") . $auth_path, "shouQuan");
		} catch (\Throwable $e) {
			return jsons(["status" => 400, "msg" => $e->getMessage()]);
		}
	}
	public function security(\think\Request $request)
	{
		if ($this->zjmf_authorize()) {
			$OauthBind = controller("OauthBind");
			$listing = $OauthBind->listing($request);
			$dataSecurity["oauthBind"] = $listing["data"];
			$data["Security"] = $dataSecurity;
		}
		$Login = controller("Login");
		$mobileLoginVerifyPage = $Login->mobileLoginVerifyPage();
		$model = \think\Db::name("clients")->find(request()->uid);
		$p_model = \think\Db::name("certifi_person")->where("auth_user_id", request()->uid)->find();
		$arr = [$model["password"] ? 1 : 0, $model["phonenumber"] ? 1 : 0, $model["email"] ? 1 : 0, $p_model && $p_model["status"] == 1 ? 1 : 0, $model["seond_verify"] ? 1 : 0, $model["is_login_sms_reminder"] ? 1 : 0, $model["email_remind"] ? 1 : 0];
		$count = count($arr);
		$number = 0;
		$t = get_title_lang("security_weak");
		foreach ($arr as $val) {
			$val == 1 && $number++;
		}
		if (intval($number / $count * 100) > 33) {
			$t = get_title_lang("security_moderate");
		}
		if (intval($number / $count * 100) > 66) {
			$t = get_title_lang("security_strong");
		}
		if (intval($number / $count * 100) == 100) {
			$t = get_title_lang("security_super_strong");
		}
		$data["SmsCountry"] = $mobileLoginVerifyPage["data"];
		$data["BindPhoneChange"] = session("bind_phone_change") ?? 0;
		$data["BindEmailChange"] = session("bind_email_change") ?? 0;
		$data["Title"] = get_title_lang("title_security");
		$data["percentage"] = [$t, intval($number / $count * 100)];
		$data["Bot"] = configuration("bot");
		return $this->view("security", $data);
	}
	public function getPercentage()
	{
	}
	public function message(\think\Request $request)
	{
		$param = $request->param();
		$page = $param["page"] >= 1 ? intval($param["page"]) : 1;
		$limit = $param["limit"] >= 1 ? intval($param["limit"]) : 20;
		$request->page = $page;
		$request->limit = $limit;
		$SystemMessage = controller("SystemMessage");
		$getMessageList = $SystemMessage->getMessageList($request);
		$getUnreadList = $SystemMessage->getUnreadList();
		$dataMessage = $getMessageList["data"]["list"];
		$data["Sys_messgage_unread"] = $getUnreadList["data"];
		$data["Message"] = $dataMessage;
		$data["Title"] = get_title_lang("title_message");
		$data["Total"] = $getMessageList["data"]["count"];
		$data["Pages"] = $this->ajaxPages($dataMessage, $limit, $page, $getMessageList["data"]["count"]);
		$data["Limit"] = $limit;
		return $this->view("message", $data);
	}
	public function addfunds(\think\Request $request)
	{
		$Pay = controller("Pay");
		$rechargePage = $Pay->rechargePage();
		$dataAddfunds["addfunds"] = $rechargePage["data"];
		$data["Addfunds"] = $dataAddfunds;
		$data["Title"] = get_title_lang("title_addfunds");
		return $this->view("addfunds", $data);
	}
	public function billing(\think\Request $request)
	{
		$param = $request->param();
		$page = $param["page"] >= 1 ? intval($param["page"]) : 1;
		$limit = $param["limit"] >= 1 ? intval($param["limit"]) : 20;
		$orderby = strval($param["orderby"]) ? strval($param["orderby"]) : "id";
		$sort = $param["sort"] ?? "DESC";
		$request->page = $page;
		$request->limit = $limit;
		$request->order = $orderby;
		$request->sort = $sort;
		$UserInvoice = controller("UserInvoice");
		$getInvoices = $UserInvoice->getInvoices();
		$getCombineInvoices = $UserInvoice->getCombineInvoices();
		$dataBilling = $getInvoices["data"]["invoices"];
		foreach ($dataBilling as &$value) {
			$value = ["id" => $value["id"], "subtotal" => $value["subtotal"], "paid_time" => $value["paid_time"], "due_time" => $value["due_time"], "status" => $value["status"], "status_zh" => $value["status_zh"], "type" => $value["type"], "type_zh" => $value["type_zh"], "payment_zh" => $value["payment_zh"]];
		}
		$data["Currency"] = $getInvoices["data"]["currency"];
		$data["Combine_billing"] = $getCombineInvoices["data"]["invoices"];
		$data["Count"] = $getCombineInvoices["data"]["count"];
		$data["Billing"] = $dataBilling;
		$data["Title"] = get_title_lang("title_billing");
		$data["Total"] = $getInvoices["data"]["total"];
		$data["Total_money"] = $getCombineInvoices["data"]["total"];
		$data["Pages"] = $this->ajaxPages($dataBilling, $limit, $page, $getInvoices["data"]["total"]);
		$data["Limit"] = $limit;
		return $this->view("billing", $data);
	}
	public function combinebilling(\think\Request $request)
	{
		$UserInvoice = controller("UserInvoice");
		if ($request->isGet()) {
			$getCombineInvoices = $UserInvoice->getCombineInvoices();
			$data["Combine_billing"] = $getCombineInvoices["data"]["invoices"];
			$data["Count"] = $getCombineInvoices["data"]["count"];
			$data["Title"] = get_title_lang("title_combinebilling");
			$data["Total"] = round($getCombineInvoices["data"]["total"], 2);
		}
		if ($request->isPost()) {
			$combine = $UserInvoice->combineInvoices();
			if ($combine["status"] == 1001 || $combine["status"] == 200) {
				$invoiceid = $combine["data"]["invoice_id"];
				$payment = $combine["data"]["payment"] ?: "";
				$data["SuccessMsg"] = $combine["msg"];
				header("location:{$this->ViewModel->domain}/viewbilling?id={$invoiceid}&payment={$payment}");
				exit;
			} else {
				$data["ErrorMsg"] = $combine["msg"];
			}
		}
		return $this->view("combinebilling", $data);
	}
	public function viewbilling(\think\Request $request)
	{
		$UserInvoice = controller("UserInvoice");
		$getInvoicesDetail = $UserInvoice->getInvoicesDetail();
		$data["ViewBilling"] = $getInvoicesDetail["data"];
		$prefix = $getInvoicesDetail["data"]["currency"]["prefix"];
		$suffix = $getInvoicesDetail["data"]["currency"]["suffix"];
		$uid = $request->uid;
		$invoiceid = $request->id;
		$invoice = \think\Db::name("invoices")->where("id", $invoiceid)->where("uid", $uid)->find();
		if (!$invoice) {
			return redirect("/billing");
		}
		$status = $invoice["status"];
		$data["Pay"]["invoice_num"] = $invoice["invoice_num"];
		if ($status == "Paid") {
			$data["Pay"]["PayStatus"] = "Paid";
			$data["Pay"]["total"] = $invoice["subtotal"];
			$data["Pay"]["invoiceid"] = $invoiceid;
		} else {
			$Pay = controller("Pay");
			$request->invoiceid = $invoiceid;
			$request->flag = true;
			if ($invoice["type"] == "recharge") {
				$data["Action"] = "recharge";
				$request->flag = false;
				$pay_data = $Pay->startPay($request);
				$data["Pay"] = $pay_data["data"];
				if ($pay_data["status"] != 200) {
					$data["ErrorMsg"] = $pay_data["msg"];
					$subtotal = $invoice["subtotal"];
					$data["Pay"]["total"] = $subtotal;
				}
			} else {
				$data["Action"] = "billing";
				$client = \think\Db::name("clients")->field("credit,credit_limit,is_open_credit_limit,currency")->where("id", $uid)->find();
				$client["is_open_credit_limit"] = configuration("credit_limit") == 1 ? $client["is_open_credit_limit"] : 0;
				$credit = $client["credit"];
				$subtotal = $invoice["subtotal"];
				$client["amount_to_be_settled"] = \think\Db::name("invoices")->where("status", "Paid")->where("use_credit_limit", 1)->where("invoice_id", 0)->where("is_delete", 0)->where("uid", $uid)->sum("total");
				$unpaid = \think\Db::name("invoices")->where("type", "credit_limit")->where("status", "Unpaid")->where("is_delete", 0)->where("uid", $uid)->sum("total");
				$client["credit_limit_used"] = round($client["amount_to_be_settled"] + $unpaid, 2);
				$client["credit_limit_balance"] = round($client["credit_limit"] - $client["credit_limit_used"] > 0 ? $client["credit_limit"] - $client["credit_limit_used"] : 0, 2);
				$param = $request->param();
				if (!empty($client["is_open_credit_limit"]) && $subtotal <= $client["credit_limit_balance"]) {
					if (!is_null($param["use_credit_limit"])) {
						if ($param["use_credit_limit"] == 1) {
							$data["Pay"]["use_credit_limit"] = 1;
							$apply_limit_credit = $Pay->applyCreditLimit($request);
							if ($apply_limit_credit["status"] == 1001) {
								$data["Pay"]["PayStatus"] = "Paid";
							} else {
								$data["ErrorMsg"] = $apply_limit_credit["msg"];
							}
							$data["Title"] = get_title_lang("title_viewbilling");
							return $this->view("viewbilling", $data);
						}
					} else {
						$currency = \think\Db::name("currencies")->field("prefix,suffix")->where("id", $client["currency"])->find();
						$data["Pay"]["use_credit_limit"] = 1;
						$data["Pay"]["total"] = $subtotal;
						$data["Pay"]["total_desc"] = $currency["prefix"] . $subtotal . $currency["suffix"];
						$data["Pay"]["deduction"] = $subtotal;
						$data["Pay"]["credit_limit_balance"] = $client["credit_limit_balance"];
						$data["Pay"]["invoiceid"] = $invoiceid;
						$data["Pay"]["action"] = "viewbilling";
						$data["Title"] = get_title_lang("title_viewbilling");
						return $this->view("viewbilling", $data);
					}
				}
				if ($credit <= 0) {
					$request->flag = false;
					$pay_data = $Pay->startPay($request);
					if ($pay_data["status"] == 200) {
						$data["Pay"] = $pay_data["data"];
					} else {
						$data["ErrorMsg"] = $pay_data["msg"];
						$data["Pay"]["total"] = $subtotal;
					}
				} else {
					$subtotal = $invoice["subtotal"];
					$credit_page = $Pay->useCreditPage();
					$credit_data = $credit_page["data"];
					$data["Pay"]["gateway_list"] = $credit_data["gateway_list"];
					$data["Pay"]["payment"] = $credit_data["payment"];
					$data["Pay"]["total"] = $credit_data["total"];
					$data["Pay"]["total_desc"] = $credit_data["currency"]["prefix"] . $credit_data["total"] . $credit_data["currency"]["suffix"];
					$data["Pay"]["credit"] = $credit_data["credit"];
					$data["Pay"]["invoiceid"] = $credit_data["invoiceid"];
					$data["Pay"]["deduction"] = $credit_data["deduction"];
					$pay_data = $Pay->startPay($request);
					if ($pay_data["status"] == 200) {
						$data["Pay"] = $pay_data["data"];
						$data["Pay"]["pay"] = true;
						$data["Pay"]["deduction"] = $credit;
						$data["Pay"]["use_credit"] = 1;
						if ($subtotal <= $credit) {
							$data["Pay"]["deduction"] = $subtotal;
							$data["Pay"]["credit_enough"] = 1;
							$data["Pay"]["total"] = $subtotal;
							$data["Pay"]["total_desc"] = $prefix . $subtotal . $suffix;
						} else {
							$data["Pay"]["deduction"] = $credit;
							$data["Pay"]["credit_enough"] = 0;
							$data["Pay"]["total"] = $subtotal;
							$data["Pay"]["total_desc"] = $prefix . bcsub($subtotal, $credit, 2) . $suffix;
						}
					} else {
						$data["ErrorMsg"] = $pay_data["msg"];
						$data["Pay"]["total"] = $subtotal;
					}
				}
			}
		}
		$data["Title"] = get_title_lang("title_billing") . "-" . $invoiceid;
		return $this->view("viewbilling", $data);
	}
	public function transaction(\think\Request $request)
	{
		$param = $request->param();
		$page = $param["page"] >= 1 ? intval($param["page"]) : 1;
		$limit = $param["limit"] >= 1 ? intval($param["limit"]) : 20;
		$orderby = strval($param["orderby"]) ? strval($param["orderby"]) : "id";
		$sort = $param["sort"] ?? "DESC";
		$action = isset($param["action"]) ? $param["action"] : "accounts_record";
		$request->page = $page;
		$request->limit = $limit;
		$request->orderby = $orderby;
		$request->sorting = $sort;
		$UserInvoice = controller("UserInvoice");
		if ($action == "accounts_record") {
			if (!in_array($orderby, ["id", "invoice_id", "amount_in", "pay_time", "gateway", "description", "type", "trans_id"])) {
				$orderby = "id";
			}
			$UserInvoice->request->order = $orderby;
			$records = $UserInvoice->accountsRecord();
		} elseif ($action == "credit_record") {
			if (!in_array($orderby, ["id", "amount", "description", "type", "create_time"])) {
				$orderby = "id";
			}
			$UserInvoice->request->order = $orderby;
			$records = $UserInvoice->creditRecord();
		} elseif ($action == "credit_limit") {
			if (!in_array($orderby, ["id", "create_time", "due_time", "paid_time", "subtotal", "payment", "status"])) {
				$orderby = "id";
			}
			$CreditLimt = controller("CreditLimit");
			$CreditLimt->request->order = $orderby;
			$index = $CreditLimt->list();
			$records["data"]["accounts"] = $index["invoices"];
			$records["data"]["total"] = $index["count"];
			$data["InvoiceStatus"] = $index["invoice_status"];
		} elseif ($action == "recharge_record") {
			if (!in_array($orderby, ["id", "invoice_id", "amount_in", "pay_time", "gateway", "description", "type", "trans_id"])) {
				$orderby = "id";
			}
			$UserInvoice->request->order = $orderby;
			$records = $UserInvoice->rechargeRecord();
		} elseif ($action == "refund_record") {
			if (!in_array($orderby, ["id", "invoice_id", "amount_out", "pay_time", "description", "create_time"])) {
				$orderby = "id";
			}
			$UserInvoice->request->order = $orderby;
			$records = $UserInvoice->refundRecord();
		} elseif ($action == "withdraw_record") {
			if (!in_array($orderby, ["id", "num", "reason", "des", "status", "create_time"])) {
				$orderby = "id";
			}
			$UserInvoice->request->order = $orderby;
			$records = $UserInvoice->withdrawRecord();
			$records["data"]["accounts"] = $records["data"]["rows"];
		}
		$dataTransaction = $records["data"]["accounts"];
		$data["Currency"] = $records["data"]["currency"];
		$data["Transaction"] = $dataTransaction;
		$data["Title"] = get_title_lang("title_transaction");
		$data["Total"] = $records["data"]["total"];
		$data["Pages"] = $this->ajaxPages($dataTransaction, $limit, $page, $records["data"]["total"]);
		$data["Limit"] = $limit;
		return $this->view("transaction", $data);
	}
	public function news(\think\Request $request)
	{
		if (trim($request->alias)) {
			$model = \think\Db::name("news_type")->field("id")->where("alias", trim($request->alias))->find();
			$request->cate = $model ? $model["id"] : $request->cate;
		}
		$param = $request->param();
		$page = $param["page"] >= 1 ? intval($param["page"]) : 1;
		$limit = $param["limit"] >= 1 ? intval($param["limit"]) : 20;
		$orderby = strval($param["orderby"]) ? strval($param["orderby"]) : "id";
		$sort = $param["sort"] ?? "DESC";
		$keywords = $param["keywords"] ?? "";
		$cate = isset($param["cate"]) ? intval($param["cate"]) : 0;
		$params["limit"] = $limit;
		if (!empty($keywords)) {
			$params["search"] = $keywords;
			$params["page"] = $page;
			$params["data"] = "Search";
			$newsList = $this->ViewModel->newsSearch($params);
		} else {
			if (!empty($cate)) {
				$params["html2"] = $cate;
				$params["html3"] = $page;
				$params["data"] = "ListCate";
				$newsList = $this->ViewModel->newsListCate($params);
			} else {
				$params["html2"] = $page;
				$newsList = $this->ViewModel->newsList($params);
			}
		}
		$newsCate = $this->ViewModel->newsCate();
		$data["NewsCate"] = $newsCate;
		$data["NewsList"] = $newsList["list"];
		$data["Title"] = get_title_lang("title_news");
		$data["Total"] = $newsList["count"];
		$data["Pages"] = $this->ajaxPages($newsList["list"], $limit, $page, $newsList["count"]);
		$data["Limit"] = $limit;
		return $this->view("news", $data);
	}
	public function newsview(\think\Request $request)
	{
		$param = $request->param();
		$params["html2"] = $param["id"];
		$data["ViewAnnouncement"] = $this->ViewModel->newsContent($params);
		if ($data["ViewAnnouncement"]["label"]) {
			$data["ViewAnnouncement"]["label"] = array_filter(explode(",", $data["ViewAnnouncement"]["label"]));
		}
		$data["Title"] = $data["ViewAnnouncement"]["title"];
		return $this->view("newsview", $data);
	}
	public function knowledgebase(\think\Request $request)
	{
		if (trim($request->alias)) {
			$model = \think\Db::name("news_type")->field("id")->where("alias", trim($request->alias))->find();
			$request->cate = $model ? $model["id"] : $request->cate;
		}
		$param = $request->param();
		$page = $param["page"] >= 1 ? intval($param["page"]) : 1;
		$limit = $param["limit"] >= 1 ? intval($param["limit"]) : 20;
		$orderby = strval($param["orderby"]) ? strval($param["orderby"]) : "id";
		$sort = $param["sort"] ?? "DESC";
		$keywords = $param["keywords"] ?? "";
		$cate = isset($param["cate"]) ? intval($param["cate"]) : 0;
		$params["limit"] = $limit;
		if (!empty($keywords)) {
			$params["search"] = $keywords;
			$params["page"] = $page;
			$params["data"] = "Search";
			$helpList = $this->ViewModel->helpSearch($params);
		} else {
			if (!empty($cate)) {
				$params["html2"] = $cate;
				$params["html3"] = $page;
				$params["data"] = "ListCate";
				$helpList = $this->ViewModel->helpListCate($params);
			} else {
				$params["html2"] = $page;
				$helpList = $this->ViewModel->helpList($params);
			}
		}
		$helpCate = $this->ViewModel->helpCate();
		$data["HelpCate"] = $helpCate;
		$data["HelpList"] = $helpList["list"];
		$data["Title"] = get_title_lang("title_knowledgebase");
		$data["Total"] = $helpList["count"];
		$data["Pages"] = $this->ajaxPages($helpList["list"], $limit, $page, $helpList["count"]);
		$data["Limit"] = $limit;
		return $this->view("knowledgebase", $data);
	}
	public function knowledgebaseview(\think\Request $request)
	{
		$param = $request->param();
		$params["html2"] = $param["id"];
		$data["KnowledgeBaseArticle"] = $this->ViewModel->helpContent($params);
		if ($data["KnowledgeBaseArticle"]["label"]) {
			$data["KnowledgeBaseArticle"]["label"] = array_filter(explode(",", $data["KnowledgeBaseArticle"]["label"]));
		}
		$data["Title"] = $data["KnowledgeBaseArticle"]["title"];
		return $this->view("knowledgebaseview", $data);
	}
	public function supporttickets(\think\Request $request)
	{
		$param = $request->param();
		$page = $param["page"] >= 1 ? intval($param["page"]) : 1;
		$limit = $param["limit"] >= 1 ? intval($param["limit"]) : 20;
		$orderby = strval($param["orderby"]) ? strval($param["orderby"]) : "id";
		$sort = $param["sort"] ?? "DESC";
		$request->page = $page;
		$request->limit = $limit;
		$request->orderby = $orderby;
		$request->sort = $sort;
		$Ticket = controller("Ticket");
		$getList = $Ticket->getList($request);
		$dataSupporttickets = $getList["data"]["list"];
		$data["SupportTickets"] = $dataSupporttickets;
		$data["Title"] = get_title_lang("title_supporttickets");
		$data["Total"] = $getList["data"]["sum"];
		$data["Pages"] = $this->ajaxPages($dataSupporttickets, $limit, $page, $getList["data"]["sum"]);
		$data["Limit"] = $limit;
		return $this->view("supporttickets", $data);
	}
	public function viewticket(\think\Request $request)
	{
		$Ticket = controller("Ticket");
		if ($request->isPost()) {
			$uploadImage = $this->ticketUploadImage();
			if ($uploadImage["status"] !== 200) {
				$data["ErrorMsg"] = $uploadImage["msg"];
			} else {
				if ($uploadImage["attachment"]) {
					$request->attachment = $uploadImage["attachment"];
				}
				$result = $Ticket->replyTicket($request);
				if ($result["status"] == 200) {
					$data["SuccessMsg"] = $result["msg"];
					header("location:{$this->ViewModel->domain}/supporttickets");
					exit;
				} else {
					$data["ErrorMsg"] = $result["msg"];
				}
			}
		}
		$ticketDetail = $Ticket->ticketDetail($request);
		$data["ViewTicket"] = $ticketDetail["data"];
		$data["Title"] = get_title_lang("title_viewticket");
		return $this->view("viewticket", $data);
	}
	public function submitticket(\think\Request $request)
	{
		$Ticket = controller("Ticket");
		if ($request->isPost()) {
			$uploadImage = $this->ticketUploadImage();
			if ($uploadImage["status"] !== 200) {
				$data["ErrorMsg"] = $uploadImage["msg"];
			} else {
				if ($uploadImage["attachment"]) {
					$request->attachment = $uploadImage["attachment"];
				}
				$result = $Ticket->createTicket($request);
				if ($result["status"] == 200) {
					$data["SuccessMsg"] = $result["msg"];
					header("location:{$this->ViewModel->domain}/supporttickets");
					exit;
				} else {
					$data["ErrorMsg"] = $result["msg"] ?: "上传附件格式不正确";
				}
			}
		}
		$getOpenTicketPage = $Ticket->getOpenTicketPage($request);
		$getDepartmentList = $Ticket->getDepartmentList($request);
		$dataSubmitticket["ticketpage"] = $getOpenTicketPage["data"];
		$dataSubmitticket["department"] = $getDepartmentList["data"];
		$data["SubmitTicket"] = $dataSubmitticket;
		if ($request->dptid) {
			$ticketCustom = \think\Db::name("customfields")->where("relid", $request->dptid)->where("type", "ticket")->select()->toArray();
			foreach ($ticketCustom as $key => $val) {
				if ($val["fieldtype"] == "dropdown") {
					$ticketCustom[$key]["dropdown_option"] = explode(",", $val["fieldoptions"]);
				}
			}
			$data["ticketCustom"] = $ticketCustom;
		}
		$data["Title"] = get_title_lang("title_submitticket");
		return $this->view("submitticket", $data);
	}
	public function affiliates(\think\Request $request)
	{
		$param = $request->param();
		$page = $param["page"] >= 1 ? intval($param["page"]) : 1;
		$limit = $param["limit"] >= 1 ? intval($param["limit"]) : 20;
		$orderby = strval($param["orderby"]) ? strval($param["orderby"]) : "id";
		$sort = $param["sort"] ?? "DESC";
		$action = isset($param["action"]) ? $param["action"] : "";
		$request->page = $page;
		$request->limit = $limit;
		$request->order = $orderby;
		$request->username = $param["keywords"];
		$UserAffiliate = controller("UserAffiliate");
		$affpage = $UserAffiliate->affpage($request);
		$affindex = $UserAffiliate->affindex($request);
		$dataAffiliates["aff"] = $affpage["aff"];
		$dataAffiliates["data"] = $affindex["data"];
		$dataAffiliates["datarr"] = $affindex["datarr"];
		$dataAffiliates["is_withdraw"] = $affindex["is_withdraw"];
		$dataAffiliates["affiliate_withdraw"] = $affindex["affiliate_withdraw"];
		$dataAffiliates["is_open"] = configuration("affiliate_enabled");
		$data["Affiliates"] = $dataAffiliates;
		$data["Title"] = get_title_lang("title_affiliates");
		$data["Limit"] = $limit;
		if ($action == "affbuyrecord") {
			$affbuyrecord = $UserAffiliate->affbuyrecord($request);
			$dataAffbuyrecord = $affbuyrecord["data"];
			$data["AffBuyRecord"] = $dataAffbuyrecord;
			$data["Total"] = $affbuyrecord["total"];
			$data["Pages"] = $this->ajaxPages($dataAffbuyrecord, $limit, $page, $affbuyrecord["total"]);
			$data["Limit"] = $limit;
			$data["Orderby"] = $orderby;
			$data["Sort"] = $sort;
			echo $this->view("affiliates/affbuyrecord", $data, ["autoinclude" => false]);
			exit;
		} elseif ($action == "withdrawrecord") {
			$withdrawrecord = $UserAffiliate->withdrawrecord($request);
			$dataWithdrawrecord = $withdrawrecord["data"];
			$data["WithdrawRecord"] = $dataWithdrawrecord;
			$data["Total"] = $withdrawrecord["total"];
			$data["Pages"] = $this->ajaxPages($dataWithdrawrecord, $limit, $page, $withdrawrecord["total"]);
			$data["Limit"] = $limit;
			$data["Orderby"] = $orderby;
			$data["Sort"] = $sort;
			echo $this->view("affiliates/withdrawrecord", $data, ["autoinclude" => false]);
			exit;
		} elseif ($action == "useraffilist") {
			$useraffilist = $UserAffiliate->useraffilist($request);
			$dataUseraffilist = $useraffilist["data"]["rows"];
			$data["UserAffilist"] = $dataUseraffilist;
			$data["Total"] = $useraffilist["data"]["total"];
			$data["Pages"] = $this->ajaxPages($dataUseraffilist, $limit, $page, $useraffilist["data"]["total"]);
			$data["Limit"] = $limit;
			$data["Orderby"] = $orderby;
			$data["Sort"] = $sort;
			echo $this->view("affiliates/useraffilist", $data, ["autoinclude" => false]);
			exit;
		}
		return $this->view("affiliates", $data);
	}
	public function downloads(\think\Request $request)
	{
		$param = $request->param();
		$action = $param["action"];
		$Down = controller("Down");
		if ($action == "download") {
			$request->type = $request->type ?? 1;
			$download = $Down->productFile($request);
			if (is_array($download) && $download["status"] == 200) {
				$url = $this->ViewModel->domain . "/downloads?action=download&id={$param["id"]}&cate_id={$param["cate_id"]}&type=0";
				cookie("downloads_location_url", $url);
				$this->redirect("/downloads");
			} else {
				if (is_array($download)) {
					if ($download["type"] == 1) {
						header("location:{$this->ViewModel->domain}/login");
						exit;
					} else {
						$data["ErrorMsg"] = $download["msg"];
					}
				} else {
					return $download;
				}
			}
			if ($request->type == 0) {
				if (is_array($download) && $download["msg"]) {
					$msg = $download["msg"];
				} else {
					$msg = "";
				}
				exit($msg);
			}
		}
		$cates = $Down->cates($request);
		$dataDownloads["downloads"] = $cates["data"];
		$data["Downloads"] = $dataDownloads;
		if (cookie("downloads_location_url")) {
			$data["Downloads"]["location_url"] = cookie("downloads_location_url");
			cookie("downloads_location_url", null);
		}
		$data["Title"] = get_title_lang("title_downloads");
		return $this->view("downloads", $data);
	}
	public function systemlog(\think\Request $request)
	{
		$param = $request->param();
		$page = $param["page"] >= 1 ? intval($param["page"]) : 1;
		$limit = $param["limit"] >= 1 ? intval($param["limit"]) : 20;
		$orderby = strval($param["orderby"]) ? strval($param["orderby"]) : "id";
		$sort = $param["sort"] ?? "DESC";
		if (!in_array($orderby, ["description", "create_time", "ipaddr", "user", "id"])) {
			$orderby = "id";
		}
		$request->page = $page;
		$request->limit = $limit;
		$request->orderby = $orderby;
		$request->sorting = $sort;
		$RecordLog = controller("RecordLog");
		$getUserLogs = $RecordLog->getUserLogs($request);
		$dataSystemlog = $getUserLogs["data"]["log_list"];
		$data["SystemLog"] = $dataSystemlog;
		$data["Title"] = get_title_lang("title_systemlog");
		$data["Total"] = $getUserLogs["data"]["count"];
		$data["Pages"] = $this->ajaxPages($dataSystemlog, $limit, $page, $getUserLogs["data"]["count"]);
		$data["Limit"] = $limit;
		return $this->view("systemlog", $data);
	}
	public function loginlog(\think\Request $request)
	{
		$param = $request->param();
		$page = $param["page"] >= 1 ? intval($param["page"]) : 1;
		$limit = $param["limit"] >= 1 ? intval($param["limit"]) : 20;
		$orderby = strval($param["orderby"]) ? strval($param["orderby"]) : "id";
		$sort = $param["sort"] ?? "DESC";
		if (!in_array($orderby, ["description", "create_time", "ipaddr", "user", "id"])) {
			$orderby = "id";
		}
		$request->page = $page;
		$request->limit = $limit;
		$request->orderby = $orderby;
		$request->sort = $sort;
		$User = controller("User");
		$user_action_log = $User->user_action_log($request);
		$dataLoginlog = $user_action_log["data"]["list"];
		foreach ($dataLoginlog as &$value) {
			$value = ["create_time" => $value["create_time"], "description" => $value["description"], "user" => $value["user"], "ipaddr" => $value["ipaddr"]];
		}
		$data["LoginLog"] = $dataLoginlog;
		$data["Title"] = get_title_lang("title_loginlog");
		$data["Total"] = $user_action_log["data"]["sum"];
		$data["Pages"] = $this->ajaxPages($dataLoginlog["list"], $limit, $page, $user_action_log["data"]["sum"]);
		$data["Limit"] = $limit;
		return $this->view("loginlog", $data);
	}
	public function apilog(\think\Request $request)
	{
		$param = $request->param();
		$page = $param["page"] >= 1 ? intval($param["page"]) : 1;
		$limit = $param["limit"] >= 1 ? intval($param["limit"]) : 20;
		$orderby = strval($param["orderby"]) ? strval($param["orderby"]) : "id";
		$sort = $param["sort"] ?? "DESC";
		if (!in_array($orderby, ["description", "create_time", "ip", "user", "id"])) {
			$orderby = "id";
		}
		$request->page = $page;
		$request->limit = $limit;
		$request->order = $orderby;
		$request->sort = $sort;
		$User = controller("ZjmfFinanceApi");
		$user_action_log = $User->apiLog($request);
		$dataLoginlog = $user_action_log["data"]["logs"];
		foreach ($dataLoginlog as &$value) {
			$value = ["create_time" => $value["create_time"], "description" => $value["description"], "user" => $value["username"], "ipaddr" => $value["ip"]];
		}
		$data["APILog"] = $dataLoginlog;
		$data["Title"] = get_title_lang("title_apilog");
		$data["Total"] = $user_action_log["data"]["count"];
		$data["Pages"] = $this->ajaxPages($data["APILog"], $limit, $page, $user_action_log["data"]["count"]);
		$data["Limit"] = $limit;
		return $this->view("apilog", $data);
	}
	public function invoicelist(\think\Request $request)
	{
		$param = $request->param();
		$page = $param["page"] >= 1 ? intval($param["page"]) : 1;
		$limit = $param["limit"] >= 1 ? intval($param["limit"]) : 20;
		$orderby = strval($param["orderby"]) ? strval($param["orderby"]) : "id";
		$sort = $param["sort"] ?? "DESC";
		$id = isset($param["id"]) ? intval($param["id"]) : 0;
		$action = isset($param["action"]) ? $param["action"] : "";
		$Voucher = controller("Voucher");
		$request->page = $page;
		$request->limit = $limit;
		$request->orderby = $orderby;
		$request->sort = $sort;
		if ($request->isPost()) {
			$result = $Voucher->postIssueVoucher();
			if ($result["status"] == 200) {
				$data["SuccessMsg"] = $result["msg"];
				header("location:{$this->ViewModel->domain}/invoicelist");
				exit;
			} else {
				$data["ErrorMsg"] = $result["msg"];
			}
		}
		if (!empty($id) && $action == "check") {
			$getCurrency = $Voucher->getCurrency();
			$getVoucherDetail = $Voucher->getVoucherDetail();
			$data["Currency"] = $getCurrency["data"]["currency"];
			$dataVoucherdetail = $getVoucherDetail["data"];
			$data["Invoicedetail"] = $dataVoucherdetail;
			$data["Title"] = get_title_lang("title_invoicelist_check");
		} else {
			if ($action == "invoiceapply") {
				$getVoucherRequest = $Voucher->getVoucherRequest();
				$getIssueVoucher = $Voucher->getIssueVoucher();
				$getCurrency = $Voucher->getCurrency();
				$data["Currency"] = $getCurrency["data"]["currency"];
				$dataVoucherrequest = $getVoucherRequest["data"]["invoices"];
				$data["Issuevoucher"] = $getIssueVoucher["data"];
				$data["Invoiceapply"] = $dataVoucherrequest;
				$data["Title"] = get_title_lang("title_invoicelist_invoiceapply");
				$data["Total"] = $getVoucherRequest["data"]["total"];
				$data["Pages"] = $this->ajaxPages($dataInvoice, $limit, $page, $getVoucherRequest["data"]["total"]);
				$data["Limit"] = $limit;
			} else {
				if (!in_array($orderby, ["id", "create_time", "title", "amount", "status", "name"])) {
					$orderby = "id";
				}
				$Voucher->request->order = $orderby;
				$getCurrency = $Voucher->getCurrency();
				$getVoucherList = $Voucher->getVoucherList();
				$data["Currency"] = $getCurrency["data"]["currency"];
				$dataInvoice = $getVoucherList["data"]["voucher"];
				$data["Invoicelist"] = $dataInvoice;
				$data["Title"] = get_title_lang("title_invoicelist_else");
				$data["Total"] = $getVoucherList["data"]["total"];
				$data["Pages"] = $this->ajaxPages($dataInvoice, $limit, $page, $getVoucherList["data"]["total"]);
				$data["Limit"] = $limit;
			}
		}
		return $this->view("invoicelist", $data);
	}
	public function invoicecompany(\think\Request $request)
	{
		$param = $request->param();
		$page = $param["page"] >= 1 ? intval($param["page"]) : 1;
		$limit = $param["limit"] >= 1 ? intval($param["limit"]) : 20;
		$orderby = strval($param["orderby"]) ? strval($param["orderby"]) : "id";
		$sort = $param["sort"] ?? "DESC";
		$id = isset($param["id"]) ? intval($param["id"]) : 0;
		$action = isset($param["action"]) ? $param["action"] : "";
		$request->page = $page;
		$request->limit = $limit;
		$request->orderby = $orderby;
		$request->sort = $sort;
		$Voucher = controller("Voucher");
		if ($request->isPost()) {
			if (!empty($id) && $action == "del") {
				$result = $Voucher->deleteVoucherInfo();
			} else {
				$result = $Voucher->postVoucherInfo();
			}
			if ($result["status"] == 200) {
				$data["SuccessMsg"] = $result["msg"];
				header("location:{$this->ViewModel->domain}/invoicecompany");
				exit;
			} else {
				$data["ErrorMsg"] = $result["msg"];
			}
		}
		if (!empty($id) && $action == "edit") {
			$getVoucherInfo = $Voucher->getVoucherInfo();
			$data["Invoicecompany"] = $getVoucherInfo["data"];
			$data["Title"] = get_title_lang("title_invoicecompany_edit");
		} else {
			if (!in_array($orderby, ["id", "title", "issue_type", "voucher_type", "tax_id"])) {
				$orderby = "id";
			}
			$Voucher->request->order = $orderby;
			$getVoucherInfoList = $Voucher->getVoucherInfoList();
			$dataVoucherinfolist = $getVoucherInfoList["data"]["voucher_type"];
			$data["Invoicecompany"] = $dataVoucherinfolist;
			$data["Title"] = get_title_lang("title_invoicecompany");
			$data["Total"] = $getVoucherInfoList["data"]["total"];
			$data["Pages"] = $this->ajaxPages($dataVoucherinfolist, $limit, $page, $getVoucherInfoList["data"]["total"]);
			$data["Limit"] = $limit;
		}
		return $this->view("invoicecompany", $data);
	}
	public function invoiceaddress(\think\Request $request)
	{
		$param = $request->param();
		$page = $param["page"] >= 1 ? intval($param["page"]) : 1;
		$limit = $param["limit"] >= 1 ? intval($param["limit"]) : 20;
		$orderby = strval($param["orderby"]) ? strval($param["orderby"]) : "id";
		$sort = $param["sort"] ?? "DESC";
		$id = isset($param["id"]) ? intval($param["id"]) : 0;
		$action = isset($param["action"]) ? $param["action"] : "";
		$request->page = $page;
		$request->limit = $limit;
		$request->orderby = $orderby;
		$request->sort = $sort;
		$Voucher = controller("Voucher");
		if ($request->isPost()) {
			if (!empty($id) && $action == "del") {
				$result = $Voucher->deleteVoucherPost();
			} else {
				$result = $Voucher->postVoucherPost();
			}
			if ($result["status"] == 200) {
				$data["SuccessMsg"] = $result["msg"];
				header("location:{$this->ViewModel->domain}/invoiceaddress");
				exit;
			} else {
				$data["ErrorMsg"] = $result["msg"];
			}
		}
		if (!empty($id) && $action == "edit") {
			$getVoucherPost = $Voucher->getVoucherPost();
			$getAreaList = $Voucher->getAreaList();
			$data["Areas"] = $getAreaList["data"]["areas"];
			$data["Invoiceaddress"] = $getVoucherPost["data"]["voucher_post"];
			$data["Title"] = get_title_lang("title_invoiceaddress_edit");
		} else {
			if (!in_array($orderby, ["id", "phone"])) {
				$orderby = "id";
			}
			$Voucher->request->order = $orderby;
			$getVoucherPostList = $Voucher->getVoucherPostList();
			$getAreaList = $Voucher->getAreaList();
			$dataVoucherpostlist = $getVoucherPostList["data"]["voucher_post"];
			$data["Areas"] = $getAreaList["data"]["areas"];
			$data["Invoiceaddress"] = $dataVoucherpostlist;
			$data["Title"] = get_title_lang("title_invoiceaddress");
			$data["Total"] = $getVoucherPostList["data"]["total"];
			$data["Pages"] = $this->ajaxPages($dataVoucherpostlist, $limit, $page, $getVoucherPostList["data"]["total"]);
			$data["Limit"] = $limit;
		}
		return $this->view("invoiceaddress", $data);
	}
	public function apps(\think\Request $request)
	{
		$param = $request->param();
		$page = $param["page"] >= 1 ? intval($param["page"]) : 1;
		$limit = $param["limit"] >= 1 ? intval($param["limit"]) : 20;
		$orderby = strval($param["orderby"]) ? strval($param["orderby"]) : "id";
		$sort = $param["sort"] ?? "DESC";
		$id = isset($param["id"]) ? intval($param["id"]) : 0;
		$action = isset($param["action"]) ? $param["action"] : "";
		$request->page = $page;
		$request->limit = $limit;
		$request->orderby = $orderby;
		$request->sort = $sort;
		$Developer = controller("Developer");
		if ($request->isPost()) {
			if (!empty($id) && $action == "del") {
				$result = $Developer->deleteDeveloperApp();
			} else {
				$result = $Developer->postDeveloperApp();
			}
			if ($result["status"] == 200) {
				$data["SuccessMsg"] = $result["msg"];
				header("location:{$this->ViewModel->domain}/apps");
				exit;
			} else {
				$data["ErrorMsg"] = $result["msg"];
			}
		}
		if ($action == "add" || $action == "edit") {
			$getDeveloperApp = $Developer->getDeveloperApp();
			$data["Apps"] = $getDeveloperApp["data"];
			$data["Title"] = get_title_lang("title_apps_edit");
		} else {
			$Developer->request->order = $orderby;
			$getDeveloperAppList = $Developer->getDeveloperAppList();
			$dataApps = $getDeveloperAppList["data"]["products"];
			$data["Developer"] = $getDeveloperAppList["data"]["developer"];
			$data["Currency"] = $getDeveloperAppList["data"]["currency"];
			$data["Apps"] = $dataApps;
			$data["Title"] = get_title_lang("title_apps");
			$data["Total"] = $getDeveloperAppList["data"]["count"];
			$data["Pages"] = $this->ajaxPages($dataApps, $limit, $page, $getDeveloperAppList["data"]["count"]);
			$data["Limit"] = $limit;
		}
		return $this->view("apps", $data);
	}
	public function appincome(\think\Request $request)
	{
		$Developer = controller("Developer");
		$getDeveloperAppIncome = $Developer->getDeveloperAppIncome();
		$dataAppincome = $getDeveloperAppIncome["data"];
		$data["AppIncome"] = $dataAppincome;
		$data["Title"] = get_title_lang("title_appincome");
		return $this->view("appincome", $data);
	}
	public function apptransaction(\think\Request $request)
	{
		$param = $request->param();
		$page = $param["page"] >= 1 ? intval($param["page"]) : 1;
		$limit = $param["limit"] >= 1 ? intval($param["limit"]) : 20;
		$orderby = strval($param["orderby"]) ? strval($param["orderby"]) : "id";
		$sort = $param["sort"] ?? "DESC";
		$request->page = $page;
		$request->limit = $limit;
		$request->orderby = $orderby;
		$request->sort = $sort;
		$Developer = controller("Developer");
		$Developer->request->order = $orderby;
		$getAppAccounts = $Developer->getAppAccounts();
		$dataApptransaction = $getAppAccounts["data"]["accounts"];
		$data["Currency"] = $getAppAccounts["data"]["currency"];
		$data["AppTransaction"] = $dataApptransaction;
		$data["Title"] = get_title_lang("title_apptransaction");
		$data["Total"] = $getAppAccounts["data"]["count"];
		$data["Pages"] = $this->ajaxPages($dataApptransaction, $limit, $page, $getAppAccounts["data"]["count"]);
		$data["Limit"] = $limit;
		return $this->view("apptransaction", $data);
	}
	public function applog(\think\Request $request)
	{
		$param = $request->param();
		$page = $param["page"] >= 1 ? intval($param["page"]) : 1;
		$limit = $param["limit"] >= 1 ? intval($param["limit"]) : 20;
		$orderby = strval($param["orderby"]) ? strval($param["orderby"]) : "id";
		$sort = $param["sort"] ?? "DESC";
		$request->page = $page;
		$request->limit = $limit;
		$request->orderby = $orderby;
		$request->sort = $sort;
		$Developer = controller("Developer");
		$Developer->request->order = $orderby;
		$getDeveloperAppLogs = $Developer->getDeveloperAppLogs();
		$dataApplog = $getDeveloperAppLogs["data"]["logs"];
		foreach ($dataApplog as &$value) {
			$value = ["id" => $value["id"], "create_time" => $value["create_time"], "desc" => $value["desc"], "name" => $value["name"], "username" => $value["username"]];
		}
		$data["AppLog"] = $dataApplog;
		$data["Title"] = get_title_lang("title_applog");
		$data["Total"] = $getDeveloperAppLogs["data"]["count"];
		$data["Pages"] = $this->ajaxPages($dataApplog, $limit, $page, $getDeveloperAppLogs["data"]["count"]);
		$data["Limit"] = $limit;
		return $this->view("applog", $data);
	}
	public function credit(\think\Request $request)
	{
		$param = $request->param();
		$page = $param["page"] >= 1 ? intval($param["page"]) : 1;
		$limit = $param["limit"] >= 1 ? intval($param["limit"]) : 20;
		$orderby = strval($param["orderby"]) ? strval($param["orderby"]) : "id";
		$sorting = $param["sorting"] ?? "DESC";
		$action = isset($param["action"]) ? $param["action"] : "";
		$request->page = $page;
		$request->limit = $limit;
		$request->orderby = $orderby;
		$request->sort = $sort;
		$CreditLimt = controller("CreditLimit");
		$index = $CreditLimt->index();
		$userInvoice = $CreditLimt->userInvoice();
		$data["Credit"] = $index["user"];
		$data["Invoices"] = $userInvoice["invoices"];
		$data["InvoiceStatus"] = $userInvoice["invoice_status"];
		$data["Title"] = get_title_lang("title_credit");
		$data["Total"] = $userInvoice["count"];
		$data["Pages"] = $this->ajaxPages($userInvoice["invoices"], $limit, $page, $userInvoice["count"]);
		$data["Limit"] = $limit;
		return $this->view("credit", $data);
	}
	public function creditdetail(\think\Request $request)
	{
		$param = $request->param();
		$action = isset($param["action"]) ? $param["action"] : "";
		$CreditLimt = controller("CreditLimit");
		if ($action == "used") {
			$index = $CreditLimt->creditLimitUsed();
			$data["CreditUsedDetail"] = $index["data"];
			$data["Title"] = get_title_lang("title_creditdetail_used");
		} else {
			$index = $CreditLimt->creditLimitInvoice();
			$data["CreditDetail"] = $index["data"];
			$prefix = $index["data"]["currency"]["prefix"];
			$suffix = $index["data"]["currency"]["suffix"];
			$invoiceid = $request->id;
			$invoice = \think\Db::name("invoices")->where("id", $invoiceid)->find();
			$status = $invoice["status"];
			if ($status == "Paid") {
				$data["Pay"]["PayStatus"] = "Paid";
			} else {
				$Pay = controller("Pay");
				$uid = $request->uid;
				$request->invoiceid = $invoiceid;
				$request->flag = true;
				if ($invoice["type"] == "recharge") {
					$data["Action"] = "recharge";
					$request->flag = false;
					$pay_data = $Pay->startPay($request);
					if ($pay_data["status"] == 200) {
						$data["Pay"] = $pay_data["data"];
					} else {
						$data["ErrorMsg"] = $pay_data["msg"];
					}
				} else {
					$data["Action"] = "billing";
					$client = \think\Db::name("clients")->field("credit,currency")->where("id", $uid)->find();
					$credit = $client["credit"];
					$subtotal = $invoice["subtotal"];
					if ($credit <= 0) {
						$request->flag = false;
						$pay_data = $Pay->startPay($request);
						if ($pay_data["status"] == 200) {
							$data["Pay"] = $pay_data["data"];
						} else {
							$data["ErrorMsg"] = $pay_data["msg"];
						}
					} else {
						$subtotal = $invoice["subtotal"];
						$credit_page = $Pay->useCreditPage();
						$credit_data = $credit_page["data"];
						$data["Pay"]["gateway_list"] = $credit_data["gateway_list"];
						$data["Pay"]["payment"] = $credit_data["payment"];
						$data["Pay"]["total"] = $credit_data["total"];
						$data["Pay"]["total_desc"] = $credit_data["currency"]["prefix"] . $credit_data["total"] . $credit_data["currency"]["suffix"];
						$data["Pay"]["credit"] = $credit_data["credit"];
						$data["Pay"]["invoiceid"] = $credit_data["invoiceid"];
						$data["Pay"]["deduction"] = $credit_data["deduction"];
						$pay_data = $Pay->startPay($request);
						if ($pay_data["status"] == 200) {
							$data["Pay"] = $pay_data["data"];
							$data["Pay"]["pay"] = true;
							$data["Pay"]["deduction"] = $credit;
							$data["Pay"]["use_credit"] = 1;
							if ($subtotal <= $credit) {
								$data["Pay"]["deduction"] = $subtotal;
								$data["Pay"]["credit_enough"] = 1;
								$data["Pay"]["total"] = $subtotal;
								$data["Pay"]["total_desc"] = $prefix . $subtotal . $suffix;
							} else {
								$data["Pay"]["deduction"] = $credit;
								$data["Pay"]["credit_enough"] = 0;
								$data["Pay"]["total"] = $subtotal;
								$data["Pay"]["total_desc"] = $prefix . bcsub($subtotal, $credit, 2) . $suffix;
							}
						} else {
							$data["ErrorMsg"] = $pay_data["msg"];
						}
					}
				}
			}
			$data["Title"] = get_title_lang("title_creditdetail");
		}
		return $this->view("creditdetail", $data);
	}
	public function service(\think\Request $request)
	{
		$param = $request->param();
		$page = $param["page"] >= 1 ? intval($param["page"]) : 1;
		$limit = $param["limit"] >= 1 ? intval($param["limit"]) : 20;
		$orderby = strval($param["orderby"]) ? strval($param["orderby"]) : "id";
		$sort = $param["sort"] ?? "DESC";
		$request->page = $page;
		$request->limit = $limit;
		$request->order = $orderby;
		$request->sort = $sort;
		$request->search = $param["keywords"];
		$nav_list = (new \app\common\logic\Menu())->getOneNavs("client", null);
		$nav_info = $nav_list[$param["groupid"]] ?? [];
		if (!getEdition()) {
			$nav_info["orderFuc"] = 0;
		}
		if (!file_exists(CMF_ROOT . "public/themes/clientarea/" . configuration("clientarea_default_themes") . "/" . $nav_info["templatePage"] . ".tpl")) {
			if (!file_exists(CMF_ROOT . "public/themes/clientarea/default/" . $nav_info["templatePage"] . ".tpl")) {
				$nav_info["templatePage"] = "service";
			}
		}
		$request->navRelid = explode(",", $nav_info["relid"] ?? "");
		$Host = controller("Host");
		$domain_status_empty = 0;
		if (!$request->param("domain_status")) {
			$domain_status_empty = 1;
			$request->domain_status = ["Pending", "Active", "Suspended"];
		}
		if ($nav_info["templatePage"] == "service_ssl") {
			if ($domain_status_empty) {
				$request->domain_status = ["Pending", "Active", "Verifiy_Active", "Overdue_Active", "Issue_Active", "Cancelled", "Deleted"];
			}
		}
		$request->templatePage = $nav_info["templatePage"];
		$getList = $Host->getList($request);
		foreach ($getList["data"]["list"] as $k => $v) {
			if (!empty($v["assignedips"])) {
				$dedicatedip = [];
				$dedicatedip[] = $v["dedicatedip"];
				$assignedips = array_merge($dedicatedip, $v["assignedips"]);
				$getList["data"]["list"][$k]["assignedips"] = array_unique($assignedips);
			}
		}
		$data["isSslPage"] = 0;
		if ($nav_info["templatePage"] == "service_ssl") {
			$getList["data"]["domainstatus"] = config("public.sslDomainStatus");
			unset($getList["data"]["domainstatus"]["Overdue_Active"]);
			unset($getList["data"]["domainstatus"]["Deleted"]);
			$data["isSslPage"] = 1;
			$data["certifi_log"] = \think\Db::name("certifi_log")->where("uid", $this->request->uid)->where("type", 1)->find();
			if ($data["certifi_log"]) {
				$data["certifi_log"]["lastname"] = mb_substr($data["certifi_log"]["certifi_name"], 0, 1);
				$data["certifi_log"]["firstname"] = mb_substr($data["certifi_log"]["certifi_name"], 1);
				$data["certifi_log"]["orgName"] = $data["certifi_log"]["company_name"] ?: "";
				$data["certifi_log"]["creditCode"] = $data["certifi_log"]["company_organ_code"] ?: "";
			}
			$data["iso_arr"] = \think\Db::name("sms_country")->field("iso,name_zh")->select()->toArray();
		}
		$data["Currency"] = $getList["data"]["page"];
		$data["Service"] = $getList["data"];
		$data["Title"] = (new \app\common\logic\Menu())->getOneNavs("client", $param["groupid"] ?: 0, "name") ?: get_title_lang("title_other_server");
		$data["Total"] = $getList["data"]["sum"];
		$data["Pages"] = $this->ajaxPages($getList["data"]["list"], $limit, $page, $getList["data"]["sum"]);
		$data["Limit"] = $limit;
		$data["nav_info"] = $nav_info;
		return $this->view($nav_info["templatePage"], $data);
	}
	public function servicedetail(\think\Request $request)
	{
		$param = $request->param();
		$request->host_id = $request->id;
		$request->hostid = $request->id;
		$action = $param["action"];
		$Host = controller("Host");
		$Upgrade = controller("Upgrade");
		$RecordLog = controller("RecordLog");
		$regdate = \think\Db::name("host")->where("id", $request->id)->value("regdate");
		$tpl = (new \app\common\logic\Contract())->getHostContract($request->id);
		if (!empty($tpl) && configuration("contract_open") == 1) {
			$has_contract = \think\Db::name("contract_pdf")->where("host_id", $request->id)->where("contract_id", $tpl["id"])->order("id", "desc")->find();
			if (!empty($has_contract)) {
				$data["ForceContract"]["has_contract"] = true;
			}
			$data["ForceContract"]["force"] = $tpl["force"];
			$data["ForceContract"]["base"] = $tpl["base"];
			$data["ForceContract"]["suspended_type"] = $tpl["suspended_type"];
			$data["ForceContract"]["suspended"] = $tpl["suspended"];
			if ($regdate + intval($tpl["suspended"]) * 24 * 3600 <= time()) {
				$data["ForceContract"]["regdated"] = true;
			} else {
				$data["ForceContract"]["regdated"] = false;
			}
		}
		$request->tplcloud = true;
		if ($action == "nat" && $request->isGet()) {
			$request->nat = true;
			$data["Detail"] = $Host->getHeader($request)["data"];
			echo $this->view("servicedetail/nat", $data, ["autoinclude" => false]);
			exit;
		}
		$getHeader = $Host->getHeader($request);
		if ($request->isPost()) {
			if ($action == "cancel") {
				$result = $Host->postCancel($request);
			} elseif ($action == "delete_cancel") {
				$result = $Host->deleteCancel();
			} elseif ($action == "upgrade") {
				$Upgrade->request->hid = $request->id;
				$result = $Upgrade->upgradeProductPost();
				if ($result["status"] == 200) {
					$getUpgradeProductPage = $Upgrade->getUpgradeProductPage();
					$data["Upgrade"] = $getUpgradeProductPage["data"];
				}
				if ($result["status"] == 200) {
					$data["SuccessMsg"] = $result["msg"];
				} else {
					$data["ErrorMsg"] = $result["msg"];
				}
				$data["Action"] = $action;
				echo $this->view("includes/upgrade", $data, ["autoinclude" => false]);
				exit;
			} elseif ($action == "upgrade_use_promo_code") {
				$Upgrade->request->hid = $request->id;
				$result = $Upgrade->addPromoToProduct();
				if ($result["status"] == 200) {
					$getUpgradeProductPage = $Upgrade->getUpgradeProductPage();
					$data["Upgrade"] = $getUpgradeProductPage["data"];
				}
				if ($result["status"] == 200) {
					$data["SuccessMsg"] = $result["msg"];
				} else {
					$data["ErrorMsg"] = $result["msg"];
				}
				$data["Action"] = $action;
				echo $this->view("includes/upgrade", $data, ["autoinclude" => false]);
				exit;
			} elseif ($action == "upgrade_remove_promo_code") {
				$Upgrade->request->hid = $request->id;
				$result = $Upgrade->RemovePromoFromProduct();
				if ($result["status"] == 200) {
					$getUpgradeProductPage = $Upgrade->getUpgradeProductPage();
					$data["Upgrade"] = $getUpgradeProductPage["data"];
				}
				if ($result["status"] == 200) {
					$data["SuccessMsg"] = $result["msg"];
				} else {
					$data["ErrorMsg"] = $result["msg"];
				}
				$data["Action"] = $action;
				echo $this->view("includes/upgrade", $data, ["autoinclude" => false]);
				exit;
			} elseif ($action == "renew") {
				$Host = controller("Host");
				$result = $Host->postRenew($request);
				if ($result["status"] == 200) {
					$invoiceid = $result["data"]["invoiceid"];
					if (!empty($invoiceid)) {
						$payment = $result["data"]["payment"];
						header("location:{$this->ViewModel->domain}/viewbilling?id={$invoiceid}&payment={$payment}");
						exit;
					}
				} else {
					$data["ErrorMsg"] = $result["msg"];
				}
				$data["Action"] = $action;
			} elseif ($action == "upgrade_config") {
				$Upgrade->request->hid = $request->id;
				$result = $Upgrade->upgradeConfigPost();
				if ($result["status"] == 200) {
					$getUpgradeProductPage = $Upgrade->getUpgradeConfigPage();
					$data["UpgradeConfig"] = $getUpgradeProductPage["data"];
				}
				if ($result["status"] == 200) {
					$data["SuccessMsg"] = $result["msg"];
				} else {
					$data["ErrorMsg"] = $result["msg"];
				}
				$data["Action"] = $action;
				echo $this->view("includes/upgradeoption", $data, ["autoinclude" => false]);
				exit;
			} elseif ($action == "upgrade_config_use_promo_code") {
				$Upgrade->request->hid = $request->id;
				$result = $Upgrade->addPromoCodeToConfig();
				if ($result["status"] == 200) {
					$getUpgradeProductPage = $Upgrade->getUpgradeConfigPage();
					$data["UpgradeConfig"] = $getUpgradeProductPage["data"];
				}
				if ($result["status"] == 200) {
					$data["SuccessMsg"] = $result["msg"];
				} else {
					$data["ErrorMsg"] = $result["msg"];
				}
				$data["Action"] = $action;
				echo $this->view("includes/upgradeoption", $data, ["autoinclude" => false]);
				exit;
			} elseif ($action == "upgrade_config_remove_promo_code") {
				$Upgrade->request->hid = $request->id;
				$result = $Upgrade->removePromoCodeFromConfig();
				if ($result["status"] == 200) {
					$getUpgradeProductPage = $Upgrade->getUpgradeConfigPage();
					$data["UpgradeConfig"] = $getUpgradeProductPage["data"];
				}
				if ($result["status"] == 200) {
					$data["SuccessMsg"] = $result["msg"];
				} else {
					$data["ErrorMsg"] = $result["msg"];
				}
				$data["Action"] = $action;
				echo $this->view("includes/upgradeoption", $data, ["autoinclude" => false]);
				exit;
			}
			if ($result["status"] == 200) {
				$data["SuccessMsg"] = $result["msg"];
			} else {
				$data["ErrorMsg"] = $result["msg"];
			}
		}
		$getHeader["data"]["host_data"]["format_nextduedate"] = format_nextduedate($getHeader["data"]["host_data"]["nextduedate"]);
		$tpls = ["hostingaccount" => "hosting", "server" => "dedicated", "cloud" => "cloud", "dcimcloud" => "zjmfcloud", "dcim" => "zjmfdcim", "software" => "software", "cdn" => "cdn", "other" => "general"];
		if (!empty($action) && $request->isGet()) {
			$product_tpl = $tpls[$getHeader["data"]["host_data"]["type"]];
			$page = $param["page"] >= 1 ? intval($param["page"]) : 1;
			$limit = $param["limit"] >= 1 ? intval($param["limit"]) : 20;
			if ($action == "log_page") {
				$orderby = strval($param["orderby"]) ? strval($param["orderby"]) : "id";
				$sort = $param["sort"] ?? "DESC";
				$getUserLogDcs = $RecordLog->getUserLogDcs($request);
				$data["RecordLog"] = $getUserLogDcs["data"]["log_list"];
				$data["Total"] = $getUserLogDcs["data"]["count"];
				$data["Pages"] = $this->ajaxPages($getUserLogDcs["data"]["log_list"], $limit, $page, $getUserLogDcs["data"]["count"]);
				$data["Limit"] = $limit;
				echo $this->view("servicedetail/servicedetail-log", $data, ["autoinclude" => false]);
				exit;
			} elseif ($action == "billing_page") {
				$orderby = strval($param["orderby"]) ? strval($param["orderby"]) : "id";
				$sort = $param["sort"] ?? "DESC";
				$getHostRecharge = $Host->getHostRecharge();
				$data["HostRecharge"] = $getHostRecharge["data"]["invoices"];
				$data["Currency"] = $getHostRecharge["data"]["currency"];
				$data["Total"] = $getHostRecharge["data"]["count"];
				$data["Pages"] = $this->ajaxPages($getHostRecharge["data"]["invoices"], $limit, $page, $getHostRecharge["data"]["count"]);
				$data["Limit"] = $limit;
				echo $this->view("servicedetail/servicedetail-billing", $data, ["autoinclude" => false]);
				exit;
			} elseif ($action == "upgrade_page") {
				$Host->request->hid = $request->id;
				$upgradeProduct = $Upgrade->upgradeProduct();
				$data["UpgradeProduct"] = $upgradeProduct["data"];
				$data["Action"] = $action;
				echo $this->view("includes/upgrade", $data, ["autoinclude" => false]);
				exit;
			} elseif ($action == "upgrade_configoption_page") {
				$Host->request->hid = $request->id;
				$upgradeConfig = $Upgrade->index();
				$data["UpgradeConfig"] = $upgradeConfig["data"];
				$data["Action"] = $action;
				echo $this->view("includes/upgradeoption", $data, ["autoinclude" => false]);
				exit;
			} elseif ($action == "renew") {
				$getRenewPage = $Host->getRenewPageView($request);
				$data["Renew"] = $getRenewPage["data"];
				echo $this->view("includes/renew", $data, ["autoinclude" => false]);
				exit;
			} elseif ($action == "flowpacket") {
				$page = $param["page"] >= 1 ? intval($param["page"]) : 1;
				$limit = 10;
				$flowpacket = $getHeader["data"]["dcim"]["flowpacket"];
				$data["Flowpacket"] = array_slice($flowpacket, ($page - 1) * $limit, $limit);
				$data["Total"] = count($flowpacket);
				$data["Pages"] = $this->ajaxPages($data["Flowpacket"], $limit, $page, $data["Total"]);
				$data["Limit"] = $limit;
				echo $this->view("includes/orderflow", $data, ["autoinclude" => false]);
				exit;
			}
		}
		if ($getHeader["status"] != 200) {
			header("Location: /service");
			exit;
		}
		$getCancel = $Host->getCancel($request);
		$Host->request->hostid = $request->id;
		$getRenewPage = $Host->getRenewPage($request);
		if ($getHeader["data"]["host_data"]["type"] == "dcim" && $getHeader["data"]["host_data"]["dcim"]["auth"]["traffic"] == "on") {
		}
		if ($getHeader["data"]["second"]["second_verify"] == 1) {
			$User = controller("User");
			$getSecondVerifyPage = $User->getSecondVerifyPage();
			$data["SecondVerify"] = $getSecondVerifyPage["data"];
		}
		$getHeader["data"]["host_data"]["remark"] = htmlspecialchars($getHeader["data"]["host_data"]["remark"]);
		if ($getHeader["data"]["host_cancel"]["type"] == "Immediate") {
			$cron_day_start_time = configuration("cron_day_start_time");
			$getHeader["data"]["host_data"]["deletedate"] = strtotime(date("Y-m-d 00:00:00")) + $cron_day_start_time * 3600;
		} elseif ($getHeader["data"]["host_cancel"]["type"] == "Endofbilling") {
			if (configuration("cron_host_terminate") == 1) {
				if (configuration("cron_host_terminate_high") == 1) {
					$cron_host_terminate_time = configuration("cron_host_terminate_time_" . $getHeader["data"]["host_data"]["type"]);
				} else {
					$cron_host_terminate_time = configuration("cron_host_terminate_time");
				}
				$getHeader["data"]["host_data"]["deletedate"] = $getHeader["data"]["host_data"]["nextduedate"] + $cron_host_terminate_time * 24 * 3600;
			}
		}
		if (!empty($getHeader["data"]["host_data"]["assignedips"])) {
			$dedicatedip = [];
			$dedicatedip[] = $getHeader["data"]["host_data"]["dedicatedip"];
			$assignedips = array_merge($dedicatedip, $getHeader["data"]["host_data"]["assignedips"]);
			$getHeader["data"]["host_data"]["assignedips"] = array_unique($assignedips);
			if (count($getHeader["data"]["host_data"]["assignedips"]) == 1 && $getHeader["data"]["host_data"]["assignedips"][0] == $dedicatedip[0]) {
				$getHeader["data"]["host_data"]["assignedips"] = [];
			}
			$getHeader["data"]["host_data"]["ip_num"] = count($getHeader["data"]["host_data"]["assignedips"]);
		}
		$data["Detail"] = $getHeader["data"];
		$data["Cancel"] = $getCancel["data"];
		$data["Renew"] = $getRenewPage["data"];
		$data["Title"] = get_title_lang("title_servicedetail");
		return $this->view("servicedetail", $data);
	}
	public function mulitrenew(\think\Request $request)
	{
		$Host = controller("Host");
		$mulitrenew_data = $Host->postBatchRenewPage($request);
		$data["MultiRenew"] = $mulitrenew_data["data"];
		$param = $request->param();
		$action = $param["action"] ?: "";
		if ($action == "batchrenew") {
			$batch_renew = $Host->postBatchRenew();
			if ($batch_renew["status"] == 1001 || $batch_renew["status"] == 200) {
				$invoiceid = $batch_renew["data"]["invoice_id"];
				$payment = $batch_renew["data"]["payment"];
				$data["SuccessMsg"] = $batch_renew["msg"];
				header("location:{$this->ViewModel->domain}/viewbilling?id={$invoiceid}&payment={$payment}");
				exit;
			} else {
				$data["ErrorMsg"] = $batch_renew["msg"];
			}
		}
		$data["Title"] = get_title_lang("title_mulitrenew");
		return $this->view("mulitrenew", $data);
	}
	public function pay(\think\Request $request)
	{
		$param = $request->param();
		$action = $param["action"];
		$Pay = controller("Pay");
		$data = [];
		if ($action == "recharge") {
			$result = $Pay->recharge($request);
			if ($result["status"] == 200) {
				$data["SuccessMsg"] = $result["msg"];
				$invoiceid = $result["data"]["invoice_id"];
				$request->invoiceid = $invoiceid;
				$pay_data = $Pay->startPay($request);
				if ($pay_data["status"] == 200) {
					$data["Pay"] = $pay_data["data"];
					$data["Action"] = "recharge";
					echo $this->view("includes/pay", $data, ["autoinclude" => false]);
					exit;
				} else {
					if ($param["beforeCheck"]) {
						return jsons($pay_data);
					}
					$data["ErrorMsg"] = $pay_data["msg"];
				}
			} else {
				if ($param["beforeCheck"]) {
					return jsons($result);
				}
				$data["ErrorMsg"] = $result["msg"];
			}
		} elseif ($action == "billing") {
			$uid = $request->uid;
			$invoiceid = $request->invoiceid;
			$invoice = \think\Db::name("invoices")->where("id", $invoiceid)->find();
			if (empty($invoice["id"])) {
				$data["ErrorMsg"] = "账单ID不存在!";
				echo $this->view("includes/pay", $data, ["autoinclude" => false]);
				exit;
			}
			if ($invoice["type"] != "recharge") {
				$user_credit = \think\Db::name("clients")->where("id", $invoice["uid"])->value("credit");
				if ($user_credit > 0) {
					if ($user_credit > 0 && $invoice["subtotal"] == $invoice["total"] && $param["use_credit"] == 1) {
						$paid_invoice_credit = $user_credit + $invoice["credit"] + $invoice["subtotal"] - $invoice["total"];
						$paid_invoice_total = bcsub($invoice["subtotal"], $paid_invoice_credit, 2);
						$invoice["total"] = $paid_invoice_total;
					} else {
						if ($param["use_credit"] == 0 && isset($param["use_credit"])) {
							$invoice["total"] = $invoice["subtotal"];
						}
					}
				}
				hook("check_divert_invoice", ["invoice_id" => $invoiceid]);
				$newinvoiceid = $Pay->invoicesidCreateTmp($invoice);
				try {
					if (!empty($newinvoiceid) && $newinvoiceid != $invoiceid) {
						\think\Db::name("invoice_items")->where("invoice_id", $invoiceid)->update(["invoice_id" => $newinvoiceid]);
						\think\Db::name("orders")->where("invoiceid", $invoiceid)->update(["invoiceid" => $newinvoiceid]);
						\think\Db::name("invoices")->where("id", $invoiceid)->update(["id" => $newinvoiceid]);
						hook("product_divert_upgrade", ["id" => $invoiceid, "new_id" => $newinvoiceid]);
						$invoice["id"] = $newinvoiceid;
						$invoiceid = $newinvoiceid;
					}
				} catch (\Exception $exception) {
					$data["ErrorMsg"] = $exception->getMessage();
				}
				$request->invoiceid = $invoiceid;
				if ($invoice["subtotal"] != $invoice["total"]) {
					$invoice["total"] = $invoice["subtotal"];
					\think\Db::name("invoices")->where("id", $invoiceid)->update(["total" => $invoice["subtotal"]]);
				}
			}
			$data["ReturnUrl"] = $invoice["url"] ?: "";
			$request->id = $invoiceid;
			$UserInvoice = controller("UserInvoice");
			$getInvoicesDetail = $UserInvoice->getInvoicesDetail();
			$data["ViewBilling"] = $getInvoicesDetail["data"];
			if ("Paid" == $data["ViewBilling"]["detail"]["status"]) {
				$data["Pay"]["PayStatus"] = "Paid";
				echo $this->view("includes/pay", $data, ["autoinclude" => false]);
				exit;
			}
			if ($invoice["type"] == "recharge") {
				$data["Action"] = "recharge";
				$pay_data = $Pay->startPay($request);
				if ($pay_data["status"] == 200) {
					$data["Pay"] = $pay_data["data"];
					echo $this->view("includes/pay", $data, ["autoinclude" => false]);
					exit;
				} else {
					$data["ErrorMsg"] = $pay_data["msg"];
				}
			} else {
				$client = \think\Db::name("clients")->field("credit,credit_limit,is_open_credit_limit,currency")->where("id", $uid)->find();
				$credit = $client["credit"];
				$subtotal = $invoice["subtotal"];
				$total = $invoice["total"];
				if ($invoice["type"] != "credit_limit") {
					$data["Action"] = "billing";
					$client["is_open_credit_limit"] = configuration("credit_limit") == 1 ? $client["is_open_credit_limit"] : 0;
					$client["amount_to_be_settled"] = \think\Db::name("invoices")->where("status", "Paid")->where("use_credit_limit", 1)->where("invoice_id", 0)->where("is_delete", 0)->where("uid", $uid)->sum("total");
					$unpaid = \think\Db::name("invoices")->where("type", "credit_limit")->where("status", "Unpaid")->where("is_delete", 0)->where("uid", $uid)->sum("total");
					$client["credit_limit_used"] = round($client["amount_to_be_settled"] + $unpaid, 2);
					$client["credit_limit_balance"] = round($client["credit_limit"] - $client["credit_limit_used"] > 0 ? $client["credit_limit"] - $client["credit_limit_used"] : 0, 2);
					$paymt["is_open_credit_limit"] = $client["is_open_credit_limit"];
					$paymt["credit_limit_balance"] = $client["credit_limit_balance"];
					$paymt["subtotal"] = $subtotal;
					$paymt["is_open_shd_credit_limit"] = configuration("shd_credit_limit");
					$data["paymt"] = $paymt;
					if (!empty($client["is_open_credit_limit"]) && $subtotal <= $client["credit_limit_balance"] && 1 == $invoice["use_credit_limit"]) {
						if (!is_null($param["use_credit_limit"])) {
							if ($param["use_credit_limit"] == 1) {
								$data["Pay"]["use_credit_limit"] = 1;
								$apply_limit_credit = $Pay->applyCreditLimit($request);
								if ($apply_limit_credit["status"] == 1001) {
									$data["Pay"]["PayStatus"] = "Paid";
								} else {
									$data["ErrorMsg"] = $apply_limit_credit["msg"];
								}
								echo $this->view("includes/pay", $data, ["autoinclude" => false]);
								exit;
							}
						} else {
							$currency = \think\Db::name("currencies")->field("prefix,suffix")->where("id", $client["currency"])->find();
							$data["Pay"]["use_credit_limit"] = 1;
							$data["Pay"]["total"] = $subtotal;
							$data["Pay"]["total_desc"] = $currency["prefix"] . $subtotal . $currency["suffix"];
							$data["Pay"]["deduction"] = $subtotal;
							$data["Pay"]["credit_limit_balance"] = $client["credit_limit_balance"];
							$data["Pay"]["invoiceid"] = $invoiceid;
							echo $this->view("includes/pay", $data, ["autoinclude" => false]);
							exit;
						}
					}
				}
				if ($subtotal == $total) {
					if ($credit <= 0) {
						$pay_data = $Pay->startPay($request);
						if ($pay_data["status"] == 200) {
							$Pay->invoicesidTmp($invoiceid, $invoiceid, $pay_data["data"]["total"]);
							$data["Pay"] = $pay_data["data"];
							echo $this->view("includes/pay", $data, ["autoinclude" => false]);
							exit;
						} else {
							$data["ErrorMsg"] = $pay_data["msg"];
						}
					} else {
						$credit_page = $Pay->useCreditPage();
						$credit_data = $credit_page["data"];
						$data["Pay"]["gateway_list"] = $credit_data["gateway_list"];
						$data["Pay"]["payment"] = $param["payment"] ?: $credit_data["payment"];
						$data["Pay"]["total"] = $credit_data["total"];
						$data["Pay"]["total_desc"] = $credit_data["currency"]["prefix"] . $credit_data["total"] . $credit_data["currency"]["suffix"];
						$data["Pay"]["credit"] = $credit_data["credit"];
						$data["Pay"]["invoiceid"] = $credit_data["invoiceid"];
						$data["Pay"]["deduction"] = $credit_data["deduction"];
						if ($subtotal <= $credit) {
							if (!is_null($param["use_credit"])) {
								if ($param["use_credit"] == 1) {
									$data["Pay"]["use_credit"] = 1;
									if ($param["pay"]) {
										$apply_credit = $Pay->applyCredit($request);
										if ($apply_credit["status"] == 1001) {
											$data["Pay"]["PayStatus"] = "Paid";
											$data["Pay"]["credit_enough"] = 1;
											echo $this->view("includes/pay", $data, ["autoinclude" => false]);
											exit;
										} else {
											$data["ErrorMsg"] = $apply_credit["msg"];
										}
									} else {
										$data["Pay"]["credit_enough"] = 1;
										echo $this->view("includes/pay", $data, ["autoinclude" => false]);
										exit;
									}
								} else {
									$pay_data = $Pay->startPay($request);
									if ($pay_data["status"] == 200) {
										$Pay->invoicesidTmp($invoiceid, $invoiceid, $pay_data["data"]["total"]);
										$data["Pay"] = $pay_data["data"];
										$data["Pay"]["deduction"] = "0.00";
										$data["Pay"]["credit_enough"] = 1;
										$data["Pay"]["use_credit"] = 0;
										echo $this->view("includes/pay", $data, ["autoinclude" => false]);
										exit;
									} else {
										$data["ErrorMsg"] = $pay_data["msg"];
									}
								}
							} else {
								$data["Pay"]["use_credit"] = 1;
								$data["Pay"]["credit_enough"] = 1;
								echo $this->view("includes/pay", $data, ["autoinclude" => false]);
								exit;
							}
						} else {
							if (!is_null($param["use_credit"])) {
								$apply_credit = $Pay->applyCredit($request);
								if ($apply_credit["status"] == 200) {
									$pay_data = $Pay->startPay($request);
									if ($pay_data["status"] == 200) {
										$Pay->invoicesidTmp($invoiceid, $invoiceid, $pay_data["data"]["total"]);
										$data["Pay"] = $pay_data["data"];
										$data["Pay"]["deduction"] = $credit;
										$data["Pay"]["credit_enough"] = 0;
										if ($param["use_credit"] == 1) {
											$data["Pay"]["use_credit"] = 1;
										} else {
											$data["Pay"]["use_credit"] = 0;
										}
										echo $this->view("includes/pay", $data, ["autoinclude" => false]);
										exit;
									} else {
										$data["ErrorMsg"] = $pay_data["msg"];
									}
								} else {
									$data["ErrorMsg"] = $apply_credit["msg"];
								}
							} else {
								$request->use_credit = 1;
								$apply_credit = $Pay->applyCredit($request);
								if ($apply_credit["status"] == 200) {
									$pay_data = $Pay->startPay($request);
									if ($pay_data["status"] == 200) {
										$Pay->invoicesidTmp($invoiceid, $invoiceid, $pay_data["data"]["total"]);
										$data["Pay"] = $pay_data["data"];
										$data["Pay"]["deduction"] = $credit;
										$data["Pay"]["use_credit"] = 1;
										$data["Pay"]["credit_enough"] = 0;
										echo $this->view("includes/pay", $data, ["autoinclude" => false]);
										exit;
									} else {
										$data["ErrorMsg"] = $pay_data["msg"];
									}
								} else {
									$data["ErrorMsg"] = $apply_credit["msg"];
								}
							}
						}
					}
				} else {
					if ($credit <= 0) {
						\think\Db::name("invoices")->where("id", $invoiceid)->update(["total" => $subtotal]);
						$pay_data = $Pay->startPay($request);
						if ($pay_data["status"] == 200) {
							$Pay->invoicesidTmp($invoiceid, $invoiceid, $pay_data["data"]["total"]);
							$data["Pay"] = $pay_data["data"];
							echo $this->view("includes/pay", $data, ["autoinclude" => false]);
							exit;
						} else {
							$data["ErrorMsg"] = $pay_data["msg"];
						}
					} else {
						$credit_page = $Pay->useCreditPage();
						$credit_data = $credit_page["data"];
						$data["Pay"]["gateway_list"] = $credit_data["gateway_list"];
						$data["Pay"]["payment"] = $param["payment"] ?: $credit_data["payment"];
						$data["Pay"]["total"] = $credit_data["total"];
						$data["Pay"]["total_desc"] = $credit_data["currency"]["prefix"] . $credit_data["total"] . $credit_data["currency"]["suffix"];
						$data["Pay"]["credit"] = $credit_data["credit"];
						$data["Pay"]["invoiceid"] = $credit_data["invoiceid"];
						$data["Pay"]["deduction"] = $credit_data["deduction"];
						if ($subtotal <= $credit) {
							if (!is_null($param["use_credit"])) {
								if ($param["use_credit"] == 1) {
									\think\Db::name("invoices")->where("id", $invoiceid)->update(["total" => 0]);
									$data["Pay"]["use_credit"] = 1;
									if ($param["pay"]) {
										$apply_credit = $Pay->applyCredit($request);
										if ($apply_credit["status"] == 1001) {
											$data["Pay"]["PayStatus"] = "Paid";
											$data["Pay"]["credit_enough"] = 1;
											echo $this->view("includes/pay", $data, ["autoinclude" => false]);
											exit;
										} else {
											$data["ErrorMsg"] = $apply_credit["msg"];
										}
									} else {
										echo $this->view("includes/pay", $data, ["autoinclude" => false]);
										exit;
									}
								} else {
									\think\Db::name("invoices")->where("id", $invoiceid)->update(["total" => $subtotal]);
									$data["Pay"]["use_credit"] = 0;
									$pay_data = $Pay->startPay($request);
									$Pay->invoicesidTmp($invoiceid, $invoiceid, $pay_data["data"]["total"]);
									$data["Pay"] = $pay_data["data"];
									$data["Pay"]["deduction"] = "0.00";
									$data["Pay"]["credit_enough"] = 1;
									echo $this->view("includes/pay", $data, ["autoinclude" => false]);
									exit;
								}
							} else {
								$data["Pay"]["use_credit"] = 1;
								$data["Pay"]["credit_enough"] = 1;
								\think\Db::name("invoices")->where("id", $invoiceid)->update(["total" => 0]);
								$data["Pay"]["total_desc"] = "￥" . $subtotal . "元";
								echo $this->view("includes/pay", $data, ["autoinclude" => false]);
								exit;
							}
						} else {
							if (!is_null($param["use_credit"])) {
								\think\Db::name("invoices")->where("id", $invoiceid)->update(["total" => $subtotal]);
								$apply_credit = $Pay->applyCredit($request);
								if ($apply_credit["status"] == 200) {
									$pay_data = $Pay->startPay($request);
									if ($pay_data["status"] == 200) {
										$Pay->invoicesidTmp($invoiceid, $invoiceid, $pay_data["data"]["total"]);
										$data["Pay"] = $pay_data["data"];
										$data["Pay"]["credit_enough"] = 0;
										if ($param["use_credit"] == 1) {
											$data["Pay"]["deduction"] = $credit;
											$data["Pay"]["use_credit"] = 1;
										} else {
											$data["Pay"]["deduction"] = "0.00";
											$data["Pay"]["use_credit"] = 0;
										}
										echo $this->view("includes/pay", $data, ["autoinclude" => false]);
										exit;
									} else {
										$data["ErrorMsg"] = $pay_data["msg"];
									}
								} else {
									$data["ErrorMsg"] = $apply_credit["msg"];
								}
							} else {
								\think\Db::name("invoices")->where("id", $invoiceid)->update(["total" => $subtotal - $credit]);
								$pay_data = $Pay->startPay($request);
								if ($pay_data["status"] == 200) {
									$Pay->invoicesidTmp($invoiceid, $invoiceid, $pay_data["data"]["total"]);
									$data["Pay"] = $pay_data["data"];
									$data["Pay"]["deduction"] = $credit;
									$data["Pay"]["use_credit"] = 1;
									$data["Pay"]["credit_enough"] = 0;
									echo $this->view("includes/pay", $data, ["autoinclude" => false]);
									exit;
								} else {
									$data["ErrorMsg"] = $pay_data["msg"];
								}
							}
						}
					}
				}
			}
		}
		if (empty($request->use_credit) && empty($request->use_credit_limit)) {
			if (empty($data["Pay"]["pay_html"]["type"])) {
				$data["Pay"]["pay_html"]["type"] = "html";
			}
			if (empty($data["Pay"]["pay_html"]["data"])) {
				$data["Pay"]["pay_html"]["data"] = $data["ErrorMsg"];
			}
		}
		echo $this->view("includes/pay", $data, ["autoinclude" => false]);
		exit;
	}
	public function verify()
	{
		$login = controller("Login");
		$res = $login->verify();
		if ($res["status"] == 400) {
			$data["ErrorMsg"] = $res["msg"];
			echo $this->view("includes/verify", $data);
			exit;
		} else {
			$data["Captcha"] = $res;
			return $res;
		}
	}
	public function apiManage()
	{
		$api = controller("ZjmfFinanceApi");
		$result = $api->summary();
		if ($result["status"] == 200) {
			$data["API"] = $result["data"];
			$data["api_open"] = $result["data"]["client"]["api_open"];
		} else {
			$data["api_open"] = 0;
			$data["server_clause_url"] = configuration("server_clause_url") ?: "";
		}
		$uid = request()->uid;
		$client = \think\Db::name("clients")->where("id", $uid)->find();
		if (configuration("allow_resource_api_phone") && empty($client["phonenumber"])) {
			$data["need_bind_phone"] = 1;
		} else {
			$data["need_bind_phone"] = 0;
		}
		if (configuration("allow_resource_api_realname") && !checkCertify($uid)) {
			$data["need_certify"] = 1;
		} else {
			$data["need_certify"] = 0;
		}
		$data["api_open_total"] = intval(configuration("allow_resource_api"));
		$data["Title"] = get_title_lang("title_APIManage");
		return $this->view("/apimanage", $data);
	}
	public function contractHost(\think\Request $request)
	{
		$param = $request->param();
		$page = !empty($param["page"]) ? intval($param["page"]) : 1;
		$limit = !empty($param["limit"]) ? intval($param["limit"]) : 10;
		$data = [];
		$contract = Controller("Contract");
		$res = $contract->host($request);
		$contract_host = $res["data"] ?: [];
		$ClientsModel = new \app\home\model\ClientsModel();
		$client_data = $ClientsModel->getClientByField("id", $request->uid, "companyname,address1,phonenumber,email");
		$ClientsModel->replaceClientName($request->uid, $client_data);
		$data["data"]["client_data"] = $client_data;
		$data["pageInfo"] = $contract_host;
		$data["pageInfo"]["pages"] = $this->ajaxPages($contract_host["hosts"], $limit, $page, $contract_host["count"]);
		$data["Title"] = get_title_lang("apply_contract");
		return $this->view("/contracthost", $data);
	}
	public function contract(\think\Request $request)
	{
		$contract = Controller("Contract");
		$this->page_data_register($request);
		$data = $contract->contractList();
		$_data = $data["data"]["lists"];
		$_count = $data["data"]["total"];
		$page_data = $this->page_data_rendering($request, $_data, $_count);
		$ClientsModel = new \app\home\model\ClientsModel();
		$client_data = $ClientsModel->getClientByField("id", $request->uid, "companyname,address1,phonenumber,email,username");
		$ClientsModel->replaceClientName($request->uid, $client_data);
		$address_client_data = $contract->postPage();
		$data["page_data"] = $page_data;
		$data["Title"] = get_title_lang("manage_contract");
		$data["data"]["client_data"] = $client_data;
		$data["data"]["address_client_data"] = $address_client_data["data"];
		return $this->view("/contract", $data);
	}
	private function page_data_register(\think\Request $request)
	{
		$param = $request->param();
		$page = !empty($param["page"]) ? intval($param["page"]) : 1;
		$limit = !empty($param["limit"]) ? intval($param["limit"]) : 10;
		$order = !empty($param["order"]) ? trim($param["order"]) : "a.create_time";
		$sort = !empty($param["sort"]) ? trim($param["sort"]) : "desc";
		$request->page = $page;
		$request->limit = $limit;
		$request->order = $order;
		$request->sort = $sort;
		return true;
	}
	private function page_data_rendering(\think\Request $request, $data, $count)
	{
		$param = $request->param();
		$page = $param["page"] >= 1 ? intval($param["page"]) : 1;
		$limit = $param["limit"] >= 1 ? intval($param["limit"]) : 20;
		$page_data["Pages"] = $this->ajaxPages($data, $limit, $page, $count);
		$page_data["Limit"] = $limit;
		$page_data["Count"] = $count;
		return $page_data;
	}
}