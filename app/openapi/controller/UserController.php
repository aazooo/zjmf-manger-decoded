<?php

namespace app\openapi\controller;

/**
 * @title 会员基础资料
 * @description 接口说明
 */
class UserController extends \cmf\controller\HomeBaseController
{
	private $imagesave = "../public/upload/home/certification/image/";
	private $getimage;
	private $type;
	public function initialize()
	{
		parent::initialize();
		$this->getimage = $this->request->host() . "/upload/home/certification/image/";
		$this->type = 1;
		$_action = ["loginNotice", "phoneBind", "emailBind"];
		$action = request()->action();
		if (in_array($action, $_action) && request()->id) {
			if ($action == "repassword") {
				$action = "crack_pass";
			}
			$client = \think\Db::name("clients")->field("phone_code,phonenumber,email,second_verify")->where("id", request()->uid)->find();
			$second_verify_action_home = explode(",", configuration("second_verify_action_home"));
			if (in_array($action, $second_verify_action_home)) {
				$second_verify_action = 1;
			} else {
				$second_verify_action = 0;
			}
			$second_verify_home = configuration("second_verify_home");
			$verification_success_1 = \think\facade\Cache::get("verification_success" . $client["phone_code"] . $client["phonenumber"]);
			$verification_success_2 = \think\facade\Cache::get("verification_success" . $client["email"]);
			if ($client["second_verify"] == 1 && !empty($second_verify_home) && !empty($second_verify_action)) {
				if (empty($verification_success_1) && empty($verification_success_2)) {
					$type = explode(",", configuration("second_verify_action_home_type"));
					$all_type = config("second_verify_action_home_type");
					$second_verify = [];
					foreach ($all_type as $v) {
						foreach ($type as $vv) {
							if ($vv == $v["name"]) {
								if ($v["name"] == "email") {
									$v["account"] = !empty($client["email"]) ? str_replace(substr($client["email"], 3, 4), "****", $client["email"]) : "未绑定邮箱";
								} elseif ($v["name"] == "phone") {
									$v["account"] = !empty($client["phonenumber"]) ? str_replace(substr($client["phonenumber"], 3, 4), "****", $client["phonenumber"]) : "未绑定手机";
								}
								$second_verify[] = $v;
							}
						}
					}
					$result["second_verify"] = $second_verify;
					echo json_encode(["status" => 400, "data" => $result, "msg" => "This operation requires secondary verification"]);
					exit;
				}
			}
			$is_certifi = \think\Db::name("host")->alias("a")->leftJoin("products b", "a.productid=b.id")->leftJoin("dcim_servers c", "a.serverid=c.serverid")->where("a.uid", request()->uid)->where("a.id", intval(request()->id))->value("c.is_certifi");
			$is_certifi = json_decode($is_certifi, true) ?: [];
			if (!empty($is_certifi)) {
				if ($is_certifi[$action] == 1 && !checkCertify(request()->uid)) {
					echo json_encode(["status" => 400, "msg" => lang("DCIM_CHECK_CERTIFY_ERROR")]);
					exit;
				}
			}
		}
	}
	/**
	 * @title 获取会员基础资料
	 * @description 接口说明:获取会员基础资料
	 * @author xiong
	 * @url v1/user
	 * @method GET
	 */
	public function userPage()
	{
		$data["client"] = \think\Db::name("clients")->field("phone_code,phonenumber as phone,email,qq,username,companyname,country,province,city,region,address1 as address,defaultgateway,marketing_emails_opt_in,credit")->where("id", $this->request->uid)->find();
		$country = config("country.country");
		foreach ($country as $v) {
			$data["country"][] = $v["name"];
		}
		return json(["status" => 200, "data" => $data]);
	}
	/**
	 * @title 修改会员资料
	 * @description 修改会员资料
	 * return
	 * @author xiong
	 * @url /user
	 * @method POST
	 */
	public function user()
	{
		if (!$this->request->isPost()) {
			return json(["status" => 400, "msg" => "illegal parameter"]);
		}
		$client = \think\Db::name("clients")->field("phone_code,phonenumber as phone,email,qq,username,companyname,country,province,city,region,address1 as address,defaultgateway,marketing_emails_opt_in,credit")->where("id", $this->request->uid)->find();
		$data = $this->request->param();
		if (!empty($data["qq"]) && !is_numeric($data["qq"])) {
			return json(["status" => 400, "msg" => "QQ numbers can only be numbers"]);
		}
		if (!empty($data["marketing_emails_opt_in"]) && ($data["marketing_emails_opt_in"] != 0 || $data["marketing_emails_opt_in"] != 1)) {
			return json(["status" => 400, "msg" => "Accept marketing messages can only be 1 or 0"]);
		}
		$gateway = array_column(gateway_list(), "name", "name");
		if (!empty($data["defaultgateway"]) && !in_array($data["defaultgateway"], $gateway)) {
			return json(["status" => 400, "msg" => "wrong payment method"]);
		}
		$country = array_column(config("country.country"), "name", "name");
		if (!empty($data["country"]) && !in_array($data["country"], $country)) {
			return json(["status" => 400, "msg" => "country error"]);
		}
		$data_clients = ["qq" => $data["qq"] ?: $client["qq"], "username" => $data["username"] ?: $client["username"], "companyname" => $data["companyname"] ?: $client["companyname"], "country" => $data["country"] ?: $client["country"], "province" => $data["province"] ?: $client["province"], "city" => $data["city"] ?: $client["city"], "region" => $data["region"] ?: $client["region"], "address1" => $data["address"] ?: $client["address"], "defaultgateway" => $data["defaultgateway"] ?: $client["defaultgateway"], "marketing_emails_opt_in" => intval($data["marketing_emails_opt_in"]) ?: $client["marketing_emails_opt_in"]];
		\think\Db::name("clients")->where("id", $this->request->uid)->update($data_clients);
		return json(["status" => 200, "msg" => "Modify member information successfully"]);
	}
	public function securityinfo()
	{
		if ($this->zjmf_authorize()) {
			$plugins = [];
			$list = \think\Db::name("plugin")->where(["module" => "oauth", "status" => 1])->order("order", "asc")->select();
			$clients_oauth = \think\Db::name("clients_oauth")->where(["uid" => $this->request->uid])->select()->toArray();
			$clients_oauth = array_column($clients_oauth, "oauth", "type");
			$oauth = array_map("basename", glob(CMF_ROOT . "modules/oauth/*", GLOB_ONLYDIR));
			$oauth2 = array_map("basename", glob(WEB_ROOT . "plugins/oauth/*", GLOB_ONLYDIR));
			foreach ($list as $k => $plugin) {
				if (!$plugin["config"]) {
					continue;
				}
				$file = CMF_ROOT . "modules/oauth/{$plugin["name"]}/{$plugin["url"]}";
				$plugins[$k]["name"] = $plugin["title"];
				$class = "oauth\\{$plugin["name"]}\\{$plugin["name"]}";
				$obj = new $class();
				$meta = $obj->meta();
				if (in_array($plugin["name"], $oauth)) {
					$oauth_img = CMF_ROOT . "modules/oauth/{$plugin["name"]}/{$meta["logo_url"]}";
				}
				if (in_array($plugin["name"], $oauth2)) {
					$oauth_img = WEB_ROOT . "plugins/oauth/{$plugin["name"]}/{$meta["logo_url"]}";
				}
				if (stripos($oauth_img, ".svg") === false) {
					$plugins[$k]["img"] = "<img width=30 height=30 src=\"" . base64EncodeImage($oauth_img) . "\" />";
				} else {
					$plugins[$k]["img"] = file_get_contents($oauth_img);
				}
				$plugins[$k]["url"] = $this->domain . "/oauth/url/" . $plugin["name"];
			}
			$plugins = array_merge($plugins);
			$data["oauth"] = $plugins;
		} else {
			$data["oauth"] = [];
		}
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
		$data["login_sms_alert"] = $model["is_login_sms_reminder"] ? 1 : 0;
		$data["login_email_alert"] = $model["email_remind"] ? 1 : 0;
		$data["sms_country"] = getCountryCode();
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	public function password()
	{
		$clientId = $this->request->uid;
		$data = $this->request->param();
		$flag = $data["flag"];
		if ($flag == 1) {
			$validate = new \think\Validate(["old_password" => "require|min:6|max:32", "new_password" => "require|min:6|max:32"]);
			if (configuration("allow_resetpwd_captcha") == 1 && configuration("is_captcha") == 1) {
				if (empty($data["captcha"])) {
					return json(["status" => 400, "msg" => "Graphic verification code cannot be empty"]);
				} else {
					if (!\think\facade\Cache::get("code_" . $data["idtoken"])) {
						return json(["status" => 400, "msg" => "Graphic verification code is invalid"]);
					} else {
						if (\think\facade\Cache::get("code_" . $data["idtoken"]) != strtoupper($data["captcha"])) {
							return json(["status" => 400, "msg" => "Graphic verification code is wrong"]);
						}
					}
				}
			}
		} else {
			$validate = new \think\Validate(["new_password" => "require|min:6|max:32"]);
			if (configuration("allow_resetpwd_captcha") == 1 && configuration("is_captcha") == 1) {
				if (empty($data["captcha"])) {
					return json(["status" => 400, "msg" => "Graphic verification code cannot be empty"]);
				} else {
					if (!\think\facade\Cache::get("code_" . $data["idtoken"])) {
						return json(["status" => 400, "msg" => "Graphic verification code is invalid"]);
					} else {
						if (\think\facade\Cache::get("code_" . $data["idtoken"]) != strtoupper($data["captcha"])) {
							return json(["status" => 400, "msg" => "Graphic verification code is wrong"]);
						}
					}
				}
			}
		}
		if (!$validate->check($data)) {
			return json(["status" => 400, "msg" => $validate->getError()]);
		}
		$client = \think\Db::name("clients")->where("id", $clientId)->find();
		$oldPassword = $data["old_password"];
		$password = $data["new_password"];
		if ($flag == 1) {
			if (cmf_compare_password($oldPassword, $client["password"])) {
				if (cmf_compare_password($password, $client["password"])) {
					return json(["status" => 400, "msg" => lang("LOGIN_NEW_SAME")]);
				} else {
					\think\facade\Cache::set("client_user_update_pass_" . $clientId, $this->request->time(), 7200);
					\think\Db::name("clients")->where("id", $clientId)->update(["password" => cmf_password($password)]);
					active_logs(sprintf($this->lang["User_home_modifyPassword_success"]), $clientId);
					active_logs(sprintf($this->lang["User_home_modifyPassword_success"]), $clientId, "", 2);
					hook("client_reset_password", ["uid" => $clientId, "password" => html_entity_decode($password, ENT_QUOTES)]);
					return json(["status" => 200, "msg" => \lang("LOGIN_UPDATE")]);
				}
			} else {
				return json(["status" => 400, "msg" => \lang("LOGIN_NO")]);
			}
		} else {
			if (cmf_compare_password($password, $client["password"])) {
				return json(["status" => 400, "msg" => lang("LOGIN_NEW_SAME")]);
			} else {
				\think\facade\Cache::set("client_user_update_pass_" . $clientId, $this->request->time(), 7200);
				\think\Db::name("clients")->where("id", $clientId)->update(["password" => cmf_password($password)]);
				active_logs(sprintf($this->lang["User_home_modifyPassword_success"]), $clientId);
				active_logs(sprintf($this->lang["User_home_modifyPassword_success"]), $clientId, "", 2);
				\think\facade\Cache::rm("code_" . $data["idtoken"]);
				return json(["status" => 200, "msg" => \lang("LOGIN_UPDATE")]);
			}
		}
	}
	public function phoneBind(\think\Request $request)
	{
		$validate = new \think\Validate(["phone_code" => "require", "phone" => "require", "code" => "require"]);
		$data = $this->request->param();
		if (!$validate->check($data)) {
			return json(["status" => 400, "msg" => $validate->getError()]);
		}
		$mobile = $data["phone"];
		$id = $this->request->uid;
		$clientsModel = new \app\home\model\ClientsModel();
		$res = $clientsModel->where("phonenumber", $data["phone"])->cache("bind_phone", 300)->find();
		if (!empty($res)) {
			if ($res["id"] != $id) {
				return json(["status" => 400, "msg" => "The mobile phone number has been bound by others"]);
			}
			if ($res["phonenumber"] == $data["phone"]) {
				return json(["status" => 400, "msg" => "You have bound this phone number, no need to repeat the operation"]);
			}
		}
		$code = $data["code"];
		$rel_code = cache("verification_code_bind_phone" . $data["phone_code"] . $mobile);
		if (!isset($rel_code)) {
			return json(["status" => 400, "msg" => "Verification code has expired"]);
		}
		if ($code != $rel_code) {
			return json(["status" => 400, "msg" => "Verification code error"]);
		}
		$User = \app\home\model\ClientsModel::get($id);
		$where = ["id" => $id];
		$res = $User->save(["phonenumber" => $mobile, "phone_code" => $data["phone_code"]], $where);
		if ($res) {
			\think\facade\Cache::rm("bind_phone" . $mobile);
			$email_logic = new \app\common\logic\Email();
			$email_logic->sendEmailBind($res["email"] ?? "", "bind phone");
			$message_template_type = array_column(config("message_template_type"), "id", "name");
			$sms = new \app\common\logic\Sms();
			$client = check_type_is_use($message_template_type[strtolower("email_bond_notice")], $id, $sms);
			if ($client) {
				$params = ["username" => $User["username"], "epw_type" => "手机", "epw_account" => $mobile];
				$ret = sendmsglimit($client["phonenumber"]);
				if ($ret["status"] == 400) {
					return json(["status" => 400, "msg" => lang("SEND FAIL") . ":" . $ret["msg"]]);
				}
				$ret = $sms->sendSms($message_template_type[strtolower("email_bond_notice")], $client["phone_code"] . $client["phonenumber"], $params, false, $id);
				if ($ret["status"] == 200) {
					$data = ["ip" => get_client_ip6(), "phone" => $client["phonenumber"], "time" => time()];
					\think\Db::name("sendmsglimit")->insertGetId($data);
				}
			}
			active_logs(sprintf($this->lang["User_home_bind_phone_handle_success"], substr_replace($mobile, "****", 3, 4)), $id);
			active_logs(sprintf($this->lang["User_home_bind_phone_handle_success"], substr_replace($mobile, "****", 3, 4)), $id, "", 2);
			return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
		}
		return json(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	public function emailBind(\think\Request $request)
	{
		$validate = new \think\Validate(["code" => "require", "email" => "email"]);
		$data = $request->only(["email", "code"]);
		if (!$validate->check($data)) {
			return json(["status" => 400, "msg" => $validate->getError()]);
		}
		$email = $data["email"];
		$id = $request->uid;
		$rel_code = cache("verification_code_bind_email" . $email);
		if ($rel_code != $data["code"]) {
			return json(["status" => 400, "msg" => "Verification code error or expired"]);
		}
		unset($data["code"]);
		$clientsModel = new \app\home\model\ClientsModel();
		$res = $clientsModel->cache("bind_email")->find($id);
		$msg = lang("SUCCESS MESSAGE");
		if ($res["email"]) {
			$msg = "Mailbox modified successfully";
		}
		$data["id"] = $id;
		$log = $clientsModel->cache("bind_email")->update($data);
		if (!$log) {
			return json(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
		}
		$email_logic = new \app\common\logic\Email();
		$email_logic->sendEmailBind($email, "bind email");
		$User = \app\home\model\ClientsModel::get($id);
		$message_template_type = array_column(config("message_template_type"), "id", "name");
		$sms = new \app\common\logic\Sms();
		$client = check_type_is_use($message_template_type[strtolower("email_bond_notice")], $id, $sms);
		if ($client) {
			$params = ["username" => $User["username"], "epw_type" => "邮箱", "epw_account" => $data["email"]];
			$sms->sendSms($message_template_type[strtolower("email_bond_notice")], $client["phone_code"] . $client["phonenumber"], $params, false, $id);
		}
		active_logs(sprintf($this->lang["User_home_bind_email_handle_success"], substr_replace($email, "****", 3, 4)), $id);
		active_logs(sprintf($this->lang["User_home_bind_email_handle_success"], substr_replace($email, "****", 3, 4)), $id, "", 2);
		return json(["data" => $data, "status" => 200, "msg" => $msg]);
	}
	public function loginNotice()
	{
		$type = $this->request->post("type");
		$status = \intval($this->request->post("status", 0));
		$code = \intval($this->request->post("code", 0));
		if ($status !== 0 && $status !== 1) {
			return json(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
		}
		if ($type == "email") {
			$user = \think\Db::name("clients")->find($this->request->uid);
			if (!isset($user["email"][0])) {
				return json(["status" => 400, "msg" => "Please bind mailbox"]);
			}
			$data = $this->request->param();
			if ($status === 0) {
				if ($code <= 0) {
					return json(["status" => 400, "msg" => "Incorrect verification code"]);
				}
				$tmp = \intval(cache("verification_code_login_notice_email" . $user["email"]));
				if ($code !== $tmp) {
					return json(["status" => 400, "msg" => "Incorrect verification code"]);
				}
			}
			$res = \think\Db::name("clients")->where("id", $this->request->uid)->update(["email_remind" => $status]);
			if ($res) {
				if ($status == 1) {
					active_logs(sprintf($this->lang["User_home_loginSmsReminder_success1"]), $this->request->uid);
					active_logs(sprintf($this->lang["User_home_loginSmsReminder_success1"]), $this->request->uid, "", 2);
				} else {
					active_logs(sprintf($this->lang["User_home_loginSmsReminder_success2"]), $this->request->uid);
					active_logs(sprintf($this->lang["User_home_loginSmsReminder_success2"]), $this->request->uid, "", 2);
				}
				return json(["status" => 200, "msg" => lang("UPDATE SUCCESS")]);
			} else {
				return json(["status" => 400, "msg" => lang("UPDATE FAIL")]);
			}
		} else {
			$user = \think\Db::name("clients")->find($this->request->uid);
			if (!isset($user["phonenumber"][0])) {
				return json(["status" => 400, "msg" => "Please bind mobile number"]);
			}
			$data = $this->request->param();
			if ($status === 0) {
				if ($code <= 0) {
					return json(["status" => 400, "msg" => "Incorrect verification code"]);
				}
				$tmp = \intval(cache("verification_code_login_notice_phone" . $user["phone_code"] . $user["phonenumber"]));
				if ($code !== $tmp) {
					return json(["status" => 400, "msg" => "Incorrect verification code"]);
				}
			}
			$res = \think\Db::name("clients")->where("id", $this->request->uid)->update(["is_login_sms_reminder" => $status]);
			if ($res) {
				if ($status == 1) {
					active_logs(sprintf($this->lang["User_home_loginSmsReminder_success1"]), $this->request->uid);
					active_logs(sprintf($this->lang["User_home_loginSmsReminder_success1"]), $this->request->uid, "", 2);
				} else {
					active_logs(sprintf($this->lang["User_home_loginSmsReminder_success2"]), $this->request->uid);
					active_logs(sprintf($this->lang["User_home_loginSmsReminder_success2"]), $this->request->uid, "", 2);
				}
				return json(["status" => 200, "msg" => lang("UPDATE SUCCESS")]);
			} else {
				return json(["status" => 400, "msg" => lang("UPDATE FAIL")]);
			}
		}
	}
	public function realNameAuth()
	{
		$uid = $this->request->uid;
		$data = ["type" => "certifi_company", "status" => 0];
		$arr = [];
		$param = $this->request->param();
		$log = \think\Db::name("certifi_log")->where("uid", $uid)->order("id", "DESC")->find();
		if (isset($param["action"])) {
			$action = $param["action"];
			if ($action == "personal") {
				$data["type"] = "certifi_person";
			}
		} else {
			if ($log["type"] == "1") {
				$data["type"] = "certifi_person";
				$action = "personal";
			} else {
				$action = "enterprises";
			}
		}
		if ($action) {
			$all_plugins = getPluginsList("certification", $action);
			if (empty($all_plugins[0])) {
				$arr[] = ["name" => "人工审核", "value" => "artificial", "custom_fields" => []];
			} else {
				foreach ($all_plugins as $all_plugin) {
					$arr[] = ["name" => $all_plugin["title"], "value" => $all_plugin["name"], "custom_fields" => $all_plugin["custom_fields"] ?: []];
				}
			}
		}
		$default = $log["certifi_type"] ?: ($arr[0]["name"] ?: "artificial");
		if (!isset($log["id"])) {
			$certifi = \think\Db::name("certifi_person")->where("auth_user_id", $uid)->find();
			if (!isset($certifi["id"])) {
				$return = ["message" => $data, "method" => $arr, "default" => $default, "upload" => configuration("certifi_is_upload") ?? 1];
				return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $return]);
			}
		} else {
			$certifi = \think\Db::name($data["type"])->where("auth_user_id", $uid)->find();
			if (!isset($certifi["id"]) || $certifi["status"] == 2 && $param["status"] == "closed") {
				$certifi = \think\Db::name("certifi_person")->where("auth_user_id", $uid)->find();
				$certifi["type"] = "certifi_person";
			}
		}
		if (!isset($certifi["id"])) {
			$return = ["message" => $data, "method" => $arr, "default" => $default, "upload" => configuration("certifi_is_upload") ?? 1];
			return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $return]);
		}
		unset($certifi["id"]);
		unset($certifi["auth_user_id"]);
		$certifi["auth_real_name"] = mb_substr($certifi["auth_real_name"], 0, 1) . "**";
		$certifi["auth_card_number"] = mb_substr($certifi["auth_card_number"], 0, 1) . str_repeat("*", strlen($certifi["auth_card_number"]) - 2) . mb_substr($certifi["auth_card_number"], strlen($certifi["auth_card_number"]) - 1, 1);
		if ($certifi["status"] == 1) {
			$certifi["auth_fail"] = "";
		}
		$data = array_merge($data, $certifi);
		if (!isset($data["img_four"])) {
			$data["img_four"] = "";
		}
		if (!empty($data["img_one"])) {
			$data["img_one"] = $this->getimage . $data["img_one"];
		}
		if (!empty($data["img_two"])) {
			$data["img_two"] = $this->getimage . $data["img_two"];
		}
		if (!empty($data["img_three"])) {
			$data["img_three"] = $this->getimage . $data["img_three"];
		}
		if (!empty($data["img_four"])) {
			$data["img_four"] = $this->getimage . $data["img_four"];
		}
		unsuspendAfterCertify($uid);
		$return = ["message" => $data, "method" => $arr, "default" => $default, "upload" => configuration("certifi_is_upload") ?? 1];
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $return]);
	}
	public function personRealNameAuth()
	{
		$certifi_open = configuration("certifi_open");
		if ($certifi_open != 1) {
			return json(["status" => 400, "msg" => lang("CERTIFI_CLOSE")]);
		}
		$re = [];
		$clientid = $uid = $this->request->uid;
		$tmp = \think\Db::name("certifi_company")->where("auth_user_id", $clientid)->find();
		if (isset($tmp["id"]) && $tmp["status"] !== 2) {
			if ($tmp["status"] === 1) {
				$msg = lang("CERTIFI_COMPANY_COMPLETE");
			} elseif ($tmp["status"] === 3) {
				$msg = "Enterprise certification is pending review and cannot be submitted";
			} elseif ($tmp["status"] === 4) {
				$msg = "Enterprise certification has submitted materials, which cannot be submitted";
			} else {
				$msg = "Status error";
			}
			return json(["status" => 400, "msg" => $msg]);
		}
		$data = $this->request->param();
		$idcard = $data["idcard"];
		$certifimodel = new \app\home\model\CertificationModel();
		if ($certifimodel->checkOtherUsed($idcard, $clientid)) {
			return json(["status" => 400, "msg" => lang("CERTIFI_CARD_HAS_USED_BY_OTHER")]);
		}
		if (!in_array($data["card_type"], ["0", "1"])) {
			return json(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
		}
		$certifi_type = $data["certifi_type"];
		if (empty($certifi_type)) {
			return json(["status" => 400, "msg" => lang("CFCATION_TYPE_IS_NOT_NULL")]);
		}
		$pluginName = $certifi_type;
		$class = cmf_get_plugin_class_shd($pluginName, "certification");
		$methods = get_class_methods($class);
		if (in_array(lcfirst($pluginName) . "idcsmartauthorize", $methods) || in_array($pluginName . "idcsmartauthorize", $methods)) {
			$res = pluginIdcsmartauthorize($pluginName);
			if ($res["status"] != 200) {
				return json($res);
			}
		}
		$all_type = array_column(getPluginsList("certification", "personal"), "name") ?: [];
		array_unshift($all_type, "artificial");
		if (!in_array($certifi_type, $all_type)) {
			return json(["status" => 400, "msg" => "Authentication method error"]);
		}
		$custom_fields_log = [];
		if ($certifi_type != "artificial") {
			$cname = cmf_parse_name($certifi_type);
			$customfields = getCertificationCustomFields($cname, "certification");
			if (!empty($customfields[0])) {
				$i = 0;
				foreach ($customfields as $customfield) {
					if ($customfield["type"] == "text") {
						if ($customfield["required"] && empty($data[$customfield["field"]])) {
							return json(["status" => 400, "msg" => $customfield["title"] . " required"]);
						}
					} elseif ($customfield["type"] == "file") {
						if ($customfield["required"] && empty($_FILES[$customfield["field"]]["name"])) {
							return json(["status" => 400, "msg" => $customfield["title"] . " required"]);
						}
						$image = request()->file($customfield["field"]);
						$str = explode(pathinfo($image->getInfo()["name"])["extension"], $image->getInfo()["name"])[0];
						if (preg_match("/[ ',:;*?~`!@#\$%^&+=)(<>{}]|\\]|\\[|\\/|\\\\|\"|\\|/", substr($str, 0, strlen($str) - 1))) {
							return json(["status" => 400, "msg" => "Only numbers, letters and Chinese characters are allowed for file names"]);
						}
						$upload = new \app\common\logic\Upload();
						$resultUpload = $upload->uploadHandle($image);
						if ($resultUpload["status"] != 200) {
							return json(["status" => 400, "msg" => $resultUpload["msg"]]);
						}
						$avatar = $upload->moveTo($resultUpload["savename"], config("certificate"));
						if (isset($avatar["error"])) {
							return json(["status" => 400, "msg" => $avatar["error"]]);
						}
						$data[$customfield["field"]] = $avatar;
					} else {
						if ($customfield["type"] == "select" && !in_array($data[$customfield["field"]], array_keys($customfield["options"]))) {
							return json(["status" => 400, "msg" => $customfield["title"] . " in " . implode(",", array_values($customfield["options"]))]);
						}
					}
					$i++;
					if ($i <= 10) {
						$certifi["custom_fields" . $i] = $data[$customfield["field"]];
						$custom_fields_log["custom_fields" . $i] = $data[$customfield["field"]];
					}
				}
			}
		}
		if (configuration("certifi_isbindphone") == 1 && !empty($data["phone"])) {
			$id = \think\Db::name("clients")->field("phonenumber")->where("id", $clientid)->find();
			if (!empty($id["phonenumber"]) && $id["phonenumber"] != $data["phone"]) {
				return json(["status" => 400, "msg" => lang("CFCATION_ISBINDPHONE_ERROR")]);
			}
		}
		$validate = new \app\home\validate\CertificationValidate();
		$cardtype = isset($data["card_type"]) ? $data["card_type"] : 1;
		$data_re = [];
		if ($cardtype == 0) {
			$othercard["auth_user_id"] = $clientid;
			$othercard["bank"] = !empty($data["bank"]) ? $data["bank"] : "";
			$othercard["phone"] = !empty($data["phone"]) ? $data["phone"] : "";
			$othercard["auth_real_name"] = $data["real_name"];
			$othercard["auth_card_type"] = 0;
			$othercard["auth_card_number"] = $idcard;
			if (!$validate->scene("gatRegion")->check($othercard)) {
				$re["status"] = 400;
				$re["msg"] = $validate->getError();
				return json($re);
			}
			$pic = "";
			$othercard["img_one"] = "";
			$othercard["img_two"] = "";
			$othercard["idimage"] = $this->request->param("idimage");
			if (!isset($othercard["idimage"][0][0])) {
				$re["status"] = 400;
				$re["msg"] = lang("CERTIFI_CARD_IMAGE1");
				return json($re);
			}
			if (!isset($othercard["idimage"][1][0])) {
				$re["status"] = 400;
				$re["msg"] = lang("CERTIFI_CARD_IMAGE2");
				return json($re);
			}
			if (isset($othercard["idimage"][0][0])) {
				$upload = new \app\common\logic\Upload();
				$avatar = $upload->moveTo($othercard["idimage"], config("certificate"));
				if (isset($avatar["error"])) {
					return json(["status" => 400, "msg" => $avatar["error"]]);
				}
				$pic = implode(",", $othercard["idimage"]);
				$othercard["img_one"] = $avatar[0] ?? "";
				$othercard["img_two"] = $avatar[1] ?? "";
				unset($othercard["idimage"]);
			}
			$othercard["status"] = 3;
			$checkuser = \think\Db::name("certifi_person")->where("auth_user_id", $clientid)->find();
			if (!empty($checkuser)) {
				$othercard["update_time"] = time();
				\think\Db::name("certifi_person")->where("auth_user_id", $clientid)->update($othercard);
			} else {
				$othercard["create_time"] = time();
				\think\Db::name("certifi_person")->insertGetId($othercard);
			}
			$othercard["pic"] = $pic;
			$this->save_log($othercard, $this->type, $certifi_type, $othercard["status"], $custom_fields_log);
			active_logs(sprintf($this->lang["Certification_home_personCertifiPost"], $othercard["auth_real_name"]), $clientid);
			active_logs(sprintf($this->lang["Certification_home_personCertifiPost"], $othercard["auth_real_name"]), $clientid, "", 2);
		}
		if ($cardtype == 1) {
			$certifi["bank"] = !empty($data["bank"]) ? $data["bank"] : "";
			$certifi["phone"] = !empty($data["phone"]) ? $data["phone"] : "";
			$certifi["auth_user_id"] = $clientid;
			$certifi["auth_real_name"] = $data["real_name"];
			$certifi["auth_card_type"] = $data["card_type"];
			$certifi["auth_card_number"] = $data["idcard"];
			if (!$validate->scene("personedit")->check($certifi)) {
				$re["status"] = 400;
				$re["msg"] = $validate->getError();
				return json($re);
			}
			$pic = "";
			$othercard["img_one"] = "";
			$othercard["img_two"] = "";
			$certifi_is_upload = configuration("certifi_is_upload");
			if ($certifi_is_upload == 1) {
				$othercard["idimage"] = $this->request->param("idimage");
				if (!isset($othercard["idimage"][0][0])) {
					$re["status"] = 400;
					$re["msg"] = lang("CERTIFI_CARD_IMAGE1");
					return json($re);
				}
				if (!isset($othercard["idimage"][1][0])) {
					$re["status"] = 400;
					$re["msg"] = lang("CERTIFI_CARD_IMAGE2");
					return json($re);
				}
				if (isset($othercard["idimage"][0][0])) {
					$upload = new \app\common\logic\Upload();
					$avatar = $upload->moveTo($othercard["idimage"], config("certificate"));
					if (isset($avatar["error"])) {
						return json(["status" => 400, "msg" => $avatar["error"]]);
					}
					$pic = implode(",", $othercard["idimage"]);
					$certifi["img_one"] = $othercard["idimage"][0];
					$certifi["img_two"] = $othercard["idimage"][1];
				}
			}
			$isPerson = \think\Db::name("certifi_person")->where("auth_user_id", $clientid)->find();
			if ($certifi_type == "artificial") {
				$certifi["status"] = 3;
			} else {
				$certifi["status"] = 4;
			}
			$flag = true;
			\think\Db::startTrans();
			try {
				if (!empty($isPerson)) {
					$certifi["update_time"] = time();
					$res1 = \think\Db::name("certifi_person")->where("auth_user_id", $clientid)->update($certifi);
				} else {
					$certifi["create_time"] = time();
					$res1 = \think\Db::name("certifi_person")->insertGetId($certifi);
				}
				$certifi["pic"] = $pic;
				$this->save_log($certifi, $this->type, $certifi_type, $certifi["status"], $custom_fields_log);
				\think\Db::commit();
			} catch (\Exception $e) {
				$flag = false;
				\think\Db::rollback();
			}
			if ($flag && $certifi_type != "artificial") {
				$_config = \think\Db::name("plugin")->where("module", "certification")->where("status", 1)->where("name", $certifi_type)->value("config");
				$_config = json_decode($_config, true);
				$free = intval($_config["free"]);
				$count = \think\Db::name("certifi_log")->where("uid", $uid)->where("card_type", 1)->where("certifi_type", $certifi_type)->count();
				$pay = false;
				if ($free == 0 || $free > 0 && $free < $count) {
					$pay = true;
				}
				if (floatval($_config["amount"]) > 0 && $pay) {
					$amount = floatval($_config["amount"]);
					$client = \think\Db::name("clients")->where("id", $uid)->find();
					$invoices_data = ["uid" => $uid, "create_time" => time(), "due_time" => time(), "paid_time" => 0, "last_capture_attempt" => 0, "subtotal" => $amount, "credit" => 0, "tax" => 0, "tax2" => 0, "total" => $amount, "taxrate" => 0, "taxrate2" => 0, "status" => "Unpaid", "payment" => $client["defaultgateway"] ?: "", "notes" => "", "type" => "certifi_person", "url" => "verified?action=personal&step=authstart&type={$certifi_type}"];
					$invoices_items = ["uid" => $uid, "type" => "certifi_person", "description" => "个人认证", "description2" => "个人认证", "amount" => $amount, "due_time" => time(), "payment" => $client["defaultgateway"] ?: "", "rel_id" => $uid];
					\think\Db::startTrans();
					try {
						$invoiceid = \think\Db::name("invoices")->insertGetId($invoices_data);
						$invoices_items["invoice_id"] = $invoiceid;
						\think\Db::name("invoice_items")->insert($invoices_items);
						\think\Db::commit();
						$data_re["invoice_id"] = $invoiceid;
					} catch (\Exception $e) {
						\think\Db::rollback();
						return json(["status" => 400, "msg" => "Failed to generate bill:" . $e->getMessage()]);
					}
				}
			}
		}
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data_re]);
	}
	public function companyRealNameAuth()
	{
		if ($this->request->isPost()) {
			$certifi_open = configuration("certifi_open");
			if ($certifi_open == 2) {
				return json(["status" => 400, "msg" => lang("CERTIFI_CLOSE")]);
			}
			if ($this->type === 1) {
				$this->type = 2;
			}
			$clientid = $uid = $this->request->uid;
			$data = $this->request->param();
			try {
				$img_three = $this->companyBusinessUpload();
				$img_four = $this->companyAuthorUpload();
			} catch (\Throwable $e) {
				return json(["status" => 400, "msg" => $e->getMessage()]);
			}
			if (!in_array($data["card_type"], ["0", "1"])) {
				return json(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
			}
			$certifi_type = $data["certifi_type"];
			$pluginName = $certifi_type;
			$class = cmf_get_plugin_class_shd($pluginName, "certification");
			$methods = get_class_methods($class);
			if (in_array(lcfirst($pluginName) . "idcsmartauthorize", $methods) || in_array($pluginName . "idcsmartauthorize", $methods)) {
				$res = pluginIdcsmartauthorize($pluginName);
				if ($res["status"] != 200) {
					return json($res);
				}
			}
			$all_type = array_column(getPluginsList("certification", "enterprises"), "name") ?: [];
			array_unshift($all_type, "artificial");
			if (!in_array($certifi_type, $all_type)) {
				return json(["status" => 400, "msg" => "Authentication method error"]);
			}
			$custom_fields_log = [];
			if ($certifi_type != "artificial") {
				$cname = cmf_parse_name($certifi_type);
				$customfields = getCertificationCustomFields($cname, "certification");
				if (!empty($customfields[0])) {
					$i = 0;
					foreach ($customfields as $customfield) {
						if ($customfield["type"] == "text") {
							if ($customfield["required"] && empty($data[$customfield["field"]])) {
								return json(["status" => 400, "msg" => $customfield["title"] . " required"]);
							}
						} elseif ($customfield["type"] == "file") {
							if ($customfield["required"] && empty($_FILES[$customfield["field"]]["name"])) {
								return json(["status" => 400, "msg" => $customfield["title"] . " required"]);
							}
							$image = request()->file($customfield["field"]);
							$str = explode(pathinfo($image->getInfo()["name"])["extension"], $image->getInfo()["name"])[0];
							if (preg_match("/[ ',:;*?~`!@#\$%^&+=)(<>{}]|\\]|\\[|\\/|\\\\|\"|\\|/", substr($str, 0, strlen($str) - 1))) {
								return json(["status" => 400, "msg" => "Only numbers, letters and Chinese characters are allowed for file names"]);
							}
							$upload = new \app\common\logic\Upload();
							$resultUpload = $upload->uploadHandle($image);
							if ($resultUpload["status"] != 200) {
								return json(["status" => 400, "msg" => $resultUpload["msg"]]);
							}
							$avatar = $upload->moveTo($resultUpload["savename"], config("certificate"));
							if (isset($avatar["error"])) {
								return json(["status" => 400, "msg" => $avatar["error"]]);
							}
							$data[$customfield["field"]] = $avatar;
						} else {
							if ($customfield["type"] == "select" && !in_array($data[$customfield["field"]], array_keys($customfield["options"]))) {
								return json(["status" => 400, "msg" => $customfield["title"] . " in " . implode(",", array_values($customfield["options"]))]);
							}
						}
						$i++;
						if ($i <= 10) {
							$certifi["custom_fields" . $i] = $data[$customfield["field"]];
							$custom_fields_log["custom_fields" . $i] = $data[$customfield["field"]];
						}
					}
				}
			}
			$certifi["bank"] = !empty($data["bank"]) ? $data["bank"] : "";
			$certifi["phone"] = !empty($data["phone"]) ? $data["phone"] : "";
			$certifi["auth_user_id"] = $clientid;
			$certifi["company_name"] = $data["company_name"] ?? "";
			$certifi["company_organ_code"] = $data["company_organ_code"] ?? "";
			$certifi["auth_real_name"] = $data["real_name"] ?? "";
			$certifi["auth_card_number"] = $data["idcard"] ?? "";
			$certifi["idimage"] = $data["idimage"] ?? "";
			$certifimodel = new \app\home\model\CertificationModel();
			$idcard = $certifi["auth_card_number"];
			if ($certifimodel->checkOtherUsed($idcard, $clientid)) {
				return json(["status" => 400, "msg" => lang("CERTIFI_CARD_HAS_USED_BY_OTHER")]);
			}
			$cardtype = isset($data["card_type"]) ? $data["card_type"] : 1;
			$certifi["auth_card_type"] = $cardtype;
			$data_re = [];
			$validate = new \app\home\validate\CertificationValidate();
			if ($cardtype == 0) {
				if (!$validate->scene("companygatregion")->check($certifi)) {
					$re["status"] = 400;
					$re["msg"] = $validate->getError();
					return json($re);
				}
				$res = $this->checkCompanyUpload($certifi);
				if ($res["status"] === 400) {
					return json($res);
				}
				unset($certifi["idimage"]);
				$certifi["img_one"] = "";
				$certifi["img_two"] = "";
				$certifi["img_three"] = $img_three;
				$certifi["img_four"] = $img_four;
				if (isset($res[0])) {
					$certifi["img_one"] = $res[0];
					$certifi["img_two"] = $res[1];
				}
				$pic = implode(",", [$certifi["img_one"], $certifi["img_two"], $certifi["img_three"], $certifi["img_four"]]);
				$certifi["status"] = 3;
				$checkuser = \think\Db::name("certifi_company")->where("auth_user_id", $clientid)->find();
				if (!empty($checkuser)) {
					$certifi["update_time"] = time();
					\think\Db::name("certifi_company")->where("auth_user_id", $clientid)->update($certifi);
				} else {
					$certifi["create_time"] = time();
					\think\Db::name("certifi_company")->insertGetId($certifi);
				}
				$certifi["pic"] = $pic;
				$this->save_log($certifi, $this->type, $certifi_type, $certifi["status"], $custom_fields_log);
			}
			if ($cardtype == 1) {
				if (!$validate->scene("companyedit")->check($certifi)) {
					$re["status"] = 400;
					$re["msg"] = $validate->getError();
					return json($re);
				}
				$upload = $this->checkCompanyUpload($certifi);
				if ($upload["status"] === 400) {
					return json($upload);
				}
				unset($certifi["idimage"]);
				$certifi["img_one"] = "";
				$certifi["img_two"] = "";
				$certifi["img_three"] = $img_three;
				$certifi["img_four"] = $img_four;
				if (isset($upload[0])) {
					$certifi["img_one"] = $upload[0];
					$certifi["img_two"] = $upload[1];
				}
				$pic = implode(",", [$certifi["img_one"], $certifi["img_two"], $certifi["img_three"], $certifi["img_four"]]);
				$isCompany = \think\Db::name("certifi_company")->where("auth_user_id", $clientid)->find();
				$certifi["status"] = 4;
				if (!empty($isCompany)) {
					$certifi["update_time"] = time();
					$res1 = \think\Db::name("certifi_company")->where("auth_user_id", $clientid)->update($certifi);
				} else {
					$certifi["create_time"] = time();
					$res1 = \think\Db::name("certifi_company")->insertGetId($certifi);
				}
				if ($res1 && $certifi_type != "artificial") {
					$_config = \think\Db::name("plugin")->where("module", "certification")->where("status", 1)->where("name", $certifi_type)->value("config");
					$_config = json_decode($_config, true);
					$free = intval($_config["free"]);
					$count = \think\Db::name("certifi_log")->where("uid", $uid)->where("card_type", 1)->where("certifi_type", $certifi_type)->count();
					$pay = false;
					if ($free == 0 || $free > 0 && $free <= $count) {
						$pay = true;
					}
					if (floatval($_config["amount"]) > 0 && $pay) {
						$amount = floatval($_config["amount"]);
						$client = \think\Db::name("clients")->where("id", $uid)->find();
						$invoices_data = ["uid" => $uid, "create_time" => time(), "due_time" => time(), "paid_time" => 0, "last_capture_attempt" => 0, "subtotal" => $amount, "credit" => 0, "tax" => 0, "tax2" => 0, "total" => $amount, "taxrate" => 0, "taxrate2" => 0, "status" => "Unpaid", "payment" => $client["defaultgateway"] ?: "", "notes" => "", "type" => "certifi_company", "url" => "verified?action=enterprises&step=authstart&type={$certifi_type}"];
						$invoices_items = ["uid" => $uid, "type" => "certifi_company", "description" => "企业认证", "description2" => "企业认证", "amount" => $amount, "due_time" => time(), "payment" => $client["defaultgateway"] ?: "", "rel_id" => $uid];
						\think\Db::startTrans();
						try {
							$invoiceid = \think\Db::name("invoices")->insertGetId($invoices_data);
							$invoices_items["invoice_id"] = $invoiceid;
							\think\Db::name("invoice_items")->insert($invoices_items);
							\think\Db::commit();
							$data_re["invoice_id"] = $invoiceid;
						} catch (\Exception $e) {
							\think\Db::rollback();
							return json(["status" => 400, "msg" => "Failed to generate bill:" . $e->getMessage()]);
						}
					}
				}
			}
			$certifi["pic"] = $pic;
			$this->save_log($certifi, $this->type, $certifi_type, $certifi["status"], $custom_fields_log);
			active_logs(sprintf($this->lang["Certification_home_companyCertifiPost"], $certifi["company_name"]), $clientid);
			active_logs(sprintf($this->lang["Certification_home_companyCertifiPost"], $certifi["company_name"]), $clientid, "");
		}
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data_re]);
	}
	protected function companyBusinessUpload()
	{
		$img_three = "";
		$conf = $this->getConfInfo();
		if (!$conf["certifi_business_is_upload"]) {
			return $img_three;
		}
		if (empty($_FILES["business_license"]["name"][0])) {
			throw new Exception(lang("CERTIFI_CARD_IMAGE3"));
		}
		$attachments = request()->file("business_license");
		foreach ($attachments as $image) {
			$str = explode(pathinfo($image->getInfo()["name"])["extension"], $image->getInfo()["name"])[0];
			if (preg_match("/[ ',:;*?~`!@#\$%^&+=)(<>{}]|\\]|\\[|\\/|\\\\|\"|\\|/", substr($str, 0, strlen($str) - 1))) {
				throw new Exception("文件名只允许数字，字母，还有汉字");
			}
		}
		$upload = new \app\common\logic\Upload(config("certificate"));
		foreach ($attachments as $image) {
			$resultUpload = $upload->uploadHandle($image);
			if (!$resultUpload) {
				throw new Exception(lang("ERROR MESSAGE"));
			}
			if ($resultUpload["status"] == 200) {
				$img_three = $resultUpload["savename"];
			} else {
				throw new Exception(lang("ERROR MESSAGE"));
			}
		}
		return $img_three;
	}
	protected function companyAuthorUpload()
	{
		$img_four = "";
		$conf = $this->getConfInfo();
		if (!$conf["certifi_business_is_author"]) {
			return $img_four;
		}
		if (empty($_FILES["certifi_author"]["name"][0])) {
			throw new Exception(lang("CERTIFI_CARD_IMAGE4"));
		}
		$attachments = request()->file("certifi_author");
		foreach ($attachments as $image) {
			$str = explode(pathinfo($image->getInfo()["name"])["extension"], $image->getInfo()["name"])[0];
			if (preg_match("/[ ',:;*?~`!@#\$%^&+=)(<>{}]|\\]|\\[|\\/|\\\\|\"|\\|/", substr($str, 0, strlen($str) - 1))) {
				throw new Exception("文件名只允许数字，字母，还有汉字");
			}
		}
		$upload = new \app\common\logic\Upload(config("certificate"));
		foreach ($attachments as $image) {
			$resultUpload = $upload->uploadHandle($image);
			if (!$resultUpload) {
				throw new Exception(lang("ERROR MESSAGE"));
			}
			if ($resultUpload["status"] == 200) {
				$img_four = $resultUpload["savename"];
			} else {
				throw new Exception(lang("ERROR MESSAGE"));
			}
		}
		return $img_four;
	}
	private function checkCompanyUpload($arr = [])
	{
		$re = [];
		$certifi_is_upload = configuration("certifi_is_upload");
		if ($certifi_is_upload == 1) {
			if (empty($arr["idimage"][0])) {
				$re["status"] = 400;
				$re["msg"] = lang("CERTIFI_CARD_IMAGE1");
				return $re;
			}
			if (empty($arr["idimage"][1])) {
				$re["status"] = 400;
				$re["msg"] = lang("CERTIFI_CARD_IMAGE2");
				return $re;
			}
			if (!empty($arr["idimage"][0])) {
				$upload = new \app\common\logic\Upload();
				$avatar = $upload->moveTo($arr["idimage"], config("certificate"));
				if (isset($avatar["error"])) {
					return ["status" => 400, "msg" => $avatar["error"]];
				}
				return $avatar;
			}
		}
		return [];
	}
	public function realNameAuthStatus()
	{
		$uid = $this->request->uid;
		$param = $this->request->param();
		$type = $param["type"] ?: "";
		$data = [];
		$log = \think\Db::name("certifi_log")->where("uid", $uid)->order("id", "DESC")->find();
		if (!isset($log["id"])) {
			return json(["status" => 400, "msg" => lang("FAIL MESSAGE"), "data" => ["status" => 0]]);
		}
		$table = "certifi_company";
		if ($log["type"] == "1") {
			$table = "certifi_person";
		}
		$certifi = \think\Db::name($table)->where("auth_user_id", $uid)->find();
		if (empty($certifi["certify_id"])) {
			return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
		}
		$data = array_merge($data, $certifi);
		if (($data["status"] == 1 || $data["status"] == 2) && $log["certifi_type"] != "Ali" && $log["certifi_type"] != "Idcsmartali") {
			if ($data["status"] == 1) {
				$status = $table === "certifi_company" ? 3 : 1;
				\think\Db::name("certifi_log")->where("id", $log["id"])->update(["status" => $status]);
				\think\Db::name($table)->where("auth_user_id", $log["uid"])->update(["status" => $status, "auth_fail" => "", "update_time" => time()]);
				if ($status == 1 && configuration("certifi_realname") == 1) {
					$cl = \think\Db::name("certifi_log")->where("id", $log["id"])->find();
					\think\Db::name("clients")->where("id", $cl["uid"])->update(["username" => $cl["certifi_name"]]);
				}
				unsuspendAfterCertify($uid);
			}
			return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
		}
		if (($data["status"] == 4 || $data["status"] == 2) && $data["auth_card_type"] == 1) {
			try {
				$res = zjmfhook($type, "certification", ["certify_id" => $certifi["certify_id"]], "getStatus");
			} catch (\Exception $e) {
				return json(["status" => 400, "msg" => $e->getMessage()]);
			}
			if ($res["status"] == 1) {
				$status = $table === "certifi_company" ? 3 : 1;
				\think\Db::name("certifi_log")->where("id", $log["id"])->update(["status" => $status]);
				\think\Db::name($table)->where("auth_user_id", $log["uid"])->update(["status" => $status, "auth_fail" => "", "update_time" => time()]);
				if ($status == 1 && configuration("certifi_realname") == 1) {
					$cl = \think\Db::name("certifi_log")->where("id", $log["id"])->find();
					\think\Db::name("clients")->where("id", $cl["uid"])->update(["username" => $cl["certifi_name"]]);
				}
				unsuspendAfterCertify($uid);
				return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
			} elseif ($res["status"] == 2) {
				$status = 2;
				\think\Db::name("certifi_log")->where("id", $log["id"])->update(["status" => $status, "error" => $res["msg"]]);
				\think\Db::name($table)->where("auth_user_id", $log["uid"])->update(["status" => $status, "update_time" => time(), "auth_fail" => $res["msg"]]);
			}
		}
		return json(["status" => 400, "msg" => lang("CFCATION_PERSONAL_AUTH_NOT_PASS")]);
	}
	private function save_log($arr, $type, $certifi_type, $status, $custom_fields_log = [])
	{
		$data = ["uid" => $this->request->uid, "certifi_name" => $arr["auth_real_name"], "card_type" => $arr["auth_card_type"], "idcard" => $arr["auth_card_number"], "company_name" => $arr["company_name"] ?? "", "bank" => $arr["bank"] ?? "", "phone" => $arr["phone"] ?? "", "company_organ_code" => $arr["company_organ_code"] ?? "", "pic" => $arr["pic"], "create_time" => $this->request->time(), "status" => $status, "type" => $type, "certifi_type" => $certifi_type, "custom_fields_log" => json_encode($custom_fields_log)];
		$res = \think\Db::name("certifi_log")->insert($data);
		return $res;
	}
	protected function getConfInfo()
	{
		$conf = configuration(["certifi_open", "certifi_is_upload", "certifi_business_open", "certifi_business_is_upload", "certifi_business_is_author", "certifi_business_author_path"]);
		$conf["certifi_is_upload"] = $conf["certifi_is_upload"] == 2 ? 0 : 1;
		$conf["certifi_open"] = $conf["certifi_open"] == 2 ? 0 : 1;
		if (!$conf["certifi_open"]) {
			$conf["certifi_business_open"] = 0;
		}
		$data = ["certifi_is_upload" => $conf["certifi_open"] ? $conf["certifi_is_upload"] : 0, "certifi_business_is_author" => $conf["certifi_business_open"] ? $conf["certifi_business_is_author"] : 0, "certifi_business_author_path" => $conf["certifi_business_author_path"] ? config("author_attachments_url") . $conf["certifi_business_author_path"] : ""];
		$data["certifi_business_is_upload"] = $conf["certifi_business_open"] ? $conf["certifi_business_is_upload"] : $data["certifi_is_upload"];
		return $data;
	}
	public function zjmf_authorize()
	{
		$zjmf_authorize = configuration("zjmf_authorize");
		if (empty($zjmf_authorize)) {
			return false;
		} else {
			$_strcode = _strcode($zjmf_authorize, "DECODE", "zjmf_key_strcode");
			$_strcode = explode("|zjmf|", $_strcode);
			$authkey = "-----BEGIN PUBLIC KEY-----\r\nMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDg6DKmQVwkQCzKcFYb0BBW7N2f\r\nI7DqL4MaiT6vibgEzH3EUFuBCRg3cXqCplJlk13PPbKMWMYsrc5cz7+k08kgTpD4\r\ntevlKOMNhYeXNk5ftZ0b6MAR0u5tiyEiATAjRwTpVmhOHOOh32MMBkf+NNWrZA/n\r\nzcLRV8GU7+LcJ8AH/QIDAQAB\r\n-----END PUBLIC KEY-----";
			$pu_key = openssl_pkey_get_public($authkey);
			foreach ($_strcode as $v) {
				openssl_public_decrypt(base64_decode($v), $de, $pu_key);
				$de_str .= $de;
			}
			$auth = json_decode($de_str, true);
			return intval($auth["edition"]);
		}
	}
}