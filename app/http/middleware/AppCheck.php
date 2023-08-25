<?php

namespace app\http\middleware;

class AppCheck
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
		if (!cmf_is_installed() && $request->module() != "api" && $request->controller() != "Install") {
			if ($request->module() == (config("database.admin_application") ?: "admin")) {
				return json(["status" => 302, "msg" => "系统未安装"], 200);
			} else {
				return redirect(cmf_get_root() . "/install.html");
			}
		}
		$request_time = $request->param("request_time", 0);
		$request_time = ceil($request_time / 1000);
		$tmp = time();
		if ($tmp + 10 < $request_time || $request_time + 10 < $tmp) {
		}
		return $next($request);
	}
}