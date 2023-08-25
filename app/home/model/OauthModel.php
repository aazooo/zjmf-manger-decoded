<?php

namespace app\home\model;

/**
 * @title 绑定账户、解绑账户
 * @description 三方登录绑定账户、解绑账户
 */
class OauthModel extends \think\Model
{
	public function bindCheck($params)
	{
		if (!$params["type"] || !$params["uid"]) {
			return ["status" => 400, "msg" => lang("ERROR MESSAGE")];
		}
		$clients_uid = \think\Db::name("clients_oauth")->where(["type" => $params["type"], "uid" => $params["uid"]])->find();
		if ($clients_uid["id"]) {
			return ["status" => 400, "msg" => lang("账号已经绑定" . $params["type"])];
		}
		$clients_oauth = \think\Db::name("clients_oauth")->where(["type" => $params["type"], "openid" => $params["openid"]])->find();
		if (!empty($clients_oauth["id"])) {
			return ["status" => 400, "msg" => lang($params["type"] . "已经绑定")];
		}
		return ["status" => 200, "msg" => lang("SUCCESS MESSAGE")];
	}
	public function bind($params)
	{
		if (!$params["type"] || !$params["uid"]) {
			return ["status" => 400, "msg" => lang("ERROR MESSAGE")];
		}
		$clients_uid = \think\Db::name("clients_oauth")->where(["type" => $params["type"], "uid" => $params["uid"]])->find();
		if ($clients_uid["id"]) {
			return ["status" => 400, "msg" => lang("账号已经绑定" . $params["type"])];
		}
		$clients_oauth = \think\Db::name("clients_oauth")->where(["type" => $params["type"], "openid" => $params["openid"]])->find();
		if (empty($clients_oauth["id"])) {
			$oauth = ["type" => $params["type"], "uid" => $params["uid"], "openid" => $params["openid"], "oauth" => json_encode($params["oauth"])];
			\think\Db::name("clients_oauth")->insert($oauth);
			if ($params["new_data"]) {
				if ($params["new_data"]["sex"] != 1 || $params["new_data"]["sex"] != 2) {
					$params["new_data"]["sex"] = 0;
				}
				if ($params["new_data"]["username"]) {
					$data["username"] = $params["new_data"]["username"];
				}
				if ($params["new_data"]["sex"]) {
					$data["sex"] = $params["new_data"]["sex"];
				}
				if ($params["new_data"]["province"]) {
					$data["province"] = $params["new_data"]["province"];
				}
				if ($params["new_data"]["city"]) {
					$data["city"] = $params["new_data"]["city"];
				}
				if ($params["new_data"]["avatar"]) {
					$data["avatar"] = $params["new_data"]["avatar"];
				}
				\think\Db::name("clients")->where(["id" => $params["uid"]])->update($data);
			}
		} else {
			return ["status" => 400, "msg" => lang($params["type"] . "已经绑定")];
		}
		$clients = \think\Db::name("clients")->where(["id" => $params["uid"]])->find();
		$sms = new \app\common\logic\Sms();
		$ret = $sms->sendSms(39, $clients["phone_code"] . $clients["phonenumber"], $params, false, $clients["id"]);
		if ($ret["status"] == 200) {
			$data = ["ip" => get_client_ip6(), "phone" => $clients["phonenumber"], "time" => time()];
			\think\Db::name("sendmsglimit")->insertGetId($data);
		}
		return ["status" => 200, "msg" => lang("SUCCESS MESSAGE")];
	}
	public function untie($params)
	{
		$clients_uid = \think\Db::name("clients_oauth")->where(["type" => $params["type"], "uid" => $params["uid"]])->find();
		if (!$clients_uid["id"]) {
			return ["status" => 400, "msg" => lang("参数错误")];
		}
		\think\Db::name("clients_oauth")->where(["type" => $params["type"], "uid" => $params["uid"]])->delete();
		return ["status" => 200, "msg" => lang("SUCCESS MESSAGE")];
	}
}