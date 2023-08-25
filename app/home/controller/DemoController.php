<?php

namespace app\home\controller;

class DemoController extends CommonController
{
	public function pay(\think\Request $request, $id)
	{
		if ($id == 1) {
			$gatewaysDIR = "gateways\\wx_pay\\WxPayPlugin";
			$payName = "wxpay_handle";
		} elseif ($id == 2) {
			$payName = "global_wxpay_handle";
			$gatewaysDIR = "gateways\\global_wxpay\\globalWxPayPlugin";
		} elseif ($id == 3) {
			$payName = "alipay_handle";
			$gatewaysDIR = "gateways\\ali_pay\\aliPayPlugin";
		} else {
			$payName = "alipay_handle";
			$gatewaysDIR = "gateways\\global_alipay\\globalAliPayPlugin";
		}
		$data = $request->post();
		\think\facade\Hook::exec([$gatewaysDIR, cmf_parse_name($payName, 1)], $data);
	}
	public function dd(\think\Request $request)
	{
		$param = $request->post();
		dump($request->param());
		p($request);
		p($this->request->param());
		$res = hook("alipay_handle");
		$data = $request->post();
		$res = hook("alipay_handle", $data);
	}
	public function demo(\think\Request $request)
	{
		return json(start_pay(413, "WxPay"));
		$res = hook("alipay_handle");
		p($res);
		$url = $menuName = request()->path();
		$str = explode("/", $url);
		$path = "";
		$id = $request->uid;
		foreach ($str as $k => $v) {
			if ($k < 3) {
				$path .= str_replace("_", "", $v);
			}
		}
		$path = strtolower($path);
		$time = time();
		$this->assign("js_debug", APP_DEBUG ? "?v={$time}" : "");
		$array_log = [$id, session("name"), date("H:i:s"), get_client_ip(), $path, request()->param()];
		p($array_log);
		$filename = CMF_ROOT . "data/user_log/";
		!is_dir($filename) && mkdir($filename, 493, true);
		$file_hwnd = fopen($filename . date("Y-m-d") . ".log", "a+");
		fwrite($file_hwnd, json_encode($array_log) . "\r\n");
		fclose($file_hwnd);
	}
	/**
	 *展示二维码
	 */
	public function index()
	{
		header("Content-type:text/html;charset=utf-8");
		$appid = "wx81d3a83634151310";
		$redirect_uri = "http://demo1.idcsmart.com/finace_root/home/demo/demo";
		$redirect_uri = urlencode($redirect_uri);
		$state = md5(uniqid(rand(), true));
		$_SESSION["wx_state"] = $state;
		$wxlogin_url = "https://open.weixin.qq.com/connect/qrconnect?appid=" . $appid . "&redirect_uri={$redirect_uri}&response_type=code&scope=snsapi_login&state={$state}#wechat_redirect";
		header("Location: {$wxlogin_url}");
		exit;
	}
	/**
	 * 微信扫码处理
	 * @param Request $request
	 * @return \think\response\Json
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function login_handel(\think\Request $request)
	{
		$param = $request->only(["code", "state"]);
		if ($_SESSION["wx_state"] != $param["state"]) {
			return json(["status" => 400, "msg" => "无效的请求"]);
		}
		if (!isset($param["code"])) {
			return json(["status" => 400, "msg" => "错误的请求"]);
		}
		$appid = config("appid");
		$secret = config("secret");
		$url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . $appid . "&" . "secret=" . $secret . "&" . "code=" . $param["code"] . "&grant_type=authorization_code";
		$res = get_data($url);
		if (isset($res["access_token"])) {
			$openid = $res["opendi"];
			$user = model("wechat_user")->where("openid", $openid)->find();
			if ($user) {
				return json(["status" => 200, "msg" => "登陆成功！"]);
			}
			$verify_url = "https://api.weixin.qq.com/sns/auth?access_token=" . $res["access_token"] . "&openid=" . $res["openid"];
			$verify_res = $this->get_data($verify_url);
			if ($verify_res["errcode"] != 0) {
				$renewal_url = "https://api.weixin.qq.com/sns/oauth2/refresh_token?appid=" . $appid . "&grant_type=refresh_token&refresh_token=" . $res["refresh_token"];
				$renewal_res = $this->get_data($renewal_url);
				if (isset($renewal_res["errcode"])) {
					return json(["status" => 400, "msg" => "access_token续期失败"]);
				} else {
					$verify_res = $renewal_res;
				}
			}
			$get_user_info = "https://api.weixin.qq.com/sns/userinfo?access_token=" . $verify_res["access_token"] . "&openid=" . $verify_res["openid"];
			$result = model("wechat_user")->data($get_user_info)->insert();
			if ($result) {
				return json(["status" => 201, "msg" => "微信注册成功"]);
			}
		}
		return json(["status" => 400, "msg" => "获取信息错误"]);
	}
	/**
	 * post请求
	 * @param $url
	 * @return bool|mixed|string
	 */
	public function get_data($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$dom = curl_exec($ch);
		curl_close($ch);
		$dom = json_decode($dom, true);
		return $dom;
	}
}