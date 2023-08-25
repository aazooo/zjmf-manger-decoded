<?php

namespace app\home\controller;

/**
* @title 微信
* @description 接口说明
* @header name:key require:1 default: desc:秘钥(区别设置)
// * @param name:public type:int require:1 default:1 other: desc:公共参数(区别设置)
*/
class WechatController extends CommonController
{
	/**
	 * @title 扫码登录(线上)
	 * @description 接口说明
	 * @author 上官磨刀
	 * @url /wechat_login
	 * @return data 微信二维码地址
	 * @method GET
	 */
	public function index()
	{
		if (!checWechatLogin()) {
			return json(["status" => 400, "msg" => lang("未开启微信登录注册功能，不能发送验证码")]);
		}
		header("Content-type:text/html;charset=utf-8");
		$appid = config("appid");
		$type = $this->request->param();
		$type["type"] = $type["type"] ?? "1";
		if ($type["type"] == 2) {
			if (empty($type["id"])) {
				return json(["status" => 400, "msg" => "没有获取到用户信息"]);
			}
			$redirect_uri = "http://f.test.idcsmart.com/bind_wechat_handle/" . $type["id"] . "/";
		} else {
			$redirect_uri = "http://f.test.idcsmart.com/wechat_login_handle";
		}
		$redirect_uri = urlencode($redirect_uri);
		$state = md5(uniqid(rand(), true));
		session("wx_state", $state);
		$wxlogin_url = "https://open.weixin.qq.com/connect/qrconnect?appid=" . $appid . "&redirect_uri={$redirect_uri}&response_type=code&scope=snsapi_login&state={$state}#wechat_redirect";
		return redirect($wxlogin_url);
	}
	/**
	 * @title 获取微信二维码配置
	 * @description 接口说明
	 * @author 上官磨刀
	 * @url /get_wechat_config
	 * @method GET
	 * @return .appid
	 * @return .redirect_uri:回调地址
	 * @return .state:签名
	 */
	public function get_wechat_config(\think\Request $request)
	{
		$type = $request->type;
		if ($type == 2) {
			if (empty($type["id"])) {
				return json(["status" => 400, "msg" => "没有获取到用户信息"]);
			}
			$redirect_uri = "http://f.test.idcsmart.com/bind_wechat_handle/" . $type["id"] . "/";
		} else {
			$redirect_uri = "http://f.test.idcsmart.com/wechat_login_handle";
		}
		$redirect_uri = urlencode($redirect_uri);
		$appid = config("appid");
		$state = md5(uniqid(rand(), true));
		$data = ["appid" => $appid, "redirect_uri" => $redirect_uri, "state" => $state];
		return json(["data" => $data, "status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * 微信扫码处理
	 * @param Request $request
	 * @return \think\response\Json
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function login_handle(\think\Request $request)
	{
		$param = $request->only(["code", "state"]);
		$wx_state = session("wx_state");
		if (!$wx_state || $wx_state != $param["state"]) {
			return json(["status" => 400, "msg" => "无效的请求"]);
		}
		if (!isset($param["code"])) {
			return json(["status" => 400, "msg" => "错误的请求"]);
		}
		$appid = config("appid");
		$secret = config("secret");
		$url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . $appid . "&" . "secret=" . $secret . "&" . "code=" . $param["code"] . "&grant_type=authorization_code";
		$res = $this->get_data($url);
		if (isset($res["access_token"])) {
			if (isset($res["unionid"])) {
				$wechat_id = model("wechat_user")->getFieldByUnionid($res["unionid"], "id");
				if (empty($wechat_id)) {
					return $this->wechat_regist($res, $appid);
				}
				$userinfo = model("clients")->where("wechat_id", $wechat_id)->field("id,username,avatar")->find();
				if ($userinfo) {
					return redirect("http://f.test.idcsmart.com/#/login", ["jwt" => createJwt($userinfo), "status" => 200]);
				} else {
					return json(["status" => 400, "msg" => "用户状态异常"]);
				}
			}
		}
		return json(["status" => 400, "msg" => "获取信息错误"]);
	}
	/**
	 * 注册
	 * @param $res .用户信息
	 * @param $appid
	 * @return \think\response\Json
	 */
	public function wechat_regist($res, $appid)
	{
		$verify_url = "https://api.weixin.qq.com/sns/auth?access_token=" . $res["access_token"] . "&openid=" . $res["openid"];
		$verify_res = $this->get_data($verify_url);
		if ($verify_res["errcode"] != 0) {
			$renewal_url = "https://api.weixin.qq.com/sns/oauth2/refresh_token?appid=" . $appid . "&grant_type=refresh_token&refresh_token=" . $res["refresh_token"];
			$renewal_res = $this->get_data($renewal_url);
			if (isset($renewal_res["errcode"])) {
				return json(["status" => 400, "msg" => "access_token续期失败"]);
			} else {
				$res = $renewal_res;
			}
		}
		$url = "https://api.weixin.qq.com/sns/userinfo?access_token=" . $res["access_token"] . "&openid=" . $res["openid"];
		$get_user_info = $this->get_data($url);
		$get_user_info["update_time"] = $get_user_info["create_time"] = time();
		$success = true;
		\think\Db::startTrans();
		try {
			$result_id = model("wechat_user")->strict(false)->insertGetId($get_user_info);
			$time = time();
			$wechat_data = ["wechat_id" => $result_id, "create_time" => $time, "update_time" => $time, "currency" => getDefaultCurrencyId(), "avatar" => "用户头像2-" . rand(10, 20) . ".jpg", "lastloginip" => get_client_ip(0, true), "lastlogin" => time(), "status" => 1];
			$uid = model("clients")->insertGetId($wechat_data);
			\think\Db::commit();
		} catch (\Exception $e) {
			\think\Db::rollback();
			$success = false;
		}
		if (!$success) {
			return json(["status" => 400, "msg" => "微信注册失败"]);
		}
		$return_userinfo = ["id" => $uid, "username" => $get_user_info["nickname"], "sex" => $get_user_info["sex"]];
		hook("user_action_log", ["type" => "user", "mid" => $uid]);
		return json(["jwt" => createJwt($return_userinfo), "status" => 201, "msg" => "微信注册成功"]);
	}
	/**
	 * get请求
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