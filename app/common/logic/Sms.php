<?php

namespace app\common\logic;

class Sms
{
	public function __construct()
	{
		if (!configuration("shd_allow_sms_send")) {
			return true;
		}
	}
	public function send($param, $templateParam, $rangeType)
	{
		if (empty($param["content"])) {
			$result["status"] = "error";
			$result["msg"] = "手机号错误或者模板未审核通过或者未设置该类型的模板";
			return $result;
		}
		if ($rangeType == 2) {
			$sms_operator = $param["sms_operator"];
		} else {
			$sms_operator = $this->getSmsOperator($rangeType);
			if (!$sms_operator) {
				$result["status"] = "error";
				$result["msg"] = "短信接口未开启！";
				return $result;
			}
		}
		$sms["template_id"] = $param["template_id"];
		$sms["content"] = $param["content"];
		$sms["mobile"] = $param["mobile"];
		$sms["templateParam"] = $templateParam;
		$sms["config"] = pluginConfig(ucfirst($sms_operator), "sms");
		if ($rangeType == 0) {
			$sendsms = "sendCnSms";
		} elseif ($rangeType == 1) {
			$sendsms = "sendGlobalSms";
		} elseif ($rangeType == 2) {
			$sendsms = "sendCnProSms";
		}
		$result = zjmfhook(ucfirst($sms_operator), "sms", $sms, $sendsms);
		return $result;
	}
	public function mobile86($rangeType, $mobile)
	{
		if ($rangeType == 0 || $rangeType == 2) {
			if (strpos($mobile, "86") === 0) {
				$mobile = substr($mobile, 2);
			} elseif (strpos($mobile, "+86") === 0) {
				$mobile = substr($mobile, 3);
			}
		} else {
			if ($rangeType == 1) {
				$mobile = str_replace("-", "", $mobile);
				$mobile = "+" . str_replace("+", "", $mobile);
			}
		}
		return $mobile;
	}
	public function rangeType($phone)
	{
		$rangeType = preg_match("/^((\\+86)|(86))?[1][3456789][0-9]{9}\$/", $phone) ? 0 : 1;
		return $rangeType;
	}
	public function sms_log($type, $range_type, $phone, $params, $uid = "", $status = 1, $admin = 0, $wx = 0, $msg = "", $content = "")
	{
		$sms_operator = configuration("sms_operator");
		if (strpos($type, "msgid") !== false) {
			$msgid_type = explode("_", $type);
			$message_template = \think\Db::name("message_template")->field("template_id,content")->where("id", $msgid_type[1])->find();
		} else {
			$message_template = \think\Db::name("message_template_link")->alias("a")->field("b.template_id,b.content")->join("message_template b", "b.id = a.sms_temp_id")->where("a.type", $type)->where("a.range_type", $range_type)->where("a.sms_operator", $sms_operator)->find();
		}
		$message_template["content"] = $content ? $content : $message_template["content"];
		$log_data = ["uid" => $uid ?? "", "params" => json_encode($params) ?? "", "phone_code" => $phone_code ?? "", "phone" => $phone ?? "", "template_code" => $message_template["template_id"] ?? "", "content" => $message_template["content"] ?? "", "status" => $status, "create_time" => time(), "is_admin" => $admin, "is_wx" => $wx, "fail_reason" => empty($msg) ? "" : $msg, "ip" => get_client_ip6(), "port" => get_remote_port()];
		return \think\Db::name("message_log")->insert($log_data);
	}
	public function sendSmsForMarerking($phone, $msgid, $param, $sync = false, $uid = "", $delay_time = 0, $is_market = false)
	{
		if (configuration("shd_allow_sms_send_queue")) {
			$_param["phone"] = $phone;
			$_param["type"] = 9;
			$_param["uid"] = $uid;
			$_param["param"] = $param;
			$_param["sync"] = $sync;
			$_param["delay_time"] = $delay_time;
			$_param["is_market"] = $is_market;
			\app\queue\job\SendSms::push($_param);
			return ["status" => 200, "msg" => lang("SEND SUCCESS"), "data" => true];
		}
		return $this->sendSmsForMarerkingFinal($phone, $msgid, $param, $sync, $uid, $delay_time, $is_market);
	}
	public function sendSmsForMarerkingFinal($phone, $msgid, $param, $sync = false, $uid = "", $delay_time = 0, $is_market = false)
	{
		$type = 9;
		if ($sync) {
			$param["phone"] = $phone;
			$param["uid"] = $uid;
			$param["msgid"] = $msgid;
			$param["queue_type"] = "sms_queue";
			$isPushed = \think\Queue::later($delay_time, "app\\common\\job\\SendActivationMarketing", json_encode($param), "SendActivationMarketing");
			if ($isPushed !== false) {
				\think\facade\Log::record("sms_queue_start" . json_encode($param), "info");
			} else {
				\think\facade\Log::record("sms_queue_start" . json_encode($param), "info");
			}
			return true;
		}
		if ($is_market) {
			$rangeType = 2;
		} else {
			$rangeType = preg_match("/^((\\+86)|(86))?[1][3456789][0-9]{9}\$/", $phone) ? 0 : 1;
			if ($rangeType == 0) {
				if (configuration("shd_allow_sms_send") == 0) {
					return jsonrule(["status" => 400, "msg" => lang("国内短信功能已关闭")]);
				}
			} elseif ($rangeType == 1) {
				if (configuration("shd_allow_sms_send_global") == 0) {
					return jsonrule(["status" => 400, "msg" => lang("国际短信功能已关闭")]);
				}
			}
			if (!($sms_operator = $this->getSmsOperator($rangeType))) {
				return ["status" => 400, "msg" => lang("SMS_OPERATOR_NO_EXIST")];
			}
		}
		$message_template = \think\Db::name("message_template")->where("id", $msgid)->find();
		$templateParam = $param;
		$phone = $this->mobile86($rangeType, $phone);
		$params = ["content" => $message_template["content"], "template_id" => $message_template["template_id"], "sms_operator" => $message_template["sms_operator"], "mobile" => $phone];
		$result = $this->send($params, $templateParam, $rangeType);
		\think\facade\Log::record(json_encode($result) . "data" . json_encode($param) . " uid:" . $type . " phone:" . $phone, "sms");
		if (isset($result["status"]) && $result["status"] == "success") {
			$this->sms_log("msgid_" . $msgid, $rangeType, $phone, $param, $uid, 1, 0, 0, "", $result["content"] ?? "");
			return ["status" => 200, "msg" => lang("SEND SUCCESS"), "data" => $result];
		} else {
			$this->sms_log("msgid_" . $msgid, $rangeType, $phone, $param, $uid, 0, 0, 0, $result["msg"], $result["content"] ?? "");
			return ["status" => 400, "msg" => lang("SEND FAIL") . ":" . (isset($result["status"]) ? $result["msg"] : ""), "data" => $result];
		}
	}
	public function sendSms($type, $phone, $param, $sync = false, $uid = "", $delay_time = 0, $is_market = false)
	{
		$rangeType = preg_match("/^((\\+86)|(86))?[1][3456789][0-9]{9}\$/", $phone) ? 0 : 1;
		if ($rangeType == 0) {
			if (configuration("shd_allow_sms_send") == 0) {
				return jsonrule(["status" => 400, "msg" => lang("国内短信功能已关闭")]);
			}
		} elseif ($rangeType == 1) {
			if (configuration("shd_allow_sms_send_global") == 0) {
				return jsonrule(["status" => 400, "msg" => lang("国际短信功能已关闭")]);
			}
		}
		$client = \think\Db::name("clients")->where("id", $uid)->find();
		if ($client["send_close"] && in_array($type, [1, 2, 3, 4, 5, 6, 7, 13, 14, 15, 16, 17, 18, 19, 24, 25, 26, 27, 28, 30, 31, 32, 34, 35, 36, 37, 39])) {
			return ["status" => 400, "msg" => lang("短信功能已关闭")];
		}
		if (configuration("shd_allow_sms_send_queue")) {
			$_param["phone"] = $phone;
			$_param["type"] = $type;
			$_param["uid"] = $uid;
			$_param["sync"] = $sync;
			$_param["param"] = $param;
			$_param["delay_time"] = $delay_time;
			$_param["is_market"] = $is_market;
			\app\queue\job\SendSms::push($_param);
			return ["status" => 200, "msg" => lang("SEND SUCCESS"), "data" => true];
		}
		return $this->sendSmsFinal($type, $phone, $param, $sync, $uid, $delay_time, $is_market);
	}
	public function sendSmsFinal($type, $phone, $param, $sync = false, $uid = "", $delay_time = 0, $is_market = false)
	{
		$rangeType = preg_match("/^((\\+86)|(86))?[1][3456789][0-9]{9}\$/", $phone) ? 0 : 1;
		if ($rangeType == 0) {
			if (configuration("shd_allow_sms_send") == 0) {
				return jsonrule(["status" => 400, "msg" => lang("国内短信功能已关闭")]);
			}
		} elseif ($rangeType == 1) {
			if (configuration("shd_allow_sms_send_global") == 0) {
				return jsonrule(["status" => 400, "msg" => lang("国际短信功能已关闭")]);
			}
		}
		if (!($sms_operator = $this->getSmsOperator($rangeType))) {
			return ["status" => 400, "msg" => lang("SMS_OPERATOR_NO_EXIST")];
		}
		$templateid = $this->getTemplateCodeByType($type, $sms_operator, $rangeType);
		$templateParamSystem = ["system_companyname" => configuration("company_name"), "system_web_url" => configuration("system_url"), "send_time" => date("Y-m-d H:i:s")];
		if (!empty($uid)) {
			$user_info = \think\Db::name("clients")->where("id", $uid)->find();
			$templateParamUserInfo = ["username" => $user_info["username"], "account" => $user_info["username"] . "(" . $user_info["phonenumber"] . ")", "user_company" => $user_info["companyname"], "account_email" => $user_info["email"], "login_data_time" => date("Y-m-d H:i:s", $user_info["lastlogin"]), "action_ip" => $user_info["lastloginip"], "time" => date("Y-m-d H:i:s", $user_info["lastlogin"]), "epw_type" => "手机", "epw_account" => $user_info["phonenumber"], "address" => $user_info["lastloginip"], "register_time" => date("Y-m-d H:i:s", $user_info["create_time"]), "user_address" => $user_info["address1"], "qq" => $user_info["qq"]];
			$templateParamSystem = array_merge($templateParamSystem, $templateParamUserInfo);
		}
		$templateParam = array_merge($templateParamSystem, $param);
		$phone = $this->mobile86($rangeType, $phone);
		$params = ["content" => $templateid["content"], "template_id" => $templateid["template_id"], "mobile" => $phone];
		$result = $this->send($params, $templateParam, $rangeType);
		$msgid = $templateid["id"];
		\think\facade\Log::record(json_encode($result) . "data" . json_encode($param) . " uid:" . $type . " phone:" . $phone, "sms");
		if (isset($result["status"]) && $result["status"] == "success") {
			$this->sms_log("msgid_" . $msgid, $rangeType, $phone, $param, $uid, 1, 0, 0, "", $result["content"] ?? "");
			return ["status" => 200, "msg" => lang("SEND SUCCESS"), "data" => $result];
		} else {
			$this->sms_log("msgid_" . $msgid, $rangeType, $phone, $param, $uid, 0, 0, 0, $result["msg"], $result["content"] ?? "");
			return ["status" => 400, "msg" => lang("SEND FAIL") . ":" . (isset($result["status"]) ? $result["msg"] : ""), "data" => $result];
		}
	}
	public function sendBatchSms($type, $param)
	{
		$inland = $international = $inlandparam = $internationalparam = $inlandsubmail = $internationalsubmail = $inlandsignname = $internationalsignname = [];
		if (!($sms_operator = $this->getSmsOperator())) {
			return json(["status" => 400, "msg" => lang("SMS_OPERATOR_NO_EXIST")]);
		}
		$signNameInland = configuration("aliyun_mobile_signature");
		$signNameInternational = configuration("aliyun_mobile_intersignature");
		if (!$signNameInland || !$signNameInternational) {
			return json(["status" => 400, "msg" => lang("SIGN_NAME_NO_EXIST")]);
		}
		foreach ($param as $key => $phone) {
			$to = $phone["to"];
			if (!$this->extractPhoneNumber($to)) {
				return json(["status" => 400, "msg" => $to . lang("MOBILE_INVALID")]);
			}
			$param[$key]["to"] = substr($to, 0, 1) == "+" ? substr($to, 1) : $to;
			$rangeType = preg_match("/^((\\+86)|(86))?[1][3456789][0-9]{9}\$/", $to) ? 0 : 1;
			if ($rangeType) {
				$internationalsubmail[$key] = $phone;
				array_push($international, $phone["to"]);
				array_push($internationalsignname, $signNameInternational);
				array_push($internationalparam, $phone["vars"]);
			} else {
				$inlandsubmail[$key] = $phone;
				array_push($inland, $phone["to"]);
				array_push($inlandsignname, $signNameInland);
				array_push($inlandparam, $phone["vars"]);
			}
		}
		$templateid = $this->getTemplateCodeByType($type, $sms_operator, 0);
		$templateid2 = $this->getTemplateCodeByType($type, $sms_operator, 1);
		if (!empty($templateid["template_id"]) || !empty($templateid2["template_id"])) {
			return json(["status" => 400, "msg" => lang("MESSAGE_TEMPLATE_MISS")]);
		}
		$submail_msg = [];
		if (!empty($result)) {
			if (is_array($result[0])) {
				foreach ($result as $value) {
					array_push($submail_msg, $value["status"] == "success" ? lang("INLAND_MOBILE_SEND_SUCCESS", ["phone" => $value["to"]]) : lang("INLAND_MOBILE_SEND_FAIL", ["phone" => isset($value["to"]) ? $value["to"] : ""]));
				}
			} else {
				array_push($submail_msg, lang("INLAND_MOBILE_SEND_FAIL", ["phone" => isset($result["to"]) ? $result["to"] : ""]) . ":" . (isset($result["msg"]) ? $result["msg"] : ""));
			}
		}
		if (!empty($result2)) {
			if (is_array($result2[0])) {
				foreach ($result2 as $value2) {
					array_push($submail_msg, $value2["status"] == "success" ? lang("INTERNATIONAL_MOBILE_SEND_SUCCESS", ["phone" => $value2["to"]]) : lang("INTERNATIONAL_MOBILE_SEND_FAIL", ["phone" => isset($value2["to"]) ? $value2["to"] : ""]));
				}
			} else {
				array_push($submail_msg, lang("INTERNATIONAL_MOBILE_SEND_FAIL", ["phone" => isset($result2["to"]) ? $result2["to"] : ""]) . ":" . (isset($result2["msg"]) ? $result2["msg"] : ""));
			}
		}
		return json(["status" => 200, "msg" => $submail_msg]);
	}
	/**
	 * 校验国际手机号码格式
	 * @param  {String} $number      [国际区号+手机号]
	 * @return {[bool]}
	 */
	public function extractPhoneNumber($number)
	{
		if (!empty($number)) {
			if (substr($number, 0, 1) == "+") {
				$fullPhone = $number;
			} else {
				$fullPhone = "+" . $number;
			}
		} else {
			return false;
		}
		try {
			$phoneNumberUtil = PhoneNumberUtil::getInstance();
			$phoneNumberObject = $phoneNumberUtil->parse($fullPhone, null);
			if ($phoneNumberUtil->isValidNumber($phoneNumberObject)) {
				return true;
			} else {
				return false;
			}
		} catch (\Exception $e) {
			return false;
		}
	}
	public function getSmsOperator($rangeType)
	{
		$plugin = \think\Db::name("plugin")->field("name")->where("module", "sms")->where("status", 1)->select()->toArray();
		if (!$plugin) {
			return false;
		}
		foreach ($plugin as $v) {
			$allowedSmsOperator[] = strtolower($v["name"]);
		}
		if ($rangeType == 0) {
			$smsOperator = configuration("sms_operator");
		} elseif ($rangeType == 1) {
			$smsOperator = configuration("sms_operator_global");
		}
		if (!empty($smsOperator)) {
			if (!in_array($smsOperator, $allowedSmsOperator)) {
				return false;
			}
			return $smsOperator;
		} else {
			return false;
		}
	}
	private function getTemplateCode($type, $smsOperator, $rangeType)
	{
		$templateid = \think\Db::name("message_template_link")->field("mt.template_id")->alias("mtl")->leftJoin("message_template mt", "mt.id = mtl.sms_temp_id")->where("mtl.range_type", $rangeType)->where("mtl.sms_operator", $smsOperator)->where("mtl.type", $type)->where("mt.status", 2)->find();
		return $templateid;
	}
	private function getTemplateCodeByType($type, $smsOperator, $rangeType)
	{
		$templateid = \think\Db::name("message_template_link")->alias("mtl")->field("mt.template_id,mt.content,mt.id")->leftJoin("message_template mt", "mt.id = mtl.sms_temp_id")->where("mtl.range_type", $rangeType)->where("mtl.sms_operator", $smsOperator)->where("mtl.type", $type)->where("mt.status", 2)->find();
		return $templateid;
	}
	public function getBaseArgphone()
	{
		return [["name" => lang("EMAIL_TEMPLATE_COMPANY_NAME"), "arg" => "{system_companyname}"], ["name" => lang("EMAIL_TEMPLATE_CODE"), "arg" => "{code}"], ["name" => lang("EMAIL_TEMPLATE_SEND_TIME"), "arg" => "{send_time}"], ["name" => lang("EMAIL_TEMPLATE_SYSTEM_URL"), "arg" => "{system_url}"], ["name" => lang("EMAIL_TEMPLATE_SYSTEM_WEB_URL"), "arg" => "{system_web_url}"], ["name" => lang("EMAIL_TEMPLATE_COMPANY_LOGO_URL"), "arg" => "{system_email_logo_url}"]];
	}
	public function getReplaceArgphone($type)
	{
		switch ($type) {
			case "general":
				$args = [["name" => lang("EMAIL_TEMPLATE_CLIENT_NAME"), "arg" => "{username}"], ["name" => lang("EMAIL_TEMPLATE_CLIENT_COMPANY_NAME"), "arg" => "{user_company}"], ["name" => lang("EMAIL_TEMPLATE_CLIENT_EMAIL"), "arg" => "{account_email}"], ["name" => lang("EMAIL_TEMPLATE_CLIENT_EMAIL"), "arg" => "{login_data_time}"], ["name" => lang("EMAIL_TEMPLATE_CLIENT_EMAIL"), "arg" => "{action_ip}"]];
				break;
			case "product":
				$args = [["name" => lang("EMAIL_TEMPLATE_PRODUCT_NAME"), "arg" => "{product_name}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_HOSTNAME"), "arg" => "{hostname}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_USER"), "arg" => "{product_user}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_PRODUCT_MAINIP"), "arg" => "{product_mainip}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_PASSWD"), "arg" => "{product_passwd}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_DCIMBMS_OS"), "arg" => "{product_dcimbms_os}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_ADDONIP"), "arg" => "{product_addonip}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_FIRST_TIME"), "arg" => "{product_first_time}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_END_TIME"), "arg" => "{product_end_time}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_BINLLY_CYCLE"), "arg" => "{product_binlly_cycle}"], ["name" => lang("EMAIL_TEMPLATE_ORDER_CREATE_TIME"), "arg" => "{order_create_time}"], ["name" => lang("EMAIL_TEMPLATE_ORDER_ID"), "arg" => "{order_id}"], ["name" => lang("EMAIL_TEMPLATE_ORDER_TOTAL_FEE"), "arg" => "{order_total_fee}"], ["name" => lang("EMAIL_TEMPLATE_INVOICE_PAID_TIME"), "arg" => "{invoice_paid_time}"], ["name" => lang("自动删除时间"), "arg" => "{product_terminate_time}"]];
				break;
			case "invoice":
				$args = [["name" => lang("EMAIL_TEMPLATE_PRODUCT_NAME"), "arg" => "{product_name}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_HOSTNAME"), "arg" => "{hostname}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_USER"), "arg" => "{product_user}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_PRODUCT_MAINIP"), "arg" => "{product_mainip}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_PASSWD"), "arg" => "{product_passwd}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_DCIMBMS_OS"), "arg" => "{product_dcimbms_os}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_ADDONIP"), "arg" => "{product_addonip}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_FIRST_TIME"), "arg" => "{product_first_time}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_END_TIME"), "arg" => "{product_end_time}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_BINLLY_CYCLE"), "arg" => "{product_binlly_cycle}"], ["name" => lang("EMAIL_TEMPLATE_ORDER_CREATE_TIME"), "arg" => "{order_create_time}"], ["name" => lang("EMAIL_TEMPLATE_ORDER_ID"), "arg" => "{order_id}"], ["name" => lang("EMAIL_TEMPLATE_ORDER_TOTAL_FEE"), "arg" => "{order_total_fee}"], ["name" => lang("EMAIL_TEMPLATE_INVOICE_PAID_TIME"), "arg" => "{invoice_paid_time}"], ["name" => lang("账单id"), "arg" => "{invoiceid}"], ["name" => lang("自动删除时间"), "arg" => "{product_terminate_time}"]];
				break;
			case "support":
				$args = [["name" => lang("EMAIL_TEMPLATE_TICKET_REPLY_TIME"), "arg" => "{ticket_reply_time}"], ["name" => lang("EMAIL_TEMPLATE_TICKET_DEPARTMENT"), "arg" => "{ticket_department}"], ["name" => lang("EMAIL_TEMPLATE_TICKET_ATUO_CLOSE_TIME"), "arg" => "{auto_ticket_close_time}"], ["name" => lang("EMAIL_TEMPLATE_TICKET_TITLE"), "arg" => "{ticketnumber_tickettitle}"], ["name" => lang("EMAIL_TEMPLATE_TICKET_CREATETIME"), "arg" => "{ticket_createtime}"], ["name" => lang("EMAIL_TEMPLATE_TICKET_PRIORITY"), "arg" => "{ticket_level}"]];
				break;
			case "notification":
				$args = [];
				break;
			case "admin":
				$args = [["name" => lang("管理员名称"), "arg" => "{admin_account_name}"], ["name" => lang("登录时间"), "arg" => "{admin_login_data_time}"], ["name" => lang("登录IP"), "arg" => "{admin_action_ip}"]];
				break;
		}
		return $args;
	}
	/**
	 * 替换短信模板中的参数信息
	 */
	public function replaceSMSContentParams($user_info, $host, $template, $sms_operator, $order)
	{
		$template_content = $template["content"];
		if ($sms_operator == "submail") {
			preg_match_all("/(?<=@var\\()[^\\)]+/", $template_content, $matches);
		}
		if ($sms_operator == "aliyun") {
			preg_match_all("/(?<=\\\$\\{)[^\\}]+/", $template_content, $matches);
		}
		$admin_id = session("ADMIN_ID");
		$admin_info = \think\Db::name("user")->where("id", $admin_id)->value("user_login");
		$var = $matches[0];
		$newvar = [];
		$system_dir = config("database.admin_application") ?: "admin";
		foreach ($var as $key => $value) {
			$key = $value;
			switch ($value) {
				case "system_companyname":
					$v = configuration("company_name");
					break;
				case "code":
					$v = mt_rand(100000, 999999);
					break;
				case "send_time":
					$v = date("Y-m-d H:i:s");
					break;
				case "system_url":
					$v = configuration("system_url") . "/" . $system_dir;
					break;
				case "system_web_url":
					$v = configuration("system_url");
					break;
				case "system_email_logo_url":
					$v = configuration("logo_url_home");
					break;
				case "username":
					$v = $user_info["username"];
					break;
				case "account":
					$v = $user_info["username"] . "(" . $user_info["phonenumber"] . ")";
					break;
				case "user_company":
					$v = $user_info["companyname"];
					break;
				case "account_email":
					$v = $user_info["email"];
					break;
				case "login_data_time":
					$v = date("Y-m-d H:i:s", $user_info["lastlogin"]);
					break;
				case "action_ip":
					$v = $user_info["lastloginip"];
					break;
				case "time":
					$v = date("Y-m-d H:i:s", $user_info["lastlogin"]);
					break;
				case "epw_type":
					$v = "手机";
					break;
				case "epw_account":
					$v = $user_info["phonenumber"];
					break;
				case "address":
					$v = $user_info["lastloginip"];
					break;
				case "admin_account_name":
					$v = $admin_info["user_login"];
					break;
				case "admin_login_data_time":
					$v = date("Y-m-d H:i:s", $admin_info["last_act_time"]);
					break;
				case "admin_action_ip":
					$v = $admin_info["last_login_ip"];
					break;
				case "product_name":
					$v = $host["name"];
					break;
				case "hostname":
					$v = $host["domain"];
					break;
				case "product_user":
					$v = $host["username"];
					break;
				case "product_passwd":
					$v = aesPasswordDecode($host["password"]);
					break;
				case "product_mainip":
					$v = $host["dedicatedip"];
					$v .= $host["port"] ? ":" . $host["port"] : "";
					break;
				case "product_dcimbms_os":
					$v = $host["os"];
					break;
				case "product_addonip":
					$v = $host["assignedips"];
					break;
				case "product_first_time":
					$v = date("Y-m-d H:i:s", $host["create_time"]);
					break;
				case "product_end_time":
					$v = date("Y-m-d H:i:s", $host["nextduedate"]);
					break;
				case "product_binlly_cycle":
					$billing_cycle = config("billing_cycle");
					$v = $billing_cycle[$host["billingcycle"]];
					break;
				case "order_create_time":
					$v = date("Y-m-d H:i:s", $order["create_time"]);
					break;
				case "order_id":
					$v = $order["id"];
					break;
				case "order_total_fee":
					$v = $order["amount"];
					break;
				case "invoice_paid_time":
					$v = date("Y-m-d H:i:s", $order["paid_time"]);
					break;
				case "invoiceid":
					$v = $order["invoiceid"];
					break;
				case "total":
					$v = $order["subtotal"];
					break;
				case "product_terminate_time":
					$v = date("Y-m-d H:i:s", $host["nextduedate"]);
					break;
				default:
					break;
			}
			$newvar[$key] = $v;
		}
		return $newvar;
	}
	/**
	 * 短信模板中的参数信息
	 */
	public function templateParam($user_info = [], $host = [], $order = [])
	{
		$admin_id = session("ADMIN_ID");
		$admin_info = \think\Db::name("user")->where("id", $admin_id)->value("user_login");
		$system_dir = config("database.admin_application") ?: "admin";
		$var = ["system_companyname" => configuration("company_name"), "code" => mt_rand(100000, 999999), "send_time" => date("Y-m-d H:i:s"), "system_url" => configuration("system_url") . "/" . $system_dir, "system_web_url" => configuration("system_url"), "system_email_logo_url" => configuration("logo_url_home"), "username" => $user_info["username"], "register_time" => date("Y-m-d H:i:s", $user_info["create_time"]), "user_address" => $user_info["address1"], "qq" => $user_info["qq"], "account" => $user_info["username"] . "(" . $user_info["phonenumber"] . ")", "user_company" => $user_info["companyname"], "account_email" => $user_info["email"], "login_data_time" => date("Y-m-d H:i:s", $user_info["lastlogin"]), "action_ip" => $user_info["lastloginip"], "time" => date("Y-m-d H:i:s", $user_info["lastlogin"]), "epw_type" => "手机", "epw_account" => $user_info["phonenumber"], "address" => $user_info["lastloginip"], "admin_account_name" => $admin_info["user_login"], "admin_login_data_time" => date("Y-m-d H:i:s", $admin_info["last_act_time"]), "admin_action_ip" => $admin_info["last_login_ip"], "product_name" => $host["name"], "hostname" => $host["domain"], "product_user" => $host["username"], "product_passwd" => aesPasswordDecode($host["password"]), "product_mainip" => $host["dedicatedip"], "product_dcimbms_os" => $host["os"], "product_addonip" => $host["assignedips"], "description" => $host["suspendreason"], "product_first_time" => date("Y-m-d H:i:s", $host["create_time"]), "product_end_time" => date("Y-m-d H:i:s", $host["nextduedate"]), "product_binlly_cycle" => config("billing_cycle")[$host["billingcycle"]], "order_create_time" => date("Y-m-d H:i:s", $order["create_time"]), "order_id" => $order["id"], "order_total_fee" => $order["amount"], "invoice_paid_time" => date("Y-m-d H:i:s", $order["paid_time"]), "invoiceid" => $order["invoiceid"], "total" => $order["subtotal"], "product_terminate_time" => date("Y-m-d H:i:s", $host["nextduedate"])];
		$var["product_mainip"] .= $var["port"] ? ":" . $var["port"] : "";
		return $var;
	}
	/**
	 * 发送短信息前的各种操作
	 * @param array $send_client 发送短信的客户信息
	 * @param string $send_type 推送营销信息的方式：clients-客户，clients_and_host-商品
	 * @param string $msgid 短信模板id
	 * @param $message_template_link
	 * @param $temp
	 * @param $type
	 * @return array
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function sendSmsBefore($send_client = [], $send_type = "clients", $msgid = "", $template, $sms_operator, $send_sync, $delay_time = 0)
	{
		$user_info = \think\Db::name("clients")->where("id", $send_client["id"])->find();
		$host = [];
		$order = [];
		if ($send_type == "clients_and_host") {
			$host = \think\Db::name("host")->alias("a")->field("a.id,a.uid,a.productid,a.domain,a.domainstatus,a.regdate,b.welcome_email,b.type,c.email,a.billingcycle,a.nextduedate,a.billingcycle")->field("b.pay_type,b.name,a.dedicatedip,a.username,a.password,a.os,a.assignedips,a.create_time,a.nextinvoicedate,a.suspendreason")->field("a.termination_date,a.port")->leftJoin("products b", "a.productid=b.id")->leftJoin("clients c", "a.uid=c.id")->where("a.id", $send_client["host_id"])->find();
			$order = \think\Db::name("orders")->alias("o")->leftJoin("invoices i", "i.id = o.invoiceid")->leftJoin("invoice_items im", "i.id = im.invoice_id")->where("im.rel_id", $send_client["host_id"])->field("o.create_time,o.id,o.amount,i.paid_time,o.invoiceid,i.subtotal")->find();
		}
		$newvar = $this->templateParam($user_info, $host, $order);
		$result = $this->sendSmsForMarerking($send_client["mobile"], $msgid, $newvar, $send_sync, $send_client["id"], $delay_time, true);
		if (!$send_sync) {
			$result_status = $result["status"] == 200 ? true : false;
		} else {
			$result_status = $result;
		}
		if ($result_status) {
			return ["status" => 200, "msg" => lang("发送成功,请注意查收")];
		} else {
			return ["status" => 400, "msg" => lang("发送失败,失败原因:") . $result["msg"]];
		}
	}
}