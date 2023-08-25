<?php

namespace app\api\controller;

class OauthController
{
	/**
	 * @title 获取登陆页数据
	 * @return \think\response\Jsonp
	 */
	public function logined()
	{
		$Login = new \app\home\controller\LoginController();
		$mobileLoginVerifyPage = $Login->mobileLoginVerifyPage();
		if (is_object($mobileLoginVerifyPage)) {
			$mobileLoginVerifyPage = $mobileLoginVerifyPage->getData();
		}
		$LoginRegisterIndex = $Login->LoginRegisterIndex();
		if (is_object($LoginRegisterIndex)) {
			$LoginRegisterIndex = $LoginRegisterIndex->getData();
		}
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
		$data["authorize_json_web_token"] = $data["user"] = "";
		$jwt = userGetCookie();
		$user_id = \think\facade\Cache::get("client_user_login_token_" . $jwt);
		$Cloents = new \app\home\model\ClientsModel();
		$user_info = $Cloents->getClientByField("id", $user_id, "username,phonenumber,email");
		\think\facade\Cache::set("authorize_json_web_token", "");
		if ($user_info) {
			$data["user"] = $user_info;
			$data["authorize_json_web_token"] = md5($jwt . randStr(9));
			\think\facade\Cache::set("authorize_json_web_token", $data["authorize_json_web_token"], 3600);
		}
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 账号授权获取AccessToken
	 * @param Request $request
	 * @return array|\think\Response|\think\response\Json
	 */
	public function accountGetAccessToken(\think\Request $request)
	{
		$param = $request->param();
		$redirect_url = $param["redirect_url"];
		if (empty($redirect_url)) {
			return json(["status" => 400, "msg" => "回调地址redirect_url 不能为空！", "data" => []]);
		}
		$action = isset($param["action"]) ? $param["action"] : "";
		$Login = new \app\home\controller\LoginController();
		if ($action == "phone_code") {
			$result = $Login->mobileLoginVerify();
		} elseif ($action == "phone") {
			$result = $Login->phonePassLogin();
		} else {
			$result = $Login->emailLogin();
		}
		if (is_object($result)) {
			$result = $result->getData();
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
			return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => ["redirect_url" => $redirect_url]]);
		}
		$error_num = \think\facade\Cookie::has("login_error_log") ? \think\facade\Cookie::get("login_error_log") : 0;
		\think\facade\Cookie::set("login_error_log", ++$error_num, 900);
		$data["ErrorMsg"] = $result["msg"];
		return json(["status" => $result["status"], "msg" => $result["msg"], "data" => []]);
	}
	/**
	 * @title 自动授权获取AccessToken
	 * @param Request $request
	 * @return array|\think\Response|\think\response\Json
	 */
	public function automaticGetAccessToken(\think\Request $request)
	{
		$param = $request->param();
		$redirect_url = $param["redirect_url"];
		if (empty($redirect_url)) {
			return json(["status" => 400, "msg" => "回调地址redirect_url 不能为空！", "data" => []]);
		}
		$jwt = userGetCookie();
		$token = md5($jwt);
		$res_check = strpos($redirect_url, "?");
		$key = \think\facade\Cache::get("authorize_json_web_token");
		$authorize_json_web_token = $param["authorize_json_web_token"];
		if (empty($key) || empty($jwt)) {
			return json(["status" => 400, "msg" => "自动授权Authorize Json Web Token 已失效！", "data" => []]);
		}
		if ($key !== $authorize_json_web_token) {
			return json(["status" => 400, "msg" => "自动授权Authorize Json Web Token 错误！", "data" => []]);
		}
		if ($res_check === false) {
			$redirect_url .= "?access_token=" . $token;
		} else {
			$redirect_url .= "&access_token=" . $token;
		}
		\think\facade\Cache::set("access_token", $token, 3600);
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => ["redirect_url" => $redirect_url]]);
	}
	/**
	 * @title 使用AccessToken 获取用户授权数据
	 * @param Request $request
	 * @return array|\think\Response|\think\response\Json
	 */
	public function getUserInfo(\think\Request $request)
	{
		$param = $request->param();
		$key = \think\facade\Cache::get("access_token");
		$access_token = $param["access_token"];
		if (empty($key)) {
			return json(["status" => 400, "msg" => "Access Token 已失效！", "data" => []]);
		}
		if ($key !== $access_token) {
			return json(["status" => 400, "msg" => "Access Token 错误！", "data" => []]);
		}
		$user_a_t = \think\facade\Cache::get("a.t." . $access_token);
		$Cloents = new \app\home\model\ClientsModel();
		if ($user_a_t) {
			$user_info = json_decode($user_a_t, true);
			$user_id = $user_info["id"];
		} else {
			$jwt = userGetCookie();
			$user_id = \think\facade\Cache::get("client_user_login_token_" . $jwt);
			$user_info = $Cloents->getClientByField("id", $user_id, "username,phonenumber,email,credit");
		}
		$user_info["user_certifi"] = 0;
		$user_info["real_name"] = "";
		$user_certifi = $Cloents->getUserCertifi($user_id);
		if ($user_certifi) {
			$user_info["user_certifi"] = 1000;
			$user_info["real_name"] = $user_certifi["auth_real_name"];
		}
		return json(["status" => 200, "msg" => "", "data" => $user_info]);
	}
	/**
	 * @title 账号授权获取AccessToken -直接使用账号密码获取
	 * @param Request $request
	 * @return array|\think\Response|\think\response\Json
	 */
	public function accountGetAccessTokenDirect(\think\Request $request)
	{
		$param = $request->param();
		$Client = new \app\home\model\ClientsModel();
		$field = "email";
		if (strpos($param["account"], "@") === false) {
			$field = "phonenumber";
		}
		$clients = $Client->getClientByField($field, $param["account"], "id,username,phonenumber,email,credit,password");
		$res = cmf_compare_password($param["password"], $clients["password"]);
		if (!$res) {
			return json(["status" => 400, "msg" => "账号或密码错误"]);
		}
		$token = md5($clients["id"] . randStr());
		\think\facade\Cache::set("access_token", $token, 3600);
		unset($clients["password"]);
		\think\facade\Cache::set("a.t." . $token, json_encode($clients), 3600);
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => ["access_token" => $token]]);
	}
}