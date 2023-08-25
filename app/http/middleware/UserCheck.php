<?php

namespace app\http\middleware;

class UserCheck extends Check
{
	protected $cookieDomain;
	protected $header = ["Access-Control-Allow-Credentials" => "true", "Access-Control-Allow-Methods" => "GET, POST, PATCH, PUT, DELETE, OPTIONS", "Access-Control-Allow-Headers" => "Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-CSRF-TOKEN, X-Requested-With"];
	public function handle($request, \Closure $next)
	{
		sessionInit();
		$sessionAdminId = session("ADMIN_ID");
		if (strpos($request->controller(), "View") === 0) {
			if (configuration("main_tenance_mode") == 1 && !$sessionAdminId) {
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
		$client_status = \think\Db::name("clients")->where("id", $request->uid)->value("status");
		if ($request->uid && $client_status != 1 && !$sessionAdminId) {
			userUnsetCookie();
			if (request()->isAjax()) {
				return json(["status" => 400, "msg" => "该帐号已停用/关闭，请联系管理员处理"]);
			}
			return redirect("/login?forceLogout=1");
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
			trace("OPTIONS1:" . $header, "info");
			return \think\Response::create()->code(204)->header($header);
		}
		$res = parent::checkToken($request);
		if ($res) {
			$request->uid = $res["id"];
			$request->uname = $res["username"];
			hook("user_action_log");
			return $next($request);
		}
		$request->uid = 0;
		$request->uname = "";
		return $next($request);
	}
}