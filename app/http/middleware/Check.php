<?php

namespace app\http\middleware;

class Check
{
	protected $cookieDomain;
	protected $header = ["Access-Control-Allow-Credentials" => "true", "Access-Control-Allow-Methods" => "GET, POST, PATCH, PUT, DELETE, OPTIONS", "Access-Control-Allow-Headers" => "Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-CSRF-TOKEN, X-Requested-With"];
	public function __construct()
	{
		$this->cookieDomain = null;
	}
	public function handle($request, \Closure $next)
	{
		sessionInit();
		$sessionAdminId = session("ADMIN_ID");
		if (strpos($request->controller(), "View") === 0 && !$sessionAdminId) {
			if (configuration("main_tenance_mode") == 1) {
				$main_tenance_mode_url = configuration("main_tenance_mode_url");
				if (!empty($main_tenance_mode_url)) {
					if (!get_headers($main_tenance_mode_url)) {
						throw new \Exception("维护模式重定向的url不是一个有效的url地址.");
					}
					header("location:" . configuration("main_tenance_mode_url"));
				} else {
					throw new \app\server\MaintainExctption([200, configuration("main_tenance_mode_message") ?: "维护中……"]);
				}
				exit;
			}
		}
		if (!!configuration("main_tenance_mode") && !$sessionAdminId) {
			return json(["status" => 503, "msg" => configuration("main_tenance_mode_message") ?? "维护中……", "data" => configuration("main_tenance_mode_url") ?? ""]);
		}
		$header = !empty($header) ? array_merge($this->header, $header) : $this->header;
		if (!isset($header["Access-Control-Allow-Origin"])) {
			$origin = $request->header("origin");
			if ($origin && ("" == $this->cookieDomain || strpos($origin, $this->cookieDomain))) {
				$header["Access-Control-Allow-Origin"] = $origin;
			} else {
				$header["Access-Control-Allow-Origin"] = "*";
			}
		}
		if ($request->method(true) == "OPTIONS") {
			return \think\Response::create()->code(204)->header($header);
		}
		$res = $this->checkToken($request);
		if ($res["status"] !== 1001) {
			return json($res);
		}
		$request->uid = $res["id"];
		$request->contactid = $res["contactid"] ?? 0;
		$request->uname = $res["username"];
		$request->is_api = intval($res["is_api"]);
		$client_status = \think\Db::name("clients")->where("id", $request->uid)->value("status");
		if ($request->uid && $client_status != 1 && !$sessionAdminId) {
			userUnsetCookie();
			if (request()->isAjax()) {
				return json(["status" => 400, "msg" => "该帐号已停用/关闭，请联系管理员处理"]);
			}
			return redirect("/login?forceLogout=1");
		}
		if (!empty($res["contactid"])) {
			$contact_permissions = config("contact_permissions");
			$controller = "";
			$allow_controller = "";
			foreach ($contact_permissions as $key => $val) {
				$controller .= $val["controller"] . ",";
			}
			$controller_arr = explode(",", $controller);
			$route = $request->routeinfo()["route"];
			if (in_array($route, $controller_arr)) {
				$contact_data = \think\Db::name("contacts")->field("permissions,generalemails,invoiceemails,productemails,supportemails")->where("id", $res["contactid"])->find();
				$permission_name_array = explode(",", $contact_data["permissions"]);
				if (empty($permission_name_array)) {
					return jsonrule(["status" => 401, "msg" => "权限不足，无法访问"]);
				}
				foreach ($contact_permissions as $key => $val) {
					if (in_array($val["name"], $permission_name_array)) {
						$allow_controller .= $val["controller"] . ",";
					}
				}
				$allow_controller = rtrim($allow_controller, ",");
				$allow_controller_arr = explode(",", $allow_controller);
				if (!in_array($route, $allow_controller_arr)) {
					return jsonrule(["status" => 401, "msg" => "权限不足，无法访问"]);
				}
			}
		}
		hook("user_action_log");
		return $next($request);
	}
	public function checkToken(\think\Request $request)
	{
		$header = $request->header();
		$userGetCookie = userGetCookie();
		if ($userGetCookie) {
			$header["authorization"] = "JWT " . $userGetCookie;
		}
		if (!isset($header["authorization"])) {
			return ["status" => 405, "msg" => "请登陆后再试"];
		} else {
			$authorization = explode(" ", $header["authorization"])[1];
			if (empty($authorization) || $authorization == "null") {
				return ["status" => 405, "msg" => "请登陆后再试"];
			}
			if (count(explode(".", $authorization)) != 3) {
				return ["status" => 405, "msg" => "请登陆后再试"];
			}
			$tmp = \think\facade\Cache::get("client_user_login_token_" . $authorization);
			if (!$tmp) {
				return ["status" => 405, "msg" => "请登陆后再试"];
			}
			$checkJwtToken = $this->verifyJwt($authorization);
			$pass = \think\facade\Cache::get("client_user_update_pass_" . $tmp);
			if ($pass && $checkJwtToken["nbf"] < $pass) {
				return ["status" => 405, "msg" => "密码已修改,请重新登陆"];
			}
			$ip_check = configuration("home_ip_check");
			if (get_client_ip() !== $checkJwtToken["ip"] && $ip_check == 1) {
				return ["status" => 405, "msg" => "登录失效,请重新登录"];
			}
			if ($checkJwtToken["status"] == 1001 && $tmp == $checkJwtToken["id"]) {
				return $checkJwtToken;
			} else {
				return ["status" => 405, "msg" => "请登陆后再试"];
			}
		}
	}
	public function checkTokenDownloads($header)
	{
		if (!isset($header["authorization"])) {
			return ["status" => 405, "msg" => "请登陆后再试"];
		} else {
			$authorization = explode(" ", $header["authorization"])[1];
			if (empty($authorization) || $authorization == "null") {
				return ["status" => 405, "msg" => "请登陆后再试"];
			}
			if (count(explode(".", $authorization)) != 3) {
				return ["status" => 405, "msg" => "请登陆后再试"];
			}
			$tmp = \think\facade\Cache::get("client_user_login_token_" . $authorization);
			if (!$tmp) {
				return ["status" => 405, "msg" => "请登陆后再试"];
			}
			$checkJwtToken = $this->verifyJwt($authorization);
			$pass = \think\facade\Cache::get("client_user_update_pass_" . $tmp);
			if ($pass && $checkJwtToken["nbf"] < $pass) {
				return ["status" => 405, "msg" => "密码已修改,请重新登陆"];
			}
			$ip_check = configuration("home_ip_check");
			if (get_client_ip() !== $checkJwtToken["ip"] && $ip_check == 1) {
				return ["status" => 405, "msg" => "登录失效,请重新登录"];
			}
			if ($checkJwtToken["status"] == 1001 && $tmp == $checkJwtToken["id"]) {
				return $checkJwtToken;
			} else {
				return ["status" => 405, "msg" => "请登陆后再试"];
			}
		}
	}
	protected function verifyJwt($jwt)
	{
		$key = config("jwtkey");
		try {
			$jwtAuth = json_encode(\Firebase\JWT\JWT::decode($jwt, $key, ["HS256"]));
			$authInfo = json_decode($jwtAuth, true);
			$msg = [];
			if (!empty($authInfo["userinfo"])) {
				$msg = ["status" => 1001, "msg" => "Token验证通过", "id" => $authInfo["userinfo"]["id"], "nbf" => $authInfo["nbf"], "ip" => $authInfo["ip"], "contactid" => $authInfo["userinfo"]["contactid"] ?? 0, "username" => $authInfo["userinfo"]["username"], "is_api" => $authInfo["userinfo"]["is_api"] ?? 0];
			} else {
				$msg = ["status" => 1002, "msg" => "Token验证不通过,用户不存在"];
			}
			return $msg;
		} catch (\Firebase\JWT\SignatureInvalidException $e) {
			echo json_encode(["status" => 1002, "msg" => "Token无效"]);
			exit;
		} catch (\Firebase\JWT\ExpiredException $e) {
			echo json_encode(["status" => 405, "msg" => "请重新登录"]);
			exit;
		} catch (Exception $e) {
			return $e;
		}
	}
}