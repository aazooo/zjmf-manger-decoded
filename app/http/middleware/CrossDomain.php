<?php

namespace app\http\middleware;

class CrossDomain
{
	public function handle($request, \Closure $next)
	{
		$allow_origin = ["a.baidu.com", "b.baidu.com"];
		$origin = isset($_SERVER["HTTP_ORIGIN"]) ? $_SERVER["HTTP_ORIGIN"] : "";
		header("Access-Control-Allow-Origin: " . $origin);
		header("Access-Control-Allow-Credentials: true");
		header("Access-Control-Allow-Headers: Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-Requested-With");
		header("Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE");
		header("Access-Control-Max-Age: 1728000");
		if (strtoupper($request->method()) == "OPTIONS") {
			return response();
		}
		return $next($request);
	}
}