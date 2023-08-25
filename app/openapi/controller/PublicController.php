<?php

namespace app\openapi\controller;

/**
 * @title 公共接口
 * @description 接口说明
 */
class PublicController extends \cmf\controller\HomeBaseController
{
	/**
	 * @title 二次验证
	 * @description 接口说明:二次验证
	 * @author xiong
	 * @url v1/second_verify
	 * @method POST
	 */
	public function secondVerify()
	{
		if (!$this->request->isPost()) {
			return json(["status" => 400, "msg" => "request error"]);
		}
		$data = $this->request->param();
		if (empty($data["code"]) || empty($data["account"])) {
			return json(["status" => 400, "msg" => "verification code must be filled"]);
		}
		if (strpos($data["account"], "@") !== false) {
			$second = "second_email";
		} else {
			$second = "second_phone";
		}
		$clients = \think\Db::name("clients");
		$clients->field("id,phone_code,phonenumber,email,password,second_verify,status,username");
		$clients->where("phonenumber=\"" . $data["account"] . "\" OR email=\"" . $data["account"] . "\"");
		$client = $clients->find();
		if (empty($client["id"])) {
			return json(["status" => 400, "msg" => "Account does not exist"]);
		}
		if ($client["phonenumber"] == $data["account"]) {
			$data["account"] = $client["phone_code"] . $data["account"];
		}
		if (\think\facade\Cache::get("verification_code_" . $second . $data["account"]) == $data["code"]) {
			\think\facade\Cache::set("verification_success" . $data["account"], $data["code"], 1800);
			return json(["status" => 200, "msg" => "success"]);
		} else {
			return json(["status" => 400, "msg" => "Verification code error"]);
		}
	}
	/**
	 * @title 发送验证码
	 * @description 接口说明:发送验证码
	 * @author xiong
	 * @url v1/code
	 * @method POST
	 */
	public function code()
	{
		if (!$this->request->isPost()) {
			return json(["status" => 400, "msg" => "request error"]);
		}
		$data = $this->request->param();
		$action = ["login_phone_code" => intval(configuration("allow_login_code_captcha")), "register_phone" => intval(configuration("allow_register_phone_captcha")), "register_email" => intval(configuration("allow_register_email_captcha")), "pwreset_phone" => intval(configuration("allow_phone_forgetpwd_captcha")), "pwreset_email" => intval(configuration("allow_email_forgetpwd_captcha")), "second_phone" => 0, "second_email" => 0, "bind_phone" => intval(configuration("allow_phone_bind_captcha")), "bind_email" => intval(configuration("allow_email_bind_captcha")), "login_notice_phone" => intval(configuration("allow_cancel_sms_captcha")), "login_notice_email" => intval(configuration("allow_cancel_email_captcha"))];
		if (empty($data["uid"]) && empty($data["action"])) {
			return json(["status" => 400, "msg" => "Captcha supported methods are required"]);
		}
		if (empty($data["uid"]) && $action[$data["action"]] === null) {
			return json(["status" => 400, "msg" => "Captcha supported method does not exist"]);
		}
		if ($data["action"] != "bind_phone" && $data["action"] != "bind_email") {
			$clients = \think\Db::name("clients");
			$clients->field("id,phone_code,phonenumber,email,password,second_verify,status,username");
			if ($data["type"] == "phone") {
				$clients->where("phonenumber", $data["account"]);
			} elseif ($data["type"] == "email") {
				$clients->where("email", $data["account"]);
			}
			$client = $clients->find();
			if ($data["action"] != "register_email" && $data["action"] != "register_phone") {
				if (empty($client["id"])) {
					return json(["status" => 400, "msg" => "Account does not exist"]);
				}
			} else {
				if ($data["action"] != "register_email" || $data["action"] != "register_phone") {
					if (!empty($client["id"])) {
						return json(["status" => 400, "msg" => "Account already exists and cannot be registered"]);
					}
				}
			}
		}
		if ($data["type"] == "phone") {
			if (empty($data["uid"]) && strpos($data["action"], "phone") === false) {
				return json(["status" => 400, "msg" => "Send type and verification code supported way than match"]);
			}
			if (!!\think\facade\Cache::get("verification_code_time_" . $data["account"])) {
				return json(["status" => 400, "msg" => "can only be sent once per minute"]);
			}
			$phone = $data["account"];
			$phone_code = $data["phone_code"];
			if (!configuration("shd_allow_sms_send_global") && !empty($phone_code) && $phone_code != "+86") {
				return json(["status" => 400, "msg" => "International SMS is turned off"]);
			} else {
				if (!configuration("shd_allow_sms_send") && (empty($phone_code) || $phone_code == "+86")) {
					return json(["status" => 400, "msg" => "Domestic SMS has been turned off"]);
				}
			}
			$validate = new \think\Validate(["account" => "require|length:4,11"]);
			$validate->message(["account.require" => "Mobile number cannot be empty", "account.length" => "Phone length is 4-11 digits"]);
			if (!$validate->check($data)) {
				return json(["status" => 400, "msg" => $validate->getError()]);
			}
			if (!!configuration("is_captcha") && $action[$data["action"]] === 1) {
				if (empty($data["captcha"])) {
					return json(["status" => 400, "msg" => "Graphic verification code cannot be empty"]);
				} else {
					if (!\think\facade\Cache::get("code_" . $data["idtoken"])) {
						return json(["status" => 400, "msg" => "Graphic verification code is invalid"]);
					} else {
						if (\think\facade\Cache::get("code_" . $data["idtoken"]) != strtoupper($data["captcha"])) {
							return json(["status" => 400, "msg" => "Graphic verification code is wrong"]);
						}
					}
				}
			}
			$clients = \think\Db::name("clients")->where("phonenumber", $phone)->find();
			if ($phone_code == "+86" || $phone_code == "86" || empty($phone_code)) {
				$phone = $phone;
			} else {
				if (substr($phone_code, 0, 1) == "+") {
					$phone = substr($phone_code, 1) . $phone;
				} else {
					$phone = $phone_code . $phone;
				}
			}
			if (cmf_check_mobile($phone)) {
				$code = mt_rand(100000, 999999);
				$params = ["code" => $code];
				$sms = new \app\common\logic\Sms();
				$ret = sendmsglimit($phone);
				if ($ret["status"] == 400) {
					return json(["status" => 400, "msg" => "Failed to send:" . $ret["msg"]]);
				}
				$result = $sms->sendSms(8, $phone, $params, false, $clients["id"]);
				if ($result["status"] == 200) {
					$sendmsglimit = ["ip" => get_client_ip6(), "phone" => $phone, "time" => time()];
					\think\Db::name("sendmsglimit")->insertGetId($sendmsglimit);
					if (empty($phone_code)) {
						$account = "86" . $data["account"];
					} else {
						$account = str_replace("+", "", $phone_code) . $data["account"];
					}
					\think\facade\Cache::set("verification_code_" . $data["action"] . $account, $code, 300);
					\think\facade\Cache::set("verification_code_time_" . $data["account"], $code, 60);
					return json(["status" => 200, "msg" => "Verification code sent successfully"]);
				} else {
					return json(["status" => 400, "msg" => "Failed to send verification code"]);
				}
			} else {
				return json(["status" => 400, "msg" => "please enter a valid phone number"]);
			}
		} elseif ($data["type"] == "email") {
			if (empty($data["uid"]) && strpos($data["action"], "email") === false) {
				return json(["status" => 400, "msg" => "Send type and verification code supported way than match"]);
			}
			if (!!\think\facade\Cache::get("verification_code_time_" . $data["account"])) {
				return json(["status" => 400, "msg" => "can only be sent once per minute"]);
			}
			$email = $data["account"];
			$key = "email_" . get_client_ip6();
			if (\think\facade\Cache::has($key)) {
				\think\facade\Cache::inc($key);
				$tmp = \think\facade\Cache::get($key);
				if ($tmp >= 10) {
					return json(["status" => 400, "msg" => "Only send five times in five minutes"]);
				}
			} else {
				\think\facade\Cache::set($key, 1, 300);
			}
			if (!!configuration("is_captcha") && $action[$data["action"]] === 1) {
				if (empty($data["captcha"])) {
					return json(["status" => 400, "msg" => "Graphic verification code cannot be empty"]);
				} else {
					if (!\think\facade\Cache::get("code_" . $data["idtoken"])) {
						return json(["status" => 400, "msg" => "Graphic verification code is invalid"]);
					} else {
						if (\think\facade\Cache::get("code_" . $data["idtoken"]) != strtoupper($data["captcha"])) {
							return json(["status" => 400, "msg" => "Graphic verification code is wrong"]);
						}
					}
				}
			}
			if (\think\facade\Validate::isEmail($email)) {
				$code = mt_rand(100000, 999999);
				$email_logic = new \app\common\logic\Email();
				$result = $email_logic->sendEmailCode($email, $code);
				if ($result["status"] == "success") {
					\think\facade\Cache::set("verification_code_" . $data["action"] . $data["account"], $code, 300);
					\think\facade\Cache::set("verification_code_time_" . $data["account"], $code, 60);
					return json(["status" => 200, "msg" => "Verification code sent successfully"]);
				} else {
					return json(["status" => 400, "msg" => "Failed to send verification code"]);
				}
			} else {
				return json(["status" => 400, "msg" => "Email format error"]);
			}
		} else {
			return json(["status" => 400, "msg" => "The requested type parameter values are only mobile and email."]);
		}
	}
	/**
	 * @title 获取图形验证码图片
	 * @description 接口说明:获取图形验证码图片
	 * @author xiong
	 * @url v1/captcha
	 * @method GET
	 */
	public function captcha()
	{
		$is_captcha = configuration("is_captcha");
		if (empty($is_captcha)) {
			return json(["status" => 400, "msg" => "Verification code is not turned on"]);
		} else {
			$idtoken = md5(microtime() . rand(10000000, 99999999));
			\think\facade\Cache::set($idtoken, $idtoken, 1800);
			$captcha_length = configuration("captcha_length");
			$captcha_combination = configuration("captcha_combination");
			$config = json_decode(configuration("captcha_configuration"), true) ?: [];
			$captcha = new \think\captcha\Captcha($config);
			if ($captcha_combination == 1) {
				$captcha->__set("codeSet", "2345678");
			} elseif ($captcha_combination == 2) {
				$captcha->__set("codeSet", "2345678abcdefhijkmnpqrstuvwxyzABCDEFGHJKLMNPQRTUVWXY");
			} elseif ($captcha_combination == 3) {
				$captcha->__set("codeSet", "abcdefhijkmnpqrstuvwxyzABCDEFGHJKLMNPQRTUVWXY");
			}
			$captcha->__set("length", $captcha_length);
			$re = $captcha->entry("code_" . $idtoken);
			\think\facade\Cache::set("code_" . $idtoken, $GLOBALS["code"], 1800);
			$data["img"] = "data:png;base64," . base64_encode($re->getData());
			$data["idtoken"] = $idtoken;
			return json(["status" => 200, "data" => $data]);
		}
	}
	/**
	 * @title 获取支付方式
	 * @description 接口说明:获取支付方式
	 * @author xiong
	 * @url v1/gateway
	 * @method GET
	 */
	public function gateway()
	{
		$gateway = gateway_list();
		foreach ($gateway as $k => $v) {
			$gateway_new[$k]["name"] = $v["name"];
			$gateway_new[$k]["title"] = $v["title"];
			$gateway_new[$k]["img"] = str_replace("\r\n", "", $v["author_url"]);
		}
		return json(["status" => 200, "data" => $gateway_new]);
	}
}