<?php

namespace app\home\model;

class ClientsModel extends \think\Model
{
	protected $pk = "id";
	protected $type = ["more" => "array"];
	public $lang = "";
	private $num_1 = 10;
	private $num_2 = 20;
	private $expire_1 = 60;
	private $expire_2 = 1800;
	private $disable_login_expire_1 = 600;
	private $disable_login_expire_2 = 2592000;
	protected function initialize()
	{
		$this->lang = get_system_langs();
	}
	public function register($user, $type)
	{
		switch ($type) {
			case 1:
				$result = \think\Db::name("clients")->where("phone_code", $user["phone_code"])->where("phonenumber", $user["phonenumber"])->find();
				break;
			case 2:
				$result = \think\Db::name("clients")->where("email", $user["email"])->find();
				break;
			default:
				$result = 0;
		}
		$status = 1;
		if (empty($result)) {
			$data = ["username" => !empty($user["username"]) ? $user["username"] : $user["phonenumber"] ?? substr($user["email"], 0, strpos($user["email"], "@")), "email" => $user["email"] ?? "", "avatar" => $user["avatar"] ?? "用户头像2-" . rand(10, 20) . ".jpg", "phone_code" => $user["phone_code"] ?? "86", "phonenumber" => $user["phonenumber"] ?? "", "currency" => getDefaultCurrencyId(), "password" => !empty($user["password"]) ? cmf_password($user["password"]) : "", "lastloginip" => get_client_ip(0, true), "create_time" => time(), "lastlogin" => time(), "status" => $status, "defaultgateway" => gateway_list()[0]["name"] ?? "", "sale_id" => $user["sale_id"] ?? 0, "qq" => $user["qq"] ?? "", "companyname" => $user["companyname"] ?? "", "address1" => $user["address1"] ?? "", "api_password" => aesPasswordEncode(randStrToPass(12, 0)), "marketing_emails_opt_in" => configuration("marketing_emails_opt_in"), "api_open" => 0];
			$userId = \think\Db::name("clients")->insertGetId($data);
			if (!empty($user["fields"])) {
				foreach ($user["fields"] as $k => $v) {
					\think\Db::name("customfieldsvalues")->insert(["fieldid" => $k, "relid" => $userId, "value" => $v, "create_time" => time()]);
				}
			}
			$data = \think\Db::name("clients")->where("id", $userId)->find();
			cmf_update_current_user($data);
			if (!empty($user["phonenumber"])) {
				cache("registertel" . $user["phonenumber"], null);
				active_log_final(sprintf($this->lang["User_home_regist"], "手机", $user["phonenumber"]), $userId, 0, 0, 2);
			}
			if (!empty($user["email"])) {
				cache("registeremail" . $user["email"], null);
				active_log_final(sprintf($this->lang["User_home_regist"], "邮箱", $user["email"]), $userId, 0, 0, 2);
			}
			$email = new \app\common\logic\Email();
			$email->sendEmailBase($userId, "注册成功", "general", true);
			$message_template_type = array_column(config("message_template_type"), "id", "name");
			$sms = new \app\common\logic\Sms();
			$client = check_type_is_use($message_template_type[strtolower("Registration_Success")], $userId, $sms);
			if ($client) {
				$params = ["system_companyname" => configuration("company_name"), "username" => $data["username"]];
				$ret = sendmsglimit($client["phonenumber"]);
				if ($ret["status"] !== 400) {
					$ret = $sms->sendSms($message_template_type[strtolower("Registration_Success")], $client["phone_code"] . $client["phonenumber"], $params, false, $userId);
					if ($ret["status"] == 200) {
						$data = ["ip" => get_client_ip6(), "phone" => $client["phonenumber"], "time" => time()];
						\think\Db::name("sendmsglimit")->insertGetId($data);
					}
				}
			}
			update_aff($userId, 1);
			updateUgp($userId);
			$userinfo["id"] = $userId;
			$userinfo["username"] = $result["username"];
			$jwt = createJwt($userinfo);
			$userinfo["jwt"] = $jwt;
			session("user", $userinfo);
			return json(["jwt" => $jwt, "status" => 200, "msg" => "注册成功"]);
		}
		return json(["status" => 400, "msg" => "此账户已存在"]);
	}
	public function mobileVerify($user, $is_market = false)
	{
		$result = $this->where("phonenumber", $user["phonenumber"])->find();
		if (!empty($result)) {
			if ($result["status"] != 1) {
				return json(["status" => 400, "msg" => "该帐号已停用/关闭，请联系管理员处理"]);
			}
			$comparePasswordResult = cmf_compare_password($user["password"], $result["password"]);
			$hookParam = ["user" => $user, "compare_password_result" => $comparePasswordResult];
			hook_one("user_login_start", $hookParam);
			$clientIP = get_client_ip(0, true);
			if ($comparePasswordResult) {
				$action = "login";
				$mobile = $result["phonenumber"];
				$email = $result["email"];
				if (isSecondVerify($action, false, $result["id"]) && !$is_market) {
					$code = $user["code"];
					if (empty($code)) {
						return jsons(["status" => 400, "msg" => "请输入验证码"]);
					}
					if ($code != cache($action . "_" . $mobile) && $code != cache($action . "_" . $email)) {
						return json(["status" => 400, "msg" => "验证码错误"]);
					}
				}
				cache($action . "_" . $mobile, null);
				cache($action . "_" . $email, null);
				session("user", $result->toArray());
				$data = ["lastlogin" => time(), "lastloginip" => $clientIP];
				$this->where("id", $result["id"])->update($data);
				$userinfo["id"] = $result["id"];
				$userinfo["username"] = $result["username"];
				$param["id"] = $result["id"];
				$param["username"] = $result["username"];
				login_sms_remind($result);
				active_log_final(sprintf($this->lang["User_home_login"], "手机密码", $user["phonenumber"], $userinfo["id"]), $userinfo["id"], 1, 0, 2);
				updateUgp($result["id"]);
				hook("client_login", ["uid" => $result["id"], "name" => $result["username"], "ip" => $data["lastloginip"], "jwt" => createJwt($userinfo)]);
				if ($is_market) {
					return ["jwt" => createJwt($userinfo, 604800), "uid" => $userinfo["id"], "username" => $userinfo["username"], "status" => 200, "msg" => "登录成功"];
				} else {
					if (file_exists(CMF_ROOT . "app/res/common.php")) {
						$jwt = createJwt($userinfo);
						userSetCookie($jwt);
						return json(["jwt" => $jwt, "status" => 200, "msg" => "登录成功"]);
					} else {
						return json(["jwt" => createJwt($userinfo), "status" => 200, "msg" => "登录成功"]);
					}
				}
			}
			$this->login_inc($clientIP);
			return json(["status" => 400, "msg" => "账号或密码错误"]);
		} else {
			$r = $result;
			$result = \think\Db::name("contacts")->field("con.id as contactid,con.uid,con.username,con.avatar,con.password,con.status")->alias("con")->leftJoin("clients c", "c.id=con.uid")->where("con.email", $user["phonenumber"])->where("con.status", 1)->where("c.status", 1)->find();
			if (!empty($result)) {
				$comparePasswordResult = cmf_compare_password($user["password"], $result["password"]);
				$hookParam = ["user" => $user, "compare_password_result" => $comparePasswordResult];
				hook_one("user_login_start", $hookParam);
				$clientIP = get_client_ip(0, true);
				if ($comparePasswordResult) {
					$data = ["lastlogin" => time(), "lastloginip" => $clientIP];
					\think\Db::name("contacts")->where("id", $result["id"])->update($data);
					$userinfo["contactid"] = $result["contactid"];
					$userinfo["id"] = $result["uid"];
					$userinfo["username"] = $result["username"];
					$param["id"] = $result["id"];
					$param["username"] = $result["username"];
					login_sms_remind($result);
					active_log_final(sprintf($this->lang["User_home_login"], "手机密码", $user["phonenumber"], $userinfo["id"]), $userinfo["id"], 1, 0, 2);
					updateUgp($r["id"]);
					if ($is_market) {
						return ["jwt" => createJwt($userinfo, 604800), "uid" => $userinfo["id"], "username" => $userinfo["username"], "status" => 200, "msg" => "登录成功"];
					} else {
						if (file_exists(CMF_ROOT . "app/res/common.php")) {
							$jwt = createJwt($userinfo);
							userSetCookie($jwt);
							return json(["jwt" => $jwt, "status" => 200, "msg" => "登录成功"]);
						} else {
							return json(["jwt" => createJwt($userinfo), "status" => 200, "msg" => "登录成功"]);
						}
					}
				}
				$this->login_inc($clientIP);
				return json(["status" => 400, "msg" => "登录失败"]);
			}
		}
		$hookParam = ["user" => $user, "compare_password_result" => false];
		hook_one("user_login_start", $hookParam);
		return json(["status" => 400, "msg" => "用户未注册"]);
	}
	public function idVerify($user, $is_market = false)
	{
		$result = $this->where("id", $user["id"])->find();
		if (!empty($result)) {
			if ($result["status"] != 1) {
				return json(["status" => 400, "msg" => "该帐号已停用/关闭，请联系管理员处理"]);
			}
			$comparePasswordResult = cmf_compare_password($user["password"], $result["password"]);
			$hookParam = ["user" => $user, "compare_password_result" => $comparePasswordResult];
			hook_one("user_login_start", $hookParam);
			$clientIP = get_client_ip(0, true);
			if ($comparePasswordResult) {
				session_start();
				$action = "login";
				$mobile = $result["phonenumber"];
				$id = $result["id"];
				if (isSecondVerify($action, false, $result["id"]) && !$is_market) {
					$code = $user["code"];
					if (empty($code)) {
						return jsons(["status" => 400, "msg" => "请输入验证码"]);
					}
					if ($code != cache($action . "_" . $mobile) && $code != cache($action . "_" . $id)) {
						return json(["status" => 400, "msg" => "验证码错误"]);
					}
				}
				cache($action . "_" . $mobile, null);
				cache($action . "_" . $id, null);
				session("user", $result->toArray());
				$data = ["lastlogin" => time(), "lastloginip" => $clientIP];
				$this->where("id", $result["id"])->update($data);
				$userinfo["id"] = $result["id"];
				$userinfo["username"] = $result["username"];
				$param["id"] = $result["id"];
				$param["username"] = $result["username"];
				login_sms_remind($result);
				active_log_final(sprintf($this->lang["User_home_login"], "id密码", $user["id"], $userinfo["id"]), $userinfo["id"], 1, 0, 2);
				updateUgp($result["id"]);
				session_write_close();
				hook("client_login", ["uid" => $result["id"], "name" => $result["username"], "ip" => $data["lastloginip"], "jwt" => createJwt($userinfo)]);
				if ($is_market) {
					return ["jwt" => createJwt($userinfo, 604800), "uid" => $userinfo["id"], "username" => $userinfo["username"], "status" => 200, "msg" => "登录成功"];
				} else {
					if (file_exists(CMF_ROOT . "app/res/common.php")) {
						$jwt = createJwt($userinfo);
						userSetCookie($jwt);
						return json(["jwt" => $jwt, "status" => 200, "msg" => "登录成功"]);
					} else {
						return json(["jwt" => createJwt($userinfo), "status" => 200, "msg" => "登录成功"]);
					}
				}
			}
			$this->login_inc($clientIP);
			return json(["status" => 400, "msg" => "账号或密码错误"]);
		} else {
			$r = $result;
			$result = \think\Db::name("contacts")->field("con.id as contactid,con.uid,con.username,con.avatar,con.password,con.status")->alias("con")->leftJoin("clients c", "c.id=con.uid")->where("con.email", $user["email"])->where("con.status", 1)->where("c.status", 1)->find();
			if (!empty($result)) {
				$comparePasswordResult = cmf_compare_password($user["password"], $result["password"]);
				$hookParam = ["user" => $user, "compare_password_result" => $comparePasswordResult];
				hook_one("user_login_start", $hookParam);
				$clientIP = get_client_ip(0, true);
				if ($comparePasswordResult) {
					$data = ["lastlogin" => time(), "lastloginip" => $clientIP];
					\think\Db::name("contacts")->where("id", $result["id"])->update($data);
					$userinfo["contactid"] = $result["contactid"];
					$userinfo["id"] = $result["uid"];
					$userinfo["username"] = $result["username"];
					$param["id"] = $result["id"];
					$param["username"] = $result["username"];
					login_sms_remind($result);
					active_log_final(sprintf($this->lang["User_home_login"], "邮件密码", $user["email"], $userinfo["id"]), $userinfo["id"], 1, 0, 2);
					updateUgp($r["id"]);
					if ($is_market) {
						return ["jwt" => createJwt($userinfo, 604800), "uid" => $userinfo["id"], "username" => $userinfo["username"], "status" => 200, "msg" => "登录成功"];
					} else {
						if (file_exists(CMF_ROOT . "app/res/common.php")) {
							$jwt = createJwt($userinfo);
							userSetCookie($jwt);
							return json(["jwt" => $jwt, "status" => 200, "msg" => "登录成功"]);
						} else {
							return json(["jwt" => createJwt($userinfo), "status" => 200, "msg" => "登录成功"]);
						}
					}
				}
				$this->login_inc($clientIP);
				return json(["status" => 400, "msg" => "登录失败"]);
			}
		}
		$hookParam = ["user" => $user, "compare_password_result" => false];
		hook_one("user_login_start", $hookParam);
		return json(["status" => 400, "msg" => "用户未注册"]);
	}
	public function emailVerify($user, $is_market = false)
	{
		$result = $this->where("email", $user["email"])->find();
		if (!empty($result)) {
			if ($result["status"] != 1) {
				return json(["status" => 400, "msg" => "该帐号已停用/关闭，请联系管理员处理"]);
			}
			$comparePasswordResult = cmf_compare_password($user["password"], $result["password"]);
			$hookParam = ["user" => $user, "compare_password_result" => $comparePasswordResult];
			hook_one("user_login_start", $hookParam);
			$clientIP = get_client_ip(0, true);
			if ($comparePasswordResult) {
				$action = "login";
				$mobile = $result["phonenumber"];
				$email = $result["email"];
				if (isSecondVerify($action, false, $result["id"]) && !$is_market) {
					$code = $user["code"];
					if (empty($code)) {
						return jsons(["status" => 400, "msg" => "请输入验证码"]);
					}
					if ($code != cache($action . "_" . $mobile) && $code != cache($action . "_" . $email)) {
						return json(["status" => 400, "msg" => "验证码错误"]);
					}
				}
				cache($action . "_" . $mobile, null);
				cache($action . "_" . $email, null);
				session("user", $result->toArray());
				$data = ["lastlogin" => time(), "lastloginip" => $clientIP];
				$this->where("id", $result["id"])->update($data);
				$userinfo["id"] = $result["id"];
				$userinfo["username"] = $result["username"];
				$param["id"] = $result["id"];
				$param["username"] = $result["username"];
				login_sms_remind($result);
				active_log_final(sprintf($this->lang["User_home_login"], "邮件密码", $user["email"], $userinfo["id"]), $userinfo["id"], 1, 0, 2);
				updateUgp($result["id"]);
				hook("client_login", ["uid" => $result["id"], "name" => $result["username"], "ip" => $data["lastloginip"], "jwt" => createJwt($userinfo)]);
				if ($is_market) {
					return ["jwt" => createJwt($userinfo, 604800), "uid" => $userinfo["id"], "username" => $userinfo["username"], "status" => 200, "msg" => "登录成功"];
				} else {
					if (file_exists(CMF_ROOT . "app/res/common.php")) {
						$jwt = createJwt($userinfo);
						userSetCookie($jwt);
						return json(["jwt" => $jwt, "status" => 200, "msg" => "登录成功"]);
					} else {
						return json(["jwt" => createJwt($userinfo), "status" => 200, "msg" => "登录成功"]);
					}
				}
			}
			$this->login_inc($clientIP);
			return json(["status" => 400, "msg" => "账号或密码错误"]);
		} else {
			$r = $result;
			$result = \think\Db::name("contacts")->field("con.id as contactid,con.uid,con.username,con.avatar,con.password,con.status")->alias("con")->leftJoin("clients c", "c.id=con.uid")->where("con.email", $user["email"])->where("con.status", 1)->where("c.status", 1)->find();
			if (!empty($result)) {
				$comparePasswordResult = cmf_compare_password($user["password"], $result["password"]);
				$hookParam = ["user" => $user, "compare_password_result" => $comparePasswordResult];
				hook_one("user_login_start", $hookParam);
				$clientIP = get_client_ip(0, true);
				if ($comparePasswordResult) {
					$data = ["lastlogin" => time(), "lastloginip" => $clientIP];
					\think\Db::name("contacts")->where("id", $result["id"])->update($data);
					$userinfo["contactid"] = $result["contactid"];
					$userinfo["id"] = $result["uid"];
					$userinfo["username"] = $result["username"];
					$param["id"] = $result["id"];
					$param["username"] = $result["username"];
					login_sms_remind($result);
					active_log_final(sprintf($this->lang["User_home_login"], "邮件密码", $user["email"], $userinfo["id"]), $userinfo["id"], 1, 0, 2);
					updateUgp($r["id"]);
					if ($is_market) {
						return ["jwt" => createJwt($userinfo, 604800), "uid" => $userinfo["id"], "username" => $userinfo["username"], "status" => 200, "msg" => "登录成功"];
					} else {
						if (file_exists(CMF_ROOT . "app/res/common.php")) {
							$jwt = createJwt($userinfo);
							userSetCookie($jwt);
							return json(["jwt" => $jwt, "status" => 200, "msg" => "登录成功"]);
						} else {
							return json(["jwt" => createJwt($userinfo), "status" => 200, "msg" => "登录成功"]);
						}
					}
				}
				$this->login_inc($clientIP);
				return json(["status" => 400, "msg" => "登录失败"]);
			}
		}
		$hookParam = ["user" => $user, "compare_password_result" => false];
		hook_one("user_login_start", $hookParam);
		return json(["status" => 400, "msg" => "用户未注册"]);
	}
	public function emailOauth($user, $is_market = false)
	{
		$result = \think\Db::name("clients")->where("id", $user["id"])->find();
		if (!empty($result)) {
			if ($result["status"] != 1) {
				return json(["status" => 400, "msg" => "该帐号已停用/关闭，请联系管理员处理"]);
			}
			$comparePasswordResult = cmf_compare_password($result["password"], $result["password"]);
			$hookParam = ["user" => $user, "compare_password_result" => $comparePasswordResult];
			hook_one("user_login_start", $hookParam);
			$clientIP = get_client_ip(0, true);
			session("user", $result);
			$data = ["lastlogin" => time(), "lastloginip" => $clientIP];
			\think\Db::name("clients")->where("id", $result["id"])->update($data);
			$userinfo["id"] = $result["id"];
			$userinfo["username"] = $result["username"];
			$param["id"] = $result["id"];
			$param["username"] = $result["username"];
			updateUgp($result["id"]);
			return ["jwt" => createJwt($userinfo), "status" => 200, "msg" => "登录成功"];
			if ($is_market) {
				return ["jwt" => createJwt($userinfo, 604800), "uid" => $userinfo["id"], "username" => $userinfo["username"], "status" => 200, "msg" => "登录成功"];
			} else {
				return json(["jwt" => createJwt($userinfo), "status" => 200, "msg" => "登录成功"]);
			}
		}
	}
	public function mobileCodeVerify($user)
	{
		$result = $this->where("phonenumber", $user["phonenumber"])->where("phone_code", $user["phone_code"])->find();
		if (!empty($result)) {
			$mobile = $user["phonenumber"];
			if ($result["status"] != 1) {
				return json(["status" => 400, "msg" => "该帐号已停用/关闭，请联系管理员处理"]);
			}
			if (!cache("logintel" . $mobile)) {
				return json(["status" => 400, "msg" => "验证码已过期"]);
			}
			if ($user["code"] == cache("logintel" . $mobile)) {
				$data = ["lastlogin" => time(), "lastloginip" => get_client_ip(0, true)];
				$this->where("id", $result["id"])->update($data);
				session("user", $result->toArray());
				cache("logintel" . $mobile, null);
				$userinfo["id"] = $result["id"];
				$userinfo["username"] = $result["username"];
				$param["id"] = $result["id"];
				$param["username"] = $result["username"];
				login_sms_remind($result);
				active_log_final(sprintf($this->lang["User_home_login"], "手机短信", $user["phonenumber"], $userinfo["id"]), $userinfo["id"], 1, 0, 2);
				updateUgp($result["id"]);
				hook("client_login", ["uid" => $result["id"], "name" => $result["username"], "ip" => $data["lastloginip"], "jwt" => createJwt($userinfo)]);
				if (file_exists(CMF_ROOT . "app/res/common.php")) {
					$jwt = createJwt($userinfo);
					userSetCookie($jwt);
					return json(["jwt" => $jwt, "status" => 200, "msg" => "登录成功"]);
				} else {
					return json(["jwt" => createJwt($userinfo), "status" => 200, "msg" => "登录成功"]);
				}
			}
			return json(["status" => 400, "msg" => "验证码错误"]);
		}
		return json(["status" => 400, "msg" => "用户未注册"]);
	}
	public function pwReset($user, $type)
	{
		switch ($type) {
			case 1:
				$result = \think\Db::name("clients")->where("phonenumber", $user["phonenumber"])->find();
				if ($result["status"] != 1) {
					return json(["status" => 400, "msg" => "该帐号已停用/关闭，请联系管理员处理"]);
				}
				if (cmf_password($user["password"]) == $result["password"]) {
					return json(["status" => 400, "msg" => lang("新密码不能与原密码一样")]);
				}
				if (!empty($result)) {
					$data = ["update_time" => time(), "password" => cmf_password($user["password"])];
					\think\Db::name("clients")->where("phonenumber", $user["phonenumber"])->update($data);
					cache("resettel" . $user["phonenumber"], null);
					active_log_final(sprintf($this->lang["User_home_reset_pass"], "手机", $user["phonenumber"]), $result["id"], 0, 0, 2);
					hook("client_reset_password", ["uid" => $result["id"], "password" => html_entity_decode($user["password"], ENT_QUOTES)]);
					return json(["status" => 200, "msg" => "修改成功"]);
				}
				break;
			case 2:
				$result = \think\Db::name("clients")->where("email", $user["email"])->find();
				if ($result["status"] != 1) {
					return json(["status" => 400, "msg" => "该帐号已停用/关闭，请联系管理员处理"]);
				}
				if (cmf_password($user["password"]) == $result["password"]) {
					return json(["status" => 400, "msg" => lang("新密码不能与原密码一样")]);
				}
				if (!empty($result)) {
					$data = ["update_time" => time(), "password" => cmf_password($user["password"])];
					\think\Db::name("clients")->where("email", $user["email"])->update($data);
					cache("resetemail" . $user["email"], null);
					active_log_final(sprintf($this->lang["User_home_reset_pass"], "邮箱", $user["email"]), $result["id"], 0, 0, 2);
					hook("client_reset_password", ["uid" => $result["id"], "password" => html_entity_decode($user["password"], ENT_QUOTES)]);
					return json(["status" => 200, "msg" => "修改成功"]);
				}
				break;
			default:
				return json(["status" => 200, "msg" => "修改成功"]);
				break;
		}
		return json(["status" => 401, "msg" => "此用户未注册"]);
	}
	public function checkIPRequestTimes($ip)
	{
		$key = md5($_SERVER["REQUEST_URI"]);
		$data["status"] = 400;
		$data["msg"] = "登录失败";
		session_start();
		if (!isset($_SESSION[$ip][$key]) || !is_array($_SESSION[$ip][$key])) {
			$_SESSION[$ip][$key] = [];
		}
		if (isset($_SESSION[$ip][$key][0])) {
			$_SESSION[$ip][$key][] = time();
			$requestFir = time() - $_SESSION[$ip][$key][0];
			if ($requestFir > 60) {
				$_SESSION[$ip][$key] = [];
			}
			if (!empty($_SESSION[$ip][$key][count($_SESSION[$ip][$key]) - 2])) {
				$requestSec = time() - $_SESSION[$ip][$key][count($_SESSION[$ip][$key]) - 2];
				if ($requestSec < 1) {
					$data["status"] = 400;
					$data["msg"] = "两次提交小于1s禁止提交";
					return json($data);
				}
			}
			if (!empty($_SESSION[$ip][$key][count($_SESSION[$ip][$key]) - 3])) {
				$requestThi = time() - $_SESSION[$ip][$key][count($_SESSION[$ip][$key]) - 3];
				if (isset($_SESSION[$ip][$key][3]) && $requestThi < 10) {
					$data["status"] = 400;
					$data["msg"] = "您在10s内已经提交了3请求，禁止提交";
					return json($data);
				}
			}
			if (!empty($_SESSION[$ip][$key][count($_SESSION[$ip][$key]) - 5])) {
				$requestFif = time() - $_SESSION[$ip][$key][count($_SESSION[$ip][$key]) - 5];
				if (isset($_SESSION[$ip][$key][5]) && $requestFif < 60) {
					$data["status"] = 400;
					$data["msg"] = "您在1分钟期间已经提交了5请求，禁止提交";
					return json($data);
				}
			}
		} else {
			$_SESSION[$ip][$key][] = time();
		}
		session_write_close();
		return json($data);
	}
	public function getUser($phone)
	{
		$result = \think\Db::name("clients")->where("phonenumber", $phone)->find();
		return $result;
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
	public function getClientByField($field, $value, $fields = "*")
	{
		return db("clients")->where($field, $value)->field($fields)->find();
	}
	public function replaceClientName($id, &$data)
	{
		$res = \think\Db::name("certifi_company")->where("auth_user_id", $id)->where("status", 1)->find();
		if ($res) {
			$data["companyname"] = $res["company_name"];
			return null;
		}
		$res = \think\Db::name("certifi_person")->where("auth_user_id", $id)->where("status", 1)->find();
		if ($res) {
			$data["companyname"] = $res["auth_real_name"];
			return null;
		}
		$data["companyname"] = "";
	}
	public function getUserCertifi($user_id, $status = 1)
	{
		$res = \think\Db::name("certifi_person")->where("auth_user_id", $user_id)->where("status", $status)->find();
		if (!$res) {
			$res = \think\Db::name("certifi_company")->where("auth_user_id", $user_id)->where("status", $status)->find();
		}
		return $res ?: [];
	}
}