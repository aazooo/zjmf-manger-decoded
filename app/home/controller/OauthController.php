<?php

namespace app\home\controller;

/**
 * @title 前台三方登录接口
 * @description 三方登录接口说明
 */
class OauthController extends CommonController
{
	private $modules = "oauth";
	public function __construct()
	{
		session_start();
		$this->domain = configuration("domain");
	}
	/**
	 * @title 三方登录
	 * @description 接口说明:所有激活启用的三方登录
	 * 时间 2020/11/30
	 * @author xionglingyuan
	 * @url /oauth
	 * @method GET
	 * @return .url:'跳转地址',
	 * @return .img:'三方logo图片',
	 * @return .name:'模块名称',
	 */
	public function listing()
	{
		$plugins = [];
		$list = \think\Db::name("plugin")->where(["module" => $this->modules, "status" => 1])->order("order", "asc")->select();
		$oauth = array_map("basename", glob(CMF_ROOT . "modules/{$this->modules}/*", GLOB_ONLYDIR));
		$oauth2 = array_map("basename", glob(WEB_ROOT . "plugins/{$this->modules}/*", GLOB_ONLYDIR));
		foreach ($list as $k => $plugin) {
			if (!$plugin["config"]) {
				continue;
			}
			$plugins[$k]["name"] = $plugin["title"];
			$plugins[$k]["dirName"] = $plugin["name"];
			$plugins[$k]["url"] = $this->domain . "/oauth/url/" . $plugin["name"];
			$class = cmf_get_oauthPlugin_class_shd($plugin["name"], $this->modules);
			$obj = new $class();
			$meta = $obj->meta();
			if (in_array($plugin["name"], $oauth)) {
				$oauth_img = CMF_ROOT . "modules/oauth/{$plugin["name"]}/{$meta["logo_url"]}";
			}
			if (in_array($plugin["name"], $oauth2)) {
				$oauth_img = WEB_ROOT . "plugins/oauth/{$plugin["name"]}/{$meta["logo_url"]}";
			}
			if (stripos($oauth_img, ".svg") === false) {
				$plugins[$k]["img"] = "<img width=40 height=40 src=\"" . base64EncodeImage($oauth_img) . "\" />";
			} else {
				$plugins[$k]["img"] = file_get_contents($oauth_img);
			}
		}
		return jsonrule(["data" => $plugins, "status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * title 跳转到登录授权网址
	 * description 接口说明:跳转到登录授权网址
	 * 时间 2020/11/30
	 * author xionglingyuan
	 * url /oauth/url/[:dirName]
	 * method GET
	 */
	public function url(\think\Request $request)
	{
		if (empty($request->dirName)) {
		}
		$pluginDb = \think\Db::name("plugin")->where(["module" => $this->modules, "name" => $request->dirName])->field("config")->find();
		if (!$pluginDb["config"]) {
		}
		$config = json_decode($pluginDb["config"], true);
		$class = cmf_get_oauthPlugin_class_shd($request->dirName, $this->modules);
		if (!class_exists($class)) {
		}
		$obj = new $class();
		$params["name"] = $request->dirName;
		$params["callback"] = $this->domain . "/oauth/callback/" . $request->dirName;
		$paramsArray = array_merge($params, $config);
		$data["url"] = $obj->url($paramsArray);
		$_SESSION["oauth"]["headerLoginUrl"] = $this->domain . "/bind";
		$_SESSION["oauth"]["headerClientsUrl"] = $this->domain . "/clientarea";
		header("Location:{$data["url"]}");
		exit;
	}
	/**
	 * title 回调地址
	 * description 接口说明:回调地址
	 * 时间 2020/11/30
	 * author xionglingyuan
	 * url /oauth/callback/[:dirName]
	 * method GET
	 * return .openid:'第三方授权用户唯一ID',
	 * return .data['username']:'获取的用户名',
	 * return .data['sex']:'获取的性别',
	 * return .data['province']:'省',
	 * return .data['city']:'市',
	 * return .data['avatar']:'头像',
	 */
	public function callback(\think\Request $request)
	{
		$headerLoginUrl = $_SESSION["oauth"]["headerLoginUrl"];
		$headerClientsUrl = $_SESSION["oauth"]["headerClientsUrl"];
		unset($_SESSION["oauth"]["headerLoginUrl"]);
		unset($_SESSION["oauth"]["headerClientsUrl"]);
		if (!$headerLoginUrl) {
			header("Location:{$this->domain}");
			exit;
		}
		if (empty($request->dirName)) {
			return jsonrule(["status" => 400, "msg" => lang("ILLEGAL_PARAM")]);
		}
		$pluginDb = \think\Db::name("plugin")->where(["module" => $this->modules, "name" => $request->dirName])->field("config")->find();
		if (!$pluginDb["config"]) {
			return jsonrule(["status" => 400, "msg" => "三方登录" . $request->dirName . "未激活或模块不存在"]);
		}
		$config = json_decode($pluginDb["config"], true);
		$class = cmf_get_oauthPlugin_class_shd($request->dirName, $this->modules);
		if (!class_exists($class)) {
			return jsonrule(["msg" => "三方登录{$request->dirName}模块不存在", "status" => 400]);
		}
		$obj = new $class();
		$params = $request->get();
		$params["name"] = $request->dirName;
		$params["callback"] = $this->domain . "/oauth/callback/" . $request->dirName;
		$paramsArray = array_merge($params, $config);
		$userinfo = $obj->callback($paramsArray);
		if (empty($userinfo["openid"])) {
			header("Location:{$headerClientsUrl}");
			exit;
		}
		if (empty($userinfo["callbackBind"]) || $userinfo["callbackBind"] == "all") {
			$userinfo["callbackBind"] = 0;
		} elseif ($userinfo["callbackBind"] == "bind_mobile") {
			$userinfo["callbackBind"] = 1;
		} elseif ($userinfo["callbackBind"] == "bind_email") {
			$userinfo["callbackBind"] = 2;
		} elseif ($userinfo["callbackBind"] == "login") {
			$userinfo["callbackBind"] = 3;
		}
		$clients_oauth = \think\Db::name("clients_oauth")->where(["type" => $request->dirName, "openid" => $userinfo["openid"]])->field("uid")->find();
		$userStatusinfo = $this->userStatusinfo();
		$OauthModel = new \app\home\model\OauthModel();
		$clientsModel = new \app\home\model\ClientsModel();
		$clients = \think\Db::name("clients")->where(["id" => $clients_oauth["uid"]])->field("id")->find();
		if (empty($clients["id"])) {
			$untie = ["type" => $request->dirName, "uid" => $clients_oauth["uid"]];
			$OauthModel = new \app\home\model\OauthModel();
			$OauthModel->untie($untie);
			$clients_oauth = [];
		}
		if ($userStatusinfo["id"]) {
			$bind = ["type" => $request->dirName, "openid" => $userinfo["openid"], "oauth" => $userinfo["data"], "uid" => $userStatusinfo["id"]];
			$OauthModel->bind($bind);
			header("Location:{$headerClientsUrl}");
			exit;
		} else {
			if ($clients_oauth["uid"]) {
				$user["id"] = $clients_oauth["uid"];
				$jwt = $clientsModel->emailOauth($user);
				if ($jwt["status"] !== 200) {
					$_SESSION["oauth"]["new_data"] = $userinfo["data"];
					$_SESSION["oauth"]["dirName"] = $request->dirName;
					$_SESSION["oauth"]["openid"] = $userinfo["openid"];
					$_SESSION["oauth"]["callbackBind"] = $userinfo["callbackBind"] ? $userinfo["callbackBind"] : 0;
					header("Location:{$headerLoginUrl}");
					exit;
				} else {
					userSetCookie($jwt["jwt"]);
					$client = \think\Db::name("clients")->where("id", $clients_oauth["uid"])->find();
					$idata = ["create_time" => time(), "description" => sprintf("使用%s : %s - User ID:%d 登录成功", $obj->meta()["name"], $client["username"], $clients_oauth["uid"]), "user" => $client["username"], "usertype" => "Client", "uid" => $clients_oauth["uid"], "ipaddr" => get_client_ip6(), "type" => 1, "activeid" => $clients_oauth["uid"], "port" => get_remote_port(), "type_data_id" => 0];
					\think\Db::name("activity_log")->insert($idata);
					header("Location:{$headerClientsUrl}");
					exit;
				}
			} else {
				if ($userinfo["callbackBind"] == 3) {
					$user["email"] = "";
					$sale_id = $this->getSalerId(-1);
					$user["sale_id"] = $sale_id ? $sale_id : 0;
					$jwt = $clientsModel->register($user, 2);
					$params["uid"] = $_SESSION["think"]["user"]["id"];
					$params["new_data"] = $params["oauth"];
					$bind = $OauthModel->bind($params);
					header("Location:{$headerClientsUrl}");
					exit;
				} else {
					$_SESSION["oauth"]["new_data"] = $userinfo["data"];
					$_SESSION["oauth"]["dirName"] = $request->dirName;
					$_SESSION["oauth"]["openid"] = $userinfo["openid"];
					$_SESSION["oauth"]["callbackBind"] = $userinfo["callbackBind"] ? $userinfo["callbackBind"] : 0;
					header("Location:{$headerLoginUrl}");
					exit;
				}
			}
		}
	}
	/**
	 * @title 回调地址
	 * @description 接口说明:回调给前端地址
	 * 时间 2020/11/30
	 * @author xionglingyuan
	 * @url /oauth/callbackInfo
	 * @method GET
	 * @return .callbackBind:'0邮箱和手机号任选绑定，1输入手机号绑定，2输入邮箱绑定',
	 */
	public function callbackInfo()
	{
		$userinfo["callbackBind"] = $_SESSION["oauth"]["callbackBind"];
		return jsonrule(["data" => $userinfo, "status" => 200]);
	}
	/**
	 * @title 邮箱登录绑定
	 * @description 接口说明:邮箱登录绑定
	 * 时间 2020/12/15
	 * @author xionglingyuan
	 * @url /oauth/bind_login_email
	 * @method POST
	 * @param .name:email type:string require:1 default: other: desc:邮箱
	 * @param .name:code type:string require:1 default: other: desc:验证码
	 * @return .oauthStatus:'invalid,绑定信息失效的时候跳转到登录页',
	 */
	public function bindLoginEmail(\think\Request $request)
	{
		if ($request->isPost()) {
			$validate = new \think\Validate(["email" => "require", "code" => "require"]);
			$validate->message(["email.require" => "邮箱不能为空", "code.require" => "验证码必填"]);
			$data = $request->param();
			if (!$validate->check($data)) {
				return json(["status" => 400, "msg" => $validate->getError()]);
			}
			$email = trim($data["email"]);
			if (!\think\facade\Validate::isEmail($email)) {
				return json(["status" => 400, "msg" => "请输入正确的邮箱"]);
			}
			$code = cache("registeremail" . $email);
			if (empty($code)) {
				return json(["status" => 400, "msg" => "验证码已过期"]);
			}
			if (trim($data["code"]) != $code) {
				return json(["status" => 400, "msg" => "验证码错误"]);
			}
			if (!$_SESSION["oauth"]["dirName"] || !$_SESSION["oauth"]["openid"]) {
				return json(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
			}
			$clientsModel = new \app\home\model\ClientsModel();
			$this->OauthModel = new \app\home\model\OauthModel();
			$clients = \think\Db::name("clients")->where("email", $email)->find();
			$params = ["type" => $_SESSION["oauth"]["dirName"], "openid" => $_SESSION["oauth"]["openid"], "oauth" => $_SESSION["oauth"]["new_data"]];
			if ($clients["id"] > 0) {
				$user["id"] = $clients["id"];
				$params["uid"] = $clients["id"];
				$bindCheck = $this->OauthModel->bindCheck($params);
				if ($bindCheck["status"] !== 200) {
					$bindCheck["oauthStatus"] = "invalid";
					unset($_SESSION["oauth"]);
					return json($bindCheck);
				}
				$jwt = $clientsModel->emailOauth($user);
				$jwt = json($jwt);
				$sms = new \app\common\logic\Sms();
				$ret = $sms->sendSms(39, $clients["phone_code"] . $clients["phonenumber"], $params, false, $clients["id"]);
				if ($ret["status"] == 200) {
					$data = ["ip" => get_client_ip6(), "phone" => $clients["phonenumber"], "time" => time()];
					\think\Db::name("sendmsglimit")->insertGetId($data);
				}
			} else {
				$user["email"] = trim($data["email"]);
				$sale_id = $this->getSalerId(-1);
				$user["sale_id"] = $sale_id ? $sale_id : 0;
				$jwt = $clientsModel->register($user, 2);
				$params["uid"] = $_SESSION["think"]["user"]["id"];
				$params["new_data"] = $params["oauth"];
			}
			$bind = $this->OauthModel->bind($params);
			unset($_SESSION["oauth"]);
			if ($bind["status"] !== 200) {
				$bind["oauthStatus"] = "invalid";
				return json($bind);
			}
			return $jwt;
		}
		return json(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	* @title 手机登录绑定
	* @description 接口说明: 手机登录绑定
		   时间 2020/12/15
	* @author xionglingyuan
	* @url /oauth/bind_login_phone
	* @method POST
	* @param .name:phone_code type:string require:0 default: other: desc:手机号区号
	* @param .name:phone type:string require:1 default: other: desc:手机号
	* @param .name:code type:string require:1 default: other: desc:验证码
	* @return .oauthStatus:'invalid,绑定信息失效的时候跳转到登录页',
	*/
	public function bindLoginPhone(\think\Request $request)
	{
		if ($request->isPost()) {
			$validate = new \think\Validate(["phone" => "require|length:4,11", "code" => "require"]);
			$validate->message(["phone.require" => "手机号不能为空", "phone.length" => "手机长度为4-11位", "code.require" => "验证码必填"]);
			$data = $request->param();
			if (!$validate->check($data)) {
				return json(["status" => 400, "msg" => $validate->getError()]);
			}
			$phone_code = trim($data["phone_code"] ?? 86);
			$mobile = trim($data["phone"]);
			if ($phone_code == "+86" || $phone_code == "86" || $phone_code == "") {
				$phone = $mobile;
			} else {
				if (substr($phone_code, 0, 1) == "+") {
					$phone = substr($phone_code, 1) . "-" . $mobile;
				} else {
					$phone = $phone_code . "-" . $mobile;
				}
			}
			if (!cmf_check_mobile($phone)) {
				return json(["status" => 400, "msg" => "请输入正确的手机号"]);
			}
			$code = cache("registertel" . $mobile);
			if (empty($code)) {
				return json(["status" => 400, "msg" => "验证码已过期"]);
			}
			if (trim($data["code"]) != $code) {
				return json(["status" => 400, "msg" => "验证码错误"]);
			}
			if (!$_SESSION["oauth"]["dirName"] || !$_SESSION["oauth"]["openid"]) {
				return json(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
			}
			$clientsModel = new \app\home\model\ClientsModel();
			$this->OauthModel = new \app\home\model\OauthModel();
			$clients = \think\Db::name("clients")->where("phone_code", $phone_code)->where("phonenumber", $phone)->find();
			$params = ["type" => $_SESSION["oauth"]["dirName"], "openid" => $_SESSION["oauth"]["openid"], "oauth" => $_SESSION["oauth"]["new_data"]];
			if ($clients["id"] > 0) {
				$user["id"] = $clients["id"];
				$params["uid"] = $user["id"];
				$bindCheck = $this->OauthModel->bindCheck($params);
				if ($bindCheck["status"] !== 200) {
					$bindCheck["oauthStatus"] = "invalid";
					unset($_SESSION["oauth"]);
					return json($bindCheck);
				}
				$jwt = $clientsModel->emailOauth($user);
				$jwt = json($jwt);
				$sms = new \app\common\logic\Sms();
				$ret = $sms->sendSms(39, $clients["phone_code"] . $clients["phonenumber"], $params, false, $clients["id"]);
				if ($ret["status"] == 200) {
					$data = ["ip" => get_client_ip6(), "phone" => $clients["phonenumber"], "time" => time()];
					\think\Db::name("sendmsglimit")->insertGetId($data);
				}
			} else {
				$user["phone_code"] = $phone_code;
				$user["phonenumber"] = $mobile;
				$sale_id = $this->getSalerId(-1);
				$user["sale_id"] = $sale_id ? $sale_id : 0;
				$jwt = $clientsModel->register($user, 1);
				$params["uid"] = $_SESSION["think"]["user"]["id"];
				$params["new_data"] = $params["oauth"];
			}
			$bind = $this->OauthModel->bind($params);
			unset($_SESSION["oauth"]);
			if ($bind["status"] !== 200) {
				$bind["oauthStatus"] = "invalid";
				return json($bind);
			}
			return $jwt;
		}
		return json(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 邮箱绑定--验证码发送
	 * @description 接口说明:阿里大鱼发送短信验证码--注册
	 * @author wyh
	 * @url /oauth/bind_email_send
	 * @method POST
	 * @param .name:email type:string require:1 default:1 other: desc:邮箱
	 */
	public function bindEmailSend(\think\Request $request)
	{
		if (configuration("shd_allow_email_send") == 0) {
			return jsonrule(["status" => 400, "msg" => "邮箱发送功能已关闭"]);
		}
		$ip = $request->ip();
		$key = "home_client_register_email_" . $ip;
		if (\think\facade\Cache::has($key)) {
			\think\facade\Cache::inc($key);
			$tmp = \think\facade\Cache::get($key);
			if ($tmp >= 10) {
				return json(["status" => 400, "msg" => "五分钟只能发送五次"]);
			}
		} else {
			\think\facade\Cache::set($key, 1, 300);
		}
		$data = $request->param();
		if (!captcha_check($data["captcha"], "allow_register_email_captcha") && configuration("allow_register_email_captcha") == 1 && configuration("is_captcha") == 1) {
			return json(["status" => 400, "msg" => "图形验证码有误"]);
		}
		if ($request->isPost()) {
			$validate = new \think\Validate(["email" => "require"]);
			$validate->message(["email.require" => "邮箱不能为空"]);
			if (!$validate->check($data)) {
				return json(["status" => 400, "msg" => $validate->getError()]);
			}
			$email = trim($data["email"]);
			if (\think\facade\Validate::isEmail($email)) {
				$code = mt_rand(100000, 999999);
				if (time() - session("registertime" . $email) >= 60) {
					$email_logic = new \app\common\logic\Email();
					$result = $email_logic->sendEmailCode($email, $code);
					session("registertime" . $email, time());
					if ($result) {
						cache("registeremail" . $email, $code, 600);
						return json(["status" => 200, "msg" => lang("CODE_SEND_SUCCESS")]);
					} else {
						return json(["status" => 400, "msg" => lang("CODE_SEND_FAIL")]);
					}
				} else {
					return json(["status" => 400, "msg" => lang("CODE_SENDED")]);
				}
			} else {
				return json(["status" => 400, "msg" => lang("EMAIL_ERROR")]);
			}
		}
		return json(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 短信绑定--验证码发送
	 * @description 接口说明:阿里大鱼发送短信验证码--注册(需要手机号，验证码模板<id=8>)
	 * @author wyh
	 * @url /oauth/bind_phone_send
	 * @method POST
	 * @param .name:phone_code type:string require:1 default:1 other: desc:国际手机区号
	 * @param .name:phone type:string require:1 default:1 other: desc:手机号
	 * @param .name:mk type:string require:1  other: desc:common_list接口返回的msfntk作为cookie写入,并在发送短信时作为token传入
	 */
	public function bindPhoneSend(\think\Request $request)
	{
		$ip = $request->ip();
		$key = "home_client_register_phone_" . $ip;
		if (\think\facade\Cache::has($key)) {
			\think\facade\Cache::inc($key);
			$tmp = \think\facade\Cache::get($key);
			if ($tmp >= 10) {
				return json(["status" => 400, "msg" => "五分钟只能发送五次"]);
			}
		} else {
			\think\facade\Cache::set($key, 1, 300);
		}
		unset($ip);
		unset($key);
		unset($tmp);
		if (!checkPhoneRegister()) {
			return json(["status" => 400, "msg" => lang("未开启手机登录注册功能，不能发送验证码")]);
		}
		$data = $request->param();
		if (!captcha_check($data["captcha"], "allow_register_phone_captcha") && configuration("allow_register_phone_captcha") == 1 && configuration("is_captcha") == 1) {
			return json(["status" => 400, "msg" => "图形验证码有误"]);
		}
		$agent = $request->header("user-agent");
		if (strpos($agent, "Mozilla") === false) {
			return json(["status" => 400, "msg" => "短信发送失败"]);
		}
		if ($request->isPost()) {
			$validate = new \think\Validate(["phone" => "require|length:5,13"]);
			$validate->message(["phone.require" => "手机号不能为空", "phone.length" => "手机长度为4-11位"]);
			if (cookie("msfntk") != $data["mk"] || !cookie("msfntk")) {
			}
			if (!$validate->check($data)) {
				return json(["status" => 400, "msg" => $validate->getError()]);
			}
			$phone_code = trim($data["phone_code"]);
			$mobile = trim($data["phone"]);
			$rangeTypeCheck = rangeTypeCheck($phone_code . $mobile);
			if ($rangeTypeCheck["status"] == 400) {
				return jsonrule($rangeTypeCheck);
			}
			if ($phone_code == "+86" || $phone_code == "86" || empty($phone_code)) {
				$phone = $mobile;
			} else {
				if (substr($phone_code, 0, 1) == "+") {
					$phone = substr($phone_code, 1) . "-" . $mobile;
				} else {
					$phone = $phone_code . "-" . $mobile;
				}
			}
			$count = \think\Db::name("clients")->where("phonenumber", $mobile)->count();
			if ($count > 0) {
				$clientsModel = new \app\home\model\ClientsModel();
				$cli = $clientsModel->getUser($mobile);
				if ($cli["phone_code"] != "86") {
					$phone = $cli["phone_code"] . "-" . $mobile;
				}
			}
			if (cmf_check_mobile($phone)) {
				if (\think\facade\Cache::has("registertime_" . $mobile . "_time")) {
					return json(["status" => 400, "msg" => lang("CODE_SENDED")]);
				}
				$code = mt_rand(100000, 999999);
				if (time() - session("registertime" . $mobile) >= 60) {
					$params = ["code" => $code];
					$sms = new \app\common\logic\Sms();
					$result = $sms->sendSms(8, $phone, $params);
					session("registertime" . $mobile, time());
					if ($result["status"] == 200) {
						$data = ["ip" => get_client_ip6(), "phone" => $phone, "time" => time()];
						\think\Db::name("sendmsglimit")->insertGetId($data);
						cache("registertel" . $mobile, $code, 300);
						\think\facade\Cache::set("registertime_" . $mobile . "_time", $code, 60);
						return json(["status" => 200, "msg" => lang("CODE_SEND_SUCCESS")]);
					} else {
						$msg = lang("CODE_SEND_FAIL");
						$tmp = config()["public"]["ali_sms_error_code"];
						if (isset($tmp[$result["data"]["Code"]])) {
							$msg = $tmp[$result["data"]["Code"]];
						}
						return json(["status" => 400, "msg" => $msg]);
					}
				} else {
					return json(["status" => 400, "msg" => lang("CODE_SENDED")]);
				}
			} else {
				return json(["status" => 400, "msg" => "请输入正确的手机号"]);
			}
		}
		return json(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * 时间 2020/6/24 11:25
	 * title 设定销售员
	 * desc 设定销售员
	 */
	public function getSalerId($sale_id)
	{
		if ($sale_id == -1) {
			$sale_reg_setting = configuration("sale_reg_setting");
			if ($sale_reg_setting == 0) {
				return jsons(["status" => 200, "msg" => "设定成功"]);
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
	public function userStatusinfo()
	{
		$jwt = userGetCookie();
		$jwt = html_entity_decode($jwt);
		$uid = \think\facade\Cache::get("client_user_login_token_" . $jwt);
		if ($uid) {
			$key = config("jwtkey");
			$jwtAuth = json_encode(\Firebase\JWT\JWT::decode($jwt, $key, ["HS256"]));
			$authInfo = json_decode($jwtAuth, true);
			$checkJwtToken = ["status" => 1001, "msg" => "Token验证通过", "id" => $authInfo["userinfo"]["id"], "nbf" => $authInfo["nbf"], "ip" => $authInfo["ip"], "contactid" => $authInfo["userinfo"]["contactid"] ?? 0, "username" => $authInfo["userinfo"]["username"]];
			$pass = \think\facade\Cache::get("client_user_update_pass_" . $uid);
			if ($pass && $checkJwtToken["nbf"] < $pass) {
				$authInfo = false;
			}
			$ip_check = configuration("home_ip_check");
			if (get_client_ip() !== $checkJwtToken["ip"] && $ip_check == 1) {
				$authInfo = false;
			}
		} else {
			$authInfo = false;
		}
		if ($authInfo) {
			$userinfo = \think\Db::name("clients")->where("id", $uid)->find();
		} else {
			$userinfo = [];
		}
		return $userinfo;
	}
}