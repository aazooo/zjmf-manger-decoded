<?php

namespace app\http\middleware;

class AdminCheck
{
	protected $cookieDomain;
	protected $header = ["Access-Control-Allow-Credentials" => "true", "Access-Control-Allow-Methods" => "GET, POST, PATCH, PUT, DELETE, OPTIONS", "Access-Control-Allow-Headers" => "Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-CSRF-TOKEN, X-Requested-With"];
	public function __construct()
	{
		$this->cookieDomain = null;
	}
	public function handle($request, \Closure $next)
	{
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
		$sessionAdminId = cmf_get_current_admin_id();
		if (!isset($sessionAdminId) || !$sessionAdminId) {
			return json(["status" => 405, "msg" => "您还没有登录"]);
		}
		return $next($request);
	}
}