<?php

namespace app\openapi\controller;

/**
 * @title 前台登录
 * @description 接口说明
 */
class LoginController extends \cmf\controller\HomeBaseController
{
	private $num_1 = 10;
	private $num_2 = 20;
	private $expire_1 = 60;
	private $expire_2 = 1800;
	private $disable_login_expire_1 = 600;
	private $disable_login_expire_2 = 2592000;
	protected function initialize()
	{
		$this->lang = home_system_lang();
	}
	/**
	 * @title 登录
	 * @description 
	 * return
	 * @author xiong
	 * @url /login_api
	 * @method POST
	 */
	public function loginAPI()
	{
		if (!$this->request->isPost()) {
			return json(["status" => 400, "msg" => "illegal parameter"]);
		}
		if (!configuration("allow_resource_api")) {
			return json(["status" => 400, "msg" => "API function is not enabled yet"]);
		}
		$param = $this->request->param();
		$clients = \think\Db::name("clients");
		$clientIP = get_client_ip6();
		if (empty($param["account"]) || empty($param["password"])) {
			return json(["status" => 400, "msg" => "parameter cannot be empty"]);
		}
		$clients->field("id,api_password,api_open,phonenumber,email,password,status,username");
		if (strpos($param["account"], "@") !== false) {
			$clients->where("email", $param["account"]);
		} else {
			$clients->where("phonenumber", $param["account"]);
		}
		$client = $clients->find();
		if (empty($client)) {
			return json(["status" => 400, "msg" => "Account does not exist"]);
		}
		if ($client["status"] != 1) {
			return json(["status" => 400, "msg" => "The account has been deactivated/closed, please contact the administrator"]);
		}
		if ($client["api_open"] != 1) {
			return json(["status" => 400, "msg" => "The account has not yet enabled the API docking function"]);
		}
		$api_password = aesPasswordDecode($client["api_password"]);
		if (md5($param["password"]) != md5($api_password)) {
			return json(["status" => 400, "msg" => "Account or API key error"]);
		}
		$userinfo["id"] = $client["id"];
		$userinfo["username"] = $client["username"];
		$jwt = createJwt($userinfo, 7200);
		return json(["jwt" => $jwt, "status" => 200, "msg" => "login successful"]);
	}
	/**
	 * @title 获取支持的登录方式
	 * @description 获取支持的登录方式
	 * return
	 * @author xiong
	 * @url /login
	 * @method GET
	 */
	public function loginPage()
	{
		$login_email = intval(configuration("allow_login_email"));
		$login_phone = intval(configuration("allow_login_phone"));
		$login_id = intval(configuration("allow_id"));
		$is_captcha = intval(configuration("is_captcha"));
		if (!empty($is_captcha)) {
			$login_phone_captcha = intval(configuration("allow_login_phone_captcha"));
			$login_email_captcha = intval(configuration("allow_login_email_captcha"));
			$login_id_captcha = intval(configuration("allow_login_email_captcha"));
			$login_code_captcha = intval(configuration("allow_login_code_captcha"));
		}
		if ($login_email) {
			$dataLogin["login_email"]["captcha"] = intval($login_email_captcha);
		}
		if ($login_phone) {
			$dataLogin["login_phone"]["captcha"] = intval($login_phone_captcha);
		}
		if ($login_phone) {
			$dataLogin["login_phone_captcha"]["captcha"] = intval($login_code_captcha);
		}
		if ($login_id) {
			$dataLogin["login_id"]["captcha"] = intval($login_id_captcha);
		}
		if (!!sendGlobal()) {
			$dataLogin["sms_country"] = \getCountryCode();
		} else {
			if ($login_phone == 1) {
				$dataLogin["sms_country"][0]["phone_code"] = "+86";
				$dataLogin["sms_country"][0]["link"] = "+86(中国)";
			}
		}
		if (\is_profession()) {
			$plugins = [];
			$list = \think\Db::name("plugin")->where(["module" => "oauth", "status" => 1])->order("order", "asc")->select()->toArray();
			$oauth = array_map("basename", glob(CMF_ROOT . "modules/oauth/*", GLOB_ONLYDIR));
			$oauth2 = array_map("basename", glob(WEB_ROOT . "plugins/oauth/*", GLOB_ONLYDIR));
			foreach ($list as $k => $plugin) {
				if (!$plugin["config"]) {
					continue;
				}
				$plugins[$k]["name"] = $plugin["title"];
				$plugins[$k]["dirName"] = $plugin["name"];
				$plugins[$k]["url"] = $this->domain . "/oauth/url/" . $plugin["name"];
				$class = \cmf_get_oauthPlugin_class_shd($plugin["name"], "oauth");
				$obj = new $class();
				$meta = $obj->meta();
				if (in_array($plugin["name"], $oauth)) {
					$oauth_img = CMF_ROOT . "modules/oauth/{$plugin["name"]}/{$meta["logo_url"]}";
				}
				if (in_array($plugin["name"], $oauth2)) {
					$oauth_img = WEB_ROOT . "plugins/oauth/{$plugin["name"]}/{$meta["logo_url"]}";
				}
				if (stripos($oauth_img, ".svg") === false) {
					$plugins[$k]["img"] = base64EncodeImage($oauth_img);
				} else {
					$plugins[$k]["img"] = svgBase64EncodeImage($oauth_img);
				}
			}
			$dataLogin["Oauth"] = $plugins;
		}
		return json(["status" => 200, "data" => $dataLogin]);
	}
	/**
	 * @title 登录
	 * @description 
	 * return
	 * @author xiong
	 * @url /login
	 * @method POST
	 */
	public function login()
	{
		if (!$this->request->isPost()) {
			return json(["status" => 400, "msg" => "illegal parameter"]);
		}
		$param = $this->request->param();
		$clients = \think\Db::name("clients");
		$second_verify = false;
		$login = false;
		$clientIP = get_client_ip6();
		$ip_restriction = $this->login_get($clientIP);
		if ($ip_restriction["status"] == 400) {
			return json($ip_restriction);
		}
		if ($param["phone"]) {
			$data = $param["phone"];
			if (!configuration("allow_login_phone")) {
				return json(["status" => 400, "msg" => "Mobile login is not enabled"]);
			}
			$validate = new \think\Validate(["phone" => "require|length:4,11", "password" => "require|min:6|max:32"]);
			$validate->message(["phone.require" => "Mobile number cannot be empty", "phone.length" => "Phone length is 4-11 digits", "password.require" => "Password required", "password.min" => "Password must be at least 6 characters", "password.max" => "Password up to 32 characters"]);
			if (!$validate->check($data)) {
				return json(["status" => 400, "msg" => $validate->getError()]);
			}
			if (!!configuration("is_captcha") && !!configuration("allow_login_phone_captcha")) {
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
			$client = $clients->field("id,phone_code,phonenumber,email,password,second_verify,status,username")->where("phonenumber", $data["phone"])->find();
			if (empty($client)) {
				return json(["status" => 400, "msg" => "Account does not exist"]);
			}
			if ($client["status"] != 1) {
				return json(["status" => 400, "msg" => "The account has been deactivated/closed, please contact the administrator"]);
			}
			$data["phone_code"] = !empty($data["phone_code"]) ? str_replace("+", "", $data["phone_code"]) : "86";
			if ($client["phone_code"] != $data["phone_code"]) {
				return json(["status" => 400, "msg" => "wrong phone area code"]);
			}
			$comparePasswordResult = cmf_compare_password($data["password"], $client["password"]);
			$active_log_final = "手机密码";
			$account = $client["phonenumber"];
		} elseif ($param["phone_code"]) {
			$data = $param["phone_code"];
			if (!configuration("allow_login_phone")) {
				return json(["status" => 400, "msg" => "Mobile login is not enabled"]);
			}
			$validate = new \think\Validate(["phone" => "require|length:4,11", "code" => "require"]);
			$validate->message(["phone.require" => "Mobile number cannot be empty", "code.require" => "verification code must be filled"]);
			if (!$validate->check($data)) {
				return json(["status" => 400, "msg" => $validate->getError()]);
			}
			if (!!configuration("is_captcha") && !!configuration("allow_login_code_captcha")) {
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
			$client = $clients->field("id,phone_code,phonenumber,email,password,second_verify,status,username")->where("phonenumber", $data["phone"])->find();
			if (empty($client)) {
				return json(["status" => 400, "msg" => "Account does not exist"]);
			}
			if ($client["status"] != 1) {
				return json(["status" => 400, "msg" => "The account has been deactivated/closed, please contact the administrator"]);
			}
			if (\think\facade\Cache::get("verification_code_login_phone_code" . $client["phone_code"] . $data["phone"]) != $data["code"]) {
				return json(["status" => 400, "msg" => "Mobile verification code error"]);
			}
			$comparePasswordResult = true;
			$active_log_final = "手机验证码";
			$account = $client["phonenumber"];
		} elseif ($param["email"]) {
			$data = $param["email"];
			if (!configuration("allow_login_email")) {
				return json(["status" => 400, "msg" => "Email login is not enabled"]);
			}
			$validate = new \think\Validate(["email" => "require", "password" => "require|min:6|max:32"]);
			$validate->message(["email.require" => "Email can not be empty", "password.require" => "Password required", "password.min" => "Password must be at least 6 characters", "password.max" => "Password up to 32 characters"]);
			if (!$validate->check($data)) {
				return json(["status" => 400, "msg" => $validate->getError()]);
			}
			if (!!configuration("is_captcha") && !!configuration("allow_login_email_captcha")) {
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
			$client = $clients->field("id,phone_code,phonenumber,email,password,second_verify,status,username")->where("email", $data["email"])->find();
			if (empty($client)) {
				return json(["status" => 400, "msg" => "Account does not exist"]);
			}
			if ($client["status"] != 1) {
				return json(["status" => 400, "msg" => "The account has been deactivated/closed, please contact the administrator"]);
			}
			$comparePasswordResult = cmf_compare_password($data["password"], $client["password"]);
			$active_log_final = "邮箱密码";
			$account = $client["email"];
		} elseif ($param["id"]) {
			$data = $param["id"];
			if (!configuration("allow_id")) {
				return json(["status" => 400, "msg" => "ID login function is not enabled"]);
			}
			$validate = new \think\Validate(["id" => "require", "password" => "require|min:6|max:32"]);
			$validate->message(["id.require" => "ID不能为空", "password.require" => "Password required", "password.min" => "Password must be at least 6 characters", "password.max" => "Password up to 32 characters"]);
			if (!$validate->check($data)) {
				return json(["status" => 400, "msg" => $validate->getError()]);
			}
			if (!!configuration("is_captcha") && !!configuration("allow_login_email_captcha")) {
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
			$client = $clients->field("id,phone_code,phonenumber,email,password,second_verify,status,username")->where("id", $data["id"])->find();
			if (empty($client)) {
				return json(["status" => 400, "msg" => "Account does not exist"]);
			}
			if ($client["status"] != 1) {
				return json(["status" => 400, "msg" => "The account has been deactivated/closed, please contact the administrator"]);
			}
			$comparePasswordResult = cmf_compare_password($data["password"], $client["password"]);
			$active_log_final = "ID密码";
			$account = $client["id"];
		}
		if ($comparePasswordResult) {
			$second_verify_action_home = explode(",", configuration("second_verify_action_home"));
			if (in_array("login", $second_verify_action_home)) {
				$second_verify_login = 1;
			} else {
				$second_verify_login = 0;
			}
			$second_verify_home = configuration("second_verify_home");
			$verification_success_1 = \think\facade\Cache::get("verification_success" . $client["phonenumber"]);
			$verification_success_2 = \think\facade\Cache::get("verification_success" . $client["email"]);
			if (empty($param["phone_code"]) && $client["second_verify"] == 1 && !empty($second_verify_home) && !empty($second_verify_login)) {
				if (empty($verification_success_1) && empty($verification_success_2)) {
					$result["second_verify"] = $this->secondVerifyPage($client);
					return json(["status" => 400, "msg" => $result]);
				}
			}
			\think\facade\Cache::rm("code_" . $data["idtoken"]);
			$_data = ["lastlogin" => time(), "lastloginip" => $clientIP];
			\think\Db::name("clients")->where("id", $client["id"])->update($_data);
			$userinfo["id"] = $client["id"];
			$userinfo["username"] = $client["username"];
			login_sms_remind($client);
			active_log_final(sprintf($this->lang["User_home_login"], $active_log_final, $account, $client["id"]), $client["id"], 1, 0, 2);
			updateUgp($client["id"]);
			$jwt = createJwt($userinfo, 7200);
			hook("client_login", ["uid" => $client["id"], "name" => $client["username"], "ip" => $clientIP, "jwt" => $jwt]);
			userSetCookie($jwt);
			return json(["jwt" => $jwt, "status" => 200, "msg" => "login successful"]);
		}
		$this->login_inc($clientIP);
		return json(["status" => 400, "msg" => "Incorrect username or password"]);
	}
	/**
	 * @title 获取支持的注册方式
	 * @description 
	 * return
	 * @author xiong
	 * @url /register
	 * @method GET
	 */
	public function registerPage()
	{
		$register_email = intval(configuration("allow_register_email"));
		$register_email_register_code = intval(configuration("allow_email_register_code"));
		$register_phone = intval(configuration("allow_register_phone"));
		$is_captcha = intval(configuration("is_captcha"));
		if (!empty($is_captcha)) {
			$register_email_captcha = intval(configuration("allow_register_phone_captcha"));
			$register_phone_captcha = intval(configuration("allow_register_email_captcha"));
		}
		if ($register_email) {
			$dataRegister["register_email"]["captcha"] = intval($register_email_captcha);
			$dataRegister["register_email"]["code"] = $register_email_register_code;
		}
		if ($register_phone) {
			$dataRegister["register_phone"]["captcha"] = intval($register_phone_captcha);
			$dataRegister["register_phone"]["code"] = 1;
		}
		if (!!sendGlobal()) {
			$dataRegister["sms_country"] = \getCountryCode();
		} else {
			if ($dataRegister["allow_login_phone"] == 1) {
				$dataRegister["sms_country"][0]["phone_code"] = "+86";
				$dataRegister["sms_country"][0]["link"] = "+86(中国)";
			}
		}
		if (configuration("sale_reg_setting") == 2) {
			$dataRegister["sale"] = db("user")->field("id,user_nickname,user_email")->where("is_sale", 1)->where("sale_is_use", 1)->select()->toArray();
		}
		$customfields = new \app\common\logic\Customfields();
		$fields = $customfields->getClientCustomField();
		$dataRegister["custom_fields"] = $fields;
		$dataRegister["system_fields"] = configuration("login_register_custom_require") ? json_decode(configuration("login_register_custom_require"), true) : [];
		return json(["status" => 200, "msg" => "SUCCESS MESSAGE", "data" => $dataRegister]);
	}
	/**
	 * @title 注册
	 * @description 
	 * return
	 * @author xiong
	 * @url /register
	 * @method POST
	 */
	public function register(\think\Request $request)
	{
		if (!$this->request->isPost()) {
			return json(["status" => 400, "msg" => "illegal parameter"]);
		}
		$param = $this->request->param();
		if ($param["phone"]) {
			$data = $param["phone"];
			if (!configuration("allow_register_phone")) {
				return json(["status" => 400, "msg" => "Mobile phone registration is not turned on"]);
			}
			$validate = new \think\Validate(["phone" => "require|length:4,11", "code" => "require", "password" => "require|min:6|max:32", "qq" => "max:20", "username" => "max:20", "companyname" => "max:50", "address1" => "max:100"]);
			$validate->message(["phone.require" => "Mobile number cannot be empty", "phone.length" => "Phone length is 4-11 digits", "password.require" => "Password required", "password.min" => "Password must be at least 6 characters", "password.max" => "Password up to 32 characters", "code.require" => "Verification code required", "qq.max" => "qq no more than 20 characters", "username.max" => "Username must not exceed 20 characters", "companyname.max" => "Company name no more than 20 characters", "address1.max" => "Address no more than 20 characters"]);
			if (!$validate->check($data)) {
				return json(["status" => 400, "msg" => $validate->getError()]);
			}
			if (!!configuration("is_captcha") && !!configuration("allow_register_phone_captcha")) {
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
			if (empty($data["phone_code"])) {
				$account = "86" . $data["phone"];
			} else {
				$account = str_replace("+", "", $data["phone_code"]) . $data["phone"];
			}
			if (\think\facade\Cache::get("verification_code_register_phone" . $account) != $data["code"]) {
				return json(["status" => 400, "msg" => "Verification code error"]);
			}
			$client = \think\Db::name("clients")->field("phone_code,phonenumber,email,password,second_verify,status")->where("phonenumber", $data["phone"])->find();
			if (!empty($client)) {
				return json(["status" => 400, "msg" => "This account already exists"]);
			}
		} elseif ($param["email"]) {
			$data = $param["email"];
			if (!configuration("allow_register_email")) {
				return json(["status" => 400, "msg" => "Email registration is not opened"]);
			}
			$validate = new \think\Validate(["email" => "require", "password" => "require|min:6|max:32", "qq" => "max:20", "username" => "max:20", "companyname" => "max:50", "address1" => "max:100"]);
			$validate->message(["email.require" => "Email can not be empty", "password.require" => "Password required", "password.min" => "Password must be at least 6 characters", "password.max" => "Password up to 32 characters", "code.require" => "Verification code required", "qq.max" => "qq no more than 20 characters", "username.max" => "Username must not exceed 20 characters", "companyname.max" => "Company name no more than 20 characters", "address1.max" => "Address no more than 20 characters"]);
			if (!$validate->check($data)) {
				return json(["status" => 400, "msg" => $validate->getError()]);
			}
			if (!!configuration("is_captcha") && !!configuration("allow_register_phone_captcha")) {
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
			if (\think\facade\Cache::get("verification_code_register_email" . $data["email"]) != $data["code"]) {
				return json(["status" => 400, "msg" => "Verification code error"]);
			}
			$client = \think\Db::name("clients")->field("phone_code,phonenumber,email,password,second_verify,status")->where("phonenumber", $data["email"])->find();
			if (!empty($client)) {
				return json(["status" => 400, "msg" => "This account already exists"]);
			}
		}
		$login_register = configuration("login_register_custom_require") ? json_decode(configuration("login_register_custom_require"), true) : [];
		if (!empty($login_register[0])) {
			$allow = config("login_register_custom_require");
			foreach ($login_register as $v) {
				if ($v["require"] && empty($data["system_fields"][$v["name"]])) {
					return json(["status" => 400, "msg" => $allow[$v["name"]] . "必填"]);
				}
			}
		}
		if (isset($data["custom_fields"]) && is_array($data["custom_fields"]) && !empty($data["custom_fields"])) {
			$custom_fields = $data["custom_fields"];
			foreach ($custom_fields as $k => $v) {
				$tmp = \think\Db::name("customfields")->where("id", $k)->find();
				if (empty($tmp)) {
					return json(["status" => 400, "msg" => "Custom field parameter error"]);
				}
			}
		}
		$sale_id = $this->getSalerId($data["sale_id"]);
		$data_clients = ["username" => !empty($data["system_fields"]["username"]) ? $data["system_fields"]["username"] : $data["phone"] ?? substr($data["email"], 0, strpos($data["email"], "@")), "email" => !empty($data["email"]) ? $data["email"] : "", "avatar" => "用户头像2-" . rand(10, 20) . ".jpg", "phone_code" => !empty($data["phone_code"]) ? $data["phone_code"] : "86", "phonenumber" => !empty($data["phone"]) ? $data["phone"] : "", "currency" => getDefaultCurrencyId(), "password" => !empty($data["password"]) ? cmf_password($data["password"]) : "", "lastloginip" => get_client_ip(0, true), "create_time" => time(), "lastlogin" => time(), "status" => 1, "defaultgateway" => gateway_list()[0]["name"] ?? "", "sale_id" => !empty($sale_id) ? $sale_id : 0, "qq" => $data["system_fields"]["qq"] ?? "", "companyname" => $data["system_fields"]["companyname"] ?? "", "address1" => $data["system_fields"]["address1"] ?? "", "api_password" => aesPasswordEncode(randStrToPass(12, 0)), "marketing_emails_opt_in" => configuration("marketing_emails_opt_in"), "api_open" => 0];
		$userId = \think\Db::name("clients")->insertGetId($data_clients);
		if (!empty($data["custom_fields"])) {
			foreach ($data["custom_fields"] as $k => $v) {
				\think\Db::name("customfieldsvalues")->insert(["fieldid" => $k, "relid" => $userId, "value" => $v, "create_time" => time()]);
			}
		}
		if ($param["email"]) {
			$email = new \app\common\logic\Email();
			$email->sendEmailBase($userId, "注册成功", "general", true);
		} elseif ($param["phone"]) {
			$message_template_type = array_column(config("message_template_type"), "id", "name");
			$sms = new \app\common\logic\Sms();
			$client = check_type_is_use($message_template_type[strtolower("Registration_Success")], $userId, $sms);
			if ($client) {
				$params = ["system_companyname" => configuration("company_name"), "username" => $data["system_fields"]["username"]];
				$ret = sendmsglimit($client["phonenumber"]);
				if ($ret["status"] !== 400) {
					$ret = $sms->sendSms($message_template_type[strtolower("Registration_Success")], $client["phone_code"] . $client["phonenumber"], $params, false, $userId);
					if ($ret["status"] == 200) {
						$data_sendmsglimit = ["ip" => get_client_ip6(), "phone" => $client["phonenumber"], "time" => time()];
						\think\Db::name("sendmsglimit")->insertGetId($data_sendmsglimit);
					}
				}
			}
		}
		update_aff($userId, 1);
		updateUgp($userId);
		$userinfo["id"] = $userId;
		$userinfo["username"] = $data["system_fields"]["username"];
		$jwt = createJwt($userinfo);
		$userinfo["jwt"] = $jwt;
		\think\facade\Cache::rm("code_" . $data["idtoken"]);
		return json(["jwt" => $jwt, "status" => 200, "msg" => "registration success"]);
	}
	/**
	 * @title 获取支持的找回密码方式
	 * @description 获取支持的找回密码方式
	 * return
	 * @author xiong
	 * @url /pwreset
	 * @method GET
	 */
	public function pwresetPage()
	{
		$login_email = intval(configuration("allow_login_email"));
		$login_phone = intval(configuration("allow_login_phone"));
		$is_captcha = intval(configuration("is_captcha"));
		if (!empty($is_captcha)) {
			$allow_phone_forgetpwd_captcha = intval(configuration("allow_phone_forgetpwd_captcha"));
			$allow_email_forgetpwd_captcha = intval(configuration("allow_email_forgetpwd_captcha"));
		}
		if ($login_email) {
			$dataPwreset["pwreset_email"]["captcha"] = $allow_email_forgetpwd_captcha;
		}
		if ($login_phone) {
			$dataPwreset["pwreset_phone"]["captcha"] = $allow_phone_forgetpwd_captcha;
		}
		if (!!sendGlobal()) {
			$dataPwreset["sms_country"] = \getCountryCode();
		} else {
			if ($login_phone == 1) {
				$dataPwreset["sms_country"][0]["phone_code"] = "+86";
				$dataPwreset["sms_country"][0]["link"] = "+86(中国)";
			}
		}
		return json(["status" => 200, "data" => $dataPwreset]);
	}
	/**
	 * @title 找回密码
	 * @description 
	 * return
	 * @author xiong
	 * @url /pwreset
	 * @method POST
	 */
	public function pwreset()
	{
		if (!$this->request->isPost()) {
			return json(["status" => 400, "msg" => "illegal parameter"]);
		}
		$param = $this->request->param();
		$clients = \think\Db::name("clients");
		if ($param["phone"]) {
			$data = $param["phone"];
			if (!configuration("allow_login_phone")) {
				return json(["status" => 400, "msg" => "The mobile phone login function is not turned on, and the password cannot be retrieved with the mobile phone"]);
			}
			$client = $clients->field("id,phone_code,phonenumber,email,password,second_verify,status,username")->where("phonenumber", $data["phone"])->find();
			if (empty($client)) {
				return json(["status" => 400, "msg" => "Account does not exist"]);
			}
			if ($client["status"] != 1) {
				return json(["status" => 400, "msg" => "The account has been deactivated/closed, please contact the administrator"]);
			}
			$validate = new \think\Validate(["phone" => "require|length:4,11", "password" => "require|min:6|max:32", "code" => "require"]);
			$validate->message(["phone.require" => "Mobile number cannot be empty", "phone.length" => "Phone length is 4-11 digits", "password.require" => "Password required", "password.min" => "Password must be at least 6 characters", "password.max" => "Password up to 32 characters", "code.require" => "Verification code required"]);
			if (!$validate->check($data)) {
				return json(["status" => 400, "msg" => $validate->getError()]);
			}
			if (!!configuration("is_captcha") && !!configuration("allow_phone_forgetpwd_captcha")) {
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
			if (empty($data["phone_code"])) {
				$account_phone = "86" . $data["phone"];
			} else {
				$account_phone = str_replace("+", "", $data["phone_code"]) . $data["phone"];
			}
			if (\think\facade\Cache::get("verification_code_pwreset_phone" . $account_phone) != $data["code"]) {
				return json(["status" => 400, "msg" => "Verification code error"]);
			}
			$active_log_final = "手机";
			$account = $data["phone"];
		} elseif ($param["email"]) {
			$data = $param["email"];
			if (!configuration("allow_login_email")) {
				return json(["status" => 400, "msg" => "The email login function is not turned on, and the email cannot be used to retrieve the password"]);
			}
			$client = $clients->field("id,phone_code,phonenumber,email,password,second_verify,status,username")->where("email", $data["email"])->find();
			if (empty($client)) {
				return json(["status" => 400, "msg" => "Account does not exist"]);
			}
			if ($client["status"] != 1) {
				return json(["status" => 400, "msg" => "The account has been deactivated/closed, please contact the administrator"]);
			}
			$validate = new \think\Validate(["email" => "require", "password" => "require|min:6|max:32"]);
			$validate->message(["email.require" => "Mobile number cannot be empty", "password.require" => "Password required", "password.min" => "Password must be at least 6 characters", "password.max" => "Password up to 32 characters", "code.require" => "Verification code required"]);
			if (!$validate->check($data)) {
				return json(["status" => 400, "msg" => $validate->getError()]);
			}
			if (!!configuration("is_captcha") && !!configuration("allow_email_forgetpwd_captcha")) {
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
			if (\think\facade\Cache::get("verification_code_pwreset_email" . $data["email"]) != $data["code"]) {
				return json(["status" => 400, "msg" => "Verification code error"]);
			}
			$active_log_final = "邮箱";
			$account = $data["email"];
		}
		$_data = ["update_time" => time(), "password" => cmf_password($data["password"])];
		\think\Db::name("clients")->where("id", $client["id"])->update($_data);
		active_log_final(sprintf($this->lang["User_home_reset_pass"], $active_log_final, $account), $client["id"], 0, 0, 2);
		hook("client_reset_password", ["uid" => $result["id"], "password" => html_entity_decode($data["password"], ENT_QUOTES)]);
		\think\facade\Cache::rm("code_" . $data["idtoken"]);
		return json(["status" => 200, "msg" => "Password reset successful"]);
	}
	/**
	 * @title 二次验证信息
	 * @description 接口说明:二次验证信息
	 */
	private function secondVerifyPage($client)
	{
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
		return $second_verify;
	}
	public function login_inc($ip)
	{
		$key = "cwxt_home_login_" . $ip . "_";
		$key_1 = $key . "_1";
		if (\think\facade\Cache::has($key_1)) {
			\think\facade\Cache::inc($key_1);
		} else {
			\think\facade\Cache::set($key_1, 1, $this->expire_1);
		}
		$key_2 = $key . "_2";
		if (\think\facade\Cache::has($key_2)) {
			\think\facade\Cache::inc($key_2);
		} else {
			\think\facade\Cache::set($key_2, 1, $this->expire_2);
		}
		return true;
	}
	public function login_get($ip)
	{
		$key = "cwxt_home_login_" . $ip . "_";
		$key_1_block = $key . "_1_block";
		$key_2_block = $key . "_2_block";
		if (\think\facade\Cache::has($key_2_block)) {
			return ["status" => 400, "msg" => lang("login_block")];
		}
		if (\think\facade\Cache::has($key_1_block)) {
			return ["status" => 400, "msg" => lang("frequent_login")];
		}
		$key_1 = $key . "_1";
		$key_2 = $key . "_2";
		$num_1 = \think\facade\Cache::get($key_1);
		$num_2 = \think\facade\Cache::get($key_2);
		if ($this->num_1 <= $num_1) {
			\think\facade\Cache::set($key_1_block, 1, $this->disable_login_expire_1);
			return ["status" => 400, "msg" => lang("frequent_login")];
		}
		if ($this->num_2 <= $num_2) {
			\think\facade\Cache::set($key_2_block, 1, $this->disable_login_expire_2);
			return ["status" => 400, "msg" => lang("login_block")];
		}
		return ["status" => 200];
	}
	/**
	 * 时间 2020/6/24 11:25
	 * @title 设定销售员
	 * @desc 设定销售员
	 * @param name:sale_id type:int  require:0  default:1 other: desc:销售员id
	 * @url set_saler
	 * @method  POST
	 * @author lgd
	 * @version v1
	 */
	public function getSalerId($sale_id)
	{
		if (!$sale_id) {
			$sale_reg_setting = configuration("sale_reg_setting");
			if ($sale_reg_setting == 0) {
				return json(["status" => 200, "msg" => "设定成功"]);
			} elseif ($sale_reg_setting == 1) {
				$sale_auto_setting = configuration("sale_auto_setting");
				if ($sale_auto_setting == 1) {
					$data = db("user")->field("id,user_nickname,user_email")->where("is_sale", 1)->select()->toArray();
					$num = rand(0, count($data) - 1);
					if (count($data) == 1) {
						$num = 0;
					}
					$sale_id = $data[$num]["id"];
				} else {
					$setsalerinc = configuration("setsalerinc") ?? 0;
					$data = db("user")->field("id")->where("is_sale", 1)->where("id", ">", $setsalerinc)->order("id", "asc")->find();
					if (empty($data)) {
						$data = db("user")->field("id")->where("is_sale", 1)->order("id", "asc")->find();
					}
					$sale_id = $data["id"];
					updateConfiguration("setsalerinc", $sale_id);
				}
			}
		}
		return $sale_id;
	}
}