<?php

namespace app\common\logic;

class Email
{
	const ATTACHMENTS_ADDRESS = "./upload/common/email/";
	private $isDebug = 0;
	public $mail;
	public $is_admin = false;
	public function __construct()
	{
		$this->mail = new \PHPMailer\PHPMailer\PHPMailer();
	}
	protected function mailConfig()
	{
		$this->mail->SMTPDebug = $this->isDebug;
		$this->mail->isSMTP();
		$this->mail->SMTPAuth = true;
		$this->mail->Timeout = 10;
		$this->mail->Host = configuration("email_host");
		$this->mail->SMTPSecure = configuration("email_smtpsecure");
		$this->mail->Port = configuration("email_port");
		$this->mail->CharSet = configuration("email_charset");
		$this->mail->FromName = configuration("email_fromname");
		$this->mail->Username = configuration("email_username");
		$this->mail->Password = aesPasswordDecode(configuration("email_password"));
		$this->mail->From = configuration("email_systememail");
		$this->mail->isHTML(true);
	}
	protected function getTemplate($templateId, $language = "")
	{
		if ($templateId) {
			$template = \think\Db::name("email_templates")->field("type,subject,message,attachments,fromname,fromemail,copyto,blind_copy_to")->where("id", intval($templateId))->where("disabled", 0)->find();
			$template = array_map(function ($v) {
				return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
			}, $template);
			return $template;
		} else {
			return false;
		}
	}
	protected function getEmail($uid, $type = "")
	{
		if ($uid) {
			$client = \think\Db::name("clients")->field("email")->where("id", intval($uid))->find();
		}
		if ($type == "admin") {
			$client = \think\Db::name("user")->field("user_email as email")->where("id", $uid)->find();
		}
		return isset($client["email"]) ? $client["email"] : null;
	}
	protected function getUid($type, $relid)
	{
		switch ($type) {
			case "general":
				$result["uid"] = $relid;
				break;
			case "product":
				$result = \think\Db::name("host")->field("uid")->where("id", $relid)->find();
				if (empty($result)) {
					$result["uid"] = $relid;
				}
				break;
			case "invoice":
				$result = \think\Db::name("host")->alias("a")->field("a.uid")->leftJoin("orders b", "a.orderid = b.id")->leftJoin("invoices c", "b.invoiceid = c.id")->where("a.id", $relid)->find();
				if (empty($result)) {
					$result["uid"] = $relid;
				}
				break;
			case "support":
				$result = \think\Db::name("ticket")->field("uid")->where("id", $relid)->find();
				break;
			case "admin":
				$result["uid"] = $relid;
				break;
			case "notification":
				$result["uid"] = $relid;
				break;
			case "credit_limit":
				$result = \think\Db::name("invoices")->field("uid")->where("id", $relid)->find();
				if (empty($result)) {
					$result["uid"] = $relid;
				}
				break;
			default:
				$result = false;
		}
		return isset($result["uid"]) ? intval($result["uid"]) : "";
	}
	protected function getOriginalName($attachment)
	{
		$originalName = explode("^", $attachment)[1];
		return $originalName;
	}
	private function getEmailTemplateByName($name, $type, $uid = "")
	{
		if (!$uid) {
			if ($type == "admin") {
				$language = \think\Db::name("user")->where("id", $uid)->value("language");
			} else {
				$language = \think\Db::name("clients")->where("id", $uid)->value("language");
			}
			$language = $language ?? configuration("language");
		} else {
			$language = configuration("language");
		}
		if ($language == "zh-cn") {
			$language = "";
		}
		$type = $type == "credit_limit" ? "invoice" : $type;
		$email_template = \think\Db::name("email_templates")->where("type", $type)->where(function (\think\db\Query $query) use($name) {
			$query->where("name", $name)->whereOr("name_en", $name);
		})->where("disabled", 0)->where("language", $language)->find();
		if (empty($email_template)) {
			$email_template = \think\Db::name("email_templates")->where("type", $type)->where(function (\think\db\Query $query) use($name) {
				$query->where("name", $name)->whereOr("name_en", $name);
			})->where("disabled", 0)->where("language", "")->find();
		}
		return $email_template ?? [];
	}
	public function sendEmailDirct($email, $subject, $message, $attachments = "", $cc = "", $bcc = "")
	{
		if (!$email) {
			return false;
		}
		$_data["email"] = $email;
		$_data["attachments"] = $attachments;
		$_data["content"] = $message;
		$_data["subject"] = $subject;
		$email_operator = configuration("email_operator");
		$_data["config"] = pluginConfig(ucfirst($email_operator), "mail");
		if (!$this->checkEmailOperator($email_operator)) {
			return false;
		}
		hook("before_email_send", ["email" => $email, "subject" => $subject, "content" => $message]);
		$result = zjmfhook(ucfirst($email_operator), "mail", $_data, "send");
		if ($result["status"] == "success") {
			if ($this->is_admin) {
				email_log(0, $subject, $message, $email, $cc, $bcc, 1, "", 1);
			} else {
				email_log(0, $subject, $message, $email, $cc, $bcc);
			}
		} else {
			email_log(0, $subject, $message, $email, $cc, $bcc, 0, $result["msg"]);
		}
		return $result;
	}
	/**
	 * 自定义邮件发送
	 * @param $relid 发送对象id，后续会根据type字段去查找真实的发送对象
	 * @param $subject
	 * @param string $message
	 * @param string $attachments
	 * @param string $type
	 * @param bool $sync
	 * @param string $cc
	 * @param string $bcc
	 * @param int $delay_time 延迟发送时间
	 * @return bool
	 * @throws \PHPMailer\PHPMailer\Exception
	 */
	public function sendEmailDiy($relid, $subject, $message = "", $attachments = "", $type = "product", $sync = false, $cc = "", $bcc = "", $delay_time = 0)
	{
		if ($sync) {
			$param["relid"] = $relid;
			$param["subject"] = $subject;
			$param["type"] = $type;
			$param["message"] = $message;
			$param["attachments"] = $attachments;
			$param["queue_type"] = "email_queue";
			$isPushed = \think\Queue::later($delay_time, "app\\common\\job\\SendActivationMarketing", json_encode($param), "SendActivationMarketing");
			if ($isPushed !== false) {
				\think\facade\Log::record("email_queue_start" . json_encode($param), "info");
			} else {
				\think\facade\Log::record("email_queue_start" . json_encode($param), "info");
			}
			return true;
		}
		$uid = $this->getUid($type, $relid);
		$email = $this->getEmail($uid, $type);
		if (!$email) {
			return false;
		}
		$server_ip = get_client_ip6(1);
		$clients = \think\Db::name("clients")->where("id", $uid)->find();
		$subject = str_replace(["{SYSTEM_COMPANYNAME}", "{ACTION_IP}", "{USERNAME}"], [configuration("company_name") ?? "智简魔方", $server_ip, $clients["username"] ?: ""], $subject);
		$subject = $this->replaceArg($subject, $uid, $type, $relid);
		$this->mail->Subject = $subject;
		$body = $this->replaceArg($message, $uid, $type, $relid);
		$_data["email"] = $email;
		$_data["attachments"] = $attachments;
		$_data["content"] = $body;
		$_data["subject"] = $subject;
		$email_operator = configuration("email_operator");
		$_data["config"] = pluginConfig(ucfirst($email_operator), "mail");
		if (!$this->checkEmailOperator($email_operator)) {
			return false;
		}
		hook("before_email_send", ["email" => $email, "subject" => $subject, "content" => $body]);
		$result = zjmfhook(ucfirst($email_operator), "mail", $_data, "send");
		if ($result["status"] == "success") {
			if ($this->is_admin) {
				email_log($uid, $subject, $body, $email, $cc, $bcc, 1, "", 1, $attachments);
			} else {
				email_log($uid, $subject, $body, $email, $cc, $bcc, 1, "", 0, $attachments);
			}
		} else {
			if ($this->is_admin) {
				email_log($uid, $subject, $body, $email, $cc, $bcc, 0, $result["msg"], 1, $attachments);
			} else {
				email_log($uid, $subject, $body, $email, $cc, $bcc, 0, $result["msg"], 0, $attachments);
			}
			$result["error"] = $result["msg"];
		}
		return $result;
	}
	public function sendEmailBase($relid, $name, $type = "product", $sync = false, $admin = false, $cc = "", $bcc = "", $message = "", $attachments = "", $adminid = "", $ip = "")
	{
		if (configuration("shd_allow_email_send_queue")) {
			$param["relid"] = $relid;
			$param["name"] = $name;
			$param["type"] = $type;
			$param["admin"] = $admin;
			$param["cc"] = $cc;
			$param["bcc"] = $bcc;
			$param["message"] = $message;
			$param["attachments"] = $attachments;
			\app\queue\job\SendMail::push($param);
			return true;
		}
		return $this->sendEmailBaseFinal($relid, $name, $type, $sync, $admin, $cc, $bcc, $message, $attachments, $adminid, $ip);
	}
	public function sendEmailBaseFinal($relid, $name, $type = "product", $sync = false, $admin = false, $cc = "", $bcc = "", $message = "", $attachments = "", $adminid = "", $ip = "")
	{
		$uid = $this->getUid($type, $relid);
		if ($admin) {
			if ($adminid) {
				$admin = \think\Db::name("user")->where("id", $adminid)->find();
			} else {
				$admin = \think\Db::name("user")->where("id", 1)->find();
			}
			$email = $admin["user_email"];
		} else {
			$email = $this->getEmail($uid, $type);
		}
		if (!$email) {
			return false;
		}
		if ($admin && $type == "support") {
			$template = $this->getEmailTemplateByName($name, "admin", $uid);
		} else {
			$template = $this->getEmailTemplateByName($name, $type, $uid);
		}
		$template = array_map(function ($v) {
			return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
		}, $template);
		if (empty($template)) {
			return false;
		}
		$clients = \think\Db::name("clients")->where("id", $uid)->find();
		$subject = str_replace(["{SYSTEM_COMPANYNAME}", "{ACTION_IP}", "{USERNAME}"], [configuration("company_name") ?? "智简魔方", !empty($ip) ? $ip : get_client_ip(0, true), $clients["username"] ?: ""], $template["subject"]);
		if ($type == "support") {
			$ticket = \think\Db::name("ticket")->where("id", $relid)->find();
			$subject = str_replace(["{TICKETNUMBER_TICKETTITLE}"], [$ticket["tid"] . $ticket["title"]], $subject);
		} elseif ($type == "product") {
			$host = \think\Db::name("host")->alias("a")->field("b.name")->leftJoin("products b", "a.productid = b.id")->where("a.id", $relid)->find();
			$subject = str_replace(["{PRODUCT_NAME}"], [$host["name"]], $subject);
		}
		$subject = $this->replaceArg($subject, $uid, $type, $relid);
		$message = empty($message) ? $template["message"] : $message;
		$body = $this->replaceArg($message, $uid, $type, $relid);
		if ($type == "admin" || $admin) {
			$body = str_replace(["{ADMIN_ACCOUNT_NAME}"], [$admin["user_login"] ?? "admin"], $body);
		}
		$_data["email"] = $email;
		$_data["attachments"] = $template["attachments"];
		$_data["content"] = $body;
		$_data["subject"] = $subject;
		$email_operator = configuration("email_operator");
		$_data["config"] = pluginConfig(ucfirst($email_operator), "mail");
		if (!$this->checkEmailOperator($email_operator)) {
			return false;
		}
		hook("before_email_send", ["email" => $email, "subject" => $subject, "content" => $body]);
		$result = zjmfhook(ucfirst($email_operator), "mail", $_data, "send");
		if ($result["status"] == "success") {
			if ($this->is_admin) {
				email_log($uid, $subject, $body, $email, $template["copyto"], $template["blind_copy_to"], 1, "", 1, "", $ip);
			} else {
				email_log($uid, $subject, $body, $email, $template["copyto"], $template["blind_copy_to"], 1, "", 0, "", $ip);
			}
		} else {
			if ($this->is_admin) {
				email_log($uid, $subject, $body, $email, $template["copyto"], $template["blind_copy_to"], 0, $result["msg"], 1, "", $ip);
			} else {
				email_log($uid, $subject, $body, $email, $template["copyto"], $template["blind_copy_to"], 0, $result["msg"], 0, "", $ip);
			}
		}
		return $result;
	}
	public function sendEmail($templateId, $relid, $ip = "")
	{
		if (!is_numeric($templateId)) {
			$templateId = \think\Db::name("email_templates")->where("name", $templateId)->value("id");
		}
		$template = $this->getTemplate(intval($templateId));
		$template = array_map(function ($v) {
			return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
		}, $template);
		if (!$template) {
			return false;
		}
		$type = $template["type"];
		$uid = $this->getUid($type, $relid);
		$email = $this->getEmail($uid);
		if (!$email) {
			return false;
		}
		if ($type === "invoice") {
			$invoice = \think\Db::name("invoice_items")->where([["invoice_id", "=", $relid]])->find();
			if (isset($invoice["rel_id"]) && $invoice["rel_id"] > 0) {
				$relid = $invoice["rel_id"];
			}
		}
		if ($type == "product" || $type == "invoice") {
			$host = \think\Db::name("host")->alias("a")->field("b.name")->leftJoin("products b", "a.productid=b.id")->where("a.id", $relid)->find();
		}
		$clients = \think\Db::name("clients")->where("id", $uid)->find();
		$subject = str_replace(["{SYSTEM_COMPANYNAME}", "{PRODUCT_NAME}", "{USERNAME}"], [configuration("company_name") ?? "智简魔方", $host["name"] ?? "", $clients["username"] ?: ""], $template["subject"]);
		$subject = $this->replaceArg($subject, $uid, $type, $relid);
		$body = $this->replaceArg($template["message"], $uid, $type, $relid);
		$_data["email"] = $email;
		$_data["attachments"] = $template["attachments"];
		$_data["content"] = $body;
		$_data["subject"] = $subject;
		$email_operator = configuration("email_operator");
		$_data["config"] = pluginConfig(ucfirst($email_operator), "mail");
		if (!$this->checkEmailOperator($email_operator)) {
			return false;
		}
		hook("before_email_send", ["email" => $email, "subject" => $subject, "content" => $body]);
		$result = zjmfhook(ucfirst($email_operator), "mail", $_data, "send");
		if ($result["status"] == "success") {
			if ($this->is_admin) {
				email_log($uid, $subject, $body, $email, $template["copyto"], $template["blind_copy_to"], 1, "", 1, "", $ip);
			} else {
				email_log($uid, $subject, $body, $email, $template["copyto"], $template["blind_copy_to"], 1, "", 0, "", $ip);
			}
		} else {
			if ($this->is_admin) {
				email_log($uid, $subject, $body, $email, $template["copyto"], $template["blind_copy_to"], 0, $result["msg"], 1, "", $ip);
			} else {
				email_log($uid, $subject, $body, $email, $template["copyto"], $template["blind_copy_to"], 0, $result["msg"], 0, "", $ip);
			}
		}
		return $result;
	}
	public function sendEmailCode($email, $code, $admin = false, $admin_id = 0, $action = "verification code")
	{
		if (!$code) {
			return false;
		}
		if (!$email) {
			return false;
		}
		$system_language = get_system_lang();
		$template = \think\Db::name("email_templates")->where("language", $system_language)->where("name", "验证码")->where("disabled", 0)->find();
		if (empty($template)) {
			$template = \think\Db::name("email_templates")->where("language", "")->where("name", "验证码")->where("disabled", 0)->find();
		}
		$template = array_map(function ($v) {
			return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
		}, $template);
		if (!$template) {
			return false;
		}
		if ($admin) {
			$username = \think\Db::name("user")->where("id", $admin_id)->value("user_login");
		} else {
			$username = \think\Db::name("clients")->where("email", $email)->value("username");
		}
		$company_name = configuration("company_name") ?? "智简魔方";
		$subject = str_replace(["{SYSTEM_COMPANYNAME}", "{USERNAME}"], [$company_name, $username], $template["subject"]);
		$action = config("verification_code")[$action];
		$str = ["{USERNAME}", "{CODE_ACTION}", "{CODE}", "{SYSTEM_COMPANYNAME}", "{SEND_TIME}", "{SYSTEM_EMAIL_LOGO_URL}"];
		$replace = [$username ?? $email, $action, $code, $company_name, date("Y-m-d H:i:s"), configuration("domain") . configuration("logo_url")];
		$subject = str_replace($str, $replace, $subject);
		$body = str_replace($str, $replace, $template["message"]);
		$_data["email"] = $email;
		$_data["attachments"] = $template["attachments"];
		$_data["content"] = $body;
		$_data["subject"] = $subject;
		$email_operator = configuration("email_operator");
		$_data["config"] = pluginConfig(ucfirst($email_operator), "mail");
		if (!$this->checkEmailOperator($email_operator)) {
			return false;
		}
		hook("before_email_send", ["email" => $email, "subject" => $subject, "content" => $body]);
		$result = zjmfhook(ucfirst($email_operator), "mail", $_data, "send");
		if ($result["status"] == "success") {
			if ($this->is_admin) {
				email_log(0, $subject, $body, $email, $template["copyto"], $template["blind_copy_to"], 1, "", 1);
			} else {
				email_log(0, $subject, $body, $email, $template["copyto"], $template["blind_copy_to"]);
			}
		} else {
			if (empty($result["msg"])) {
				$result["msg"] = "";
			}
			if ($this->is_admin) {
				email_log(0, $subject, $body, $email, $template["copyto"], $template["blind_copy_to"], 0, $result["msg"], 1);
			} else {
				email_log(0, $subject, $body, $email, $template["copyto"], $template["blind_copy_to"], 0, $result["msg"]);
			}
		}
		return $result;
	}
	private function checkEmailOperator($name)
	{
		$plugin = \think\Db::name("plugin")->field("name")->where("module", "mail")->where("status", 1)->where("name", $name)->select()->toArray();
		return $plugin;
	}
	public function sendEmailCode2($email, $code, $admin = false, $admin_id = 0, $action = "verification code")
	{
		if (!$code) {
			return false;
		}
		if (!$email) {
			return false;
		}
		$system_language = get_system_lang();
		$template = \think\Db::name("email_templates")->where("language", $system_language)->where("name", "验证码")->where("disabled", 0)->find();
		if (empty($template)) {
			$template = \think\Db::name("email_templates")->where("language", "")->where("name", "验证码")->where("disabled", 0)->find();
		}
		$template = array_map(function ($v) {
			return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
		}, $template);
		if (!$template) {
			return false;
		}
		$this->mailConfig();
		$this->mail->addAddress($email);
		if ($admin) {
			$username = \think\Db::name("user")->where("id", $admin_id)->value("user_login");
		} else {
			$username = \think\Db::name("clients")->where("email", $email)->value("username");
		}
		$company_name = configuration("company_name") ?? "智简魔方";
		$subject = str_replace(["{SYSTEM_COMPANYNAME}", "{USERNAME}"], [$company_name, $username], $template["subject"]);
		$this->mail->Subject = $subject;
		$action = config("verification_code")[$action];
		if (!empty($template["attachments"])) {
			$attachments = explode(",", $template["attachments"]);
			foreach ($attachments as $attachment) {
				$originalName = $this->getOriginalName($attachment);
				$this->mail->AddAttachment(self::ATTACHMENTS_ADDRESS . $attachment, $originalName);
			}
		}
		$str = ["{USERNAME}", "{CODE_ACTION}", "{CODE}", "{SYSTEM_COMPANYNAME}", "{SEND_TIME}", "{SYSTEM_EMAIL_LOGO_URL}"];
		$replace = [$username ?? $email, $action, $code, $company_name, date("Y-m-d H:i:s"), configuration("domain") . configuration("logo_url")];
		$body = str_replace($str, $replace, $template["message"]);
		$this->mail->Body = $body;
		hook("before_email_send", ["email" => $email, "subject" => $subject, "content" => $body]);
		$result = $this->mail->send();
		if ($result) {
			if ($this->is_admin) {
				email_log(0, $subject, $body, $email, $template["copyto"], $template["blind_copy_to"], 1, "", 1);
			} else {
				email_log(0, $subject, $body, $email, $template["copyto"], $template["blind_copy_to"]);
			}
		} else {
			if ($this->is_admin) {
				email_log(0, $subject, $body, $email, $template["copyto"], $template["blind_copy_to"], 0, $this->mail->ErrorInfo, 1);
			} else {
				email_log(0, $subject, $body, $email, $template["copyto"], $template["blind_copy_to"], 0, $this->mail->ErrorInfo);
			}
		}
		return $result;
	}
	public function sendEmailBind($email, $action = "bind email", $sync = false)
	{
		if (!$email) {
			return false;
		}
		$system_language = get_system_lang();
		$template = \think\Db::name("email_templates")->where("language", $system_language)->where("name", "邮箱绑定通知")->where("disabled", 0)->find();
		if (empty($template)) {
			$template = \think\Db::name("email_templates")->where("language", "")->where("name", "邮箱绑定通知")->where("disabled", 0)->find();
		}
		$template = array_map(function ($v) {
			return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
		}, $template);
		if (!$template) {
			return false;
		}
		$client = \think\Db::name("clients")->where("email", $email)->find();
		$username = $client["username"];
		$phonenumber = $client["phonenumber"];
		$company_name = configuration("company_name") ?? "智简魔方";
		$subject = $template["subject"];
		$subject = str_replace(["{EPW_TYPE}", "{SYSTEM_COMPANYNAME}", "{USERNAME}"], [config("bind_email")[$action], $company_name, $username], $subject);
		$nickname = \think\Db::name("wechat_user")->where("id", $client["wechat_id"])->value("nickname");
		if ($action == "bind email") {
			$account = $email;
		} elseif ($action == "bing phone") {
			$account = $phonenumber;
		} else {
			$account = $nickname;
		}
		$str = ["{USERNAME}", "{EPW_TYPE}", "{EPW_ACCOUNT}", "{SYSTEM_COMPANYNAME}", "{SEND_TIME}", "{SYSTEM_EMAIL_LOGO_URL}"];
		$replace = [$username ?? $email, config("bind_email")[$action], $account, $company_name, date("Y-m-d H:i:s"), configuration("domain") . configuration("logo_url")];
		$subject = str_replace($str, $replace, $subject);
		$body = str_replace($str, $replace, $template["message"]);
		$_data["email"] = $email;
		$_data["attachments"] = $template["attachments"];
		$_data["content"] = $body;
		$_data["subject"] = $subject;
		$email_operator = configuration("email_operator");
		$_data["config"] = pluginConfig(ucfirst($email_operator), "mail");
		if (!$this->checkEmailOperator($email_operator)) {
			return false;
		}
		hook("before_email_send", ["email" => $email, "subject" => $subject, "content" => $body]);
		$result = zjmfhook(ucfirst($email_operator), "mail", $_data, "send");
		if ($result["status"] == "success") {
			if ($this->is_admin) {
				email_log(0, $subject, $body, $email, $template["copyto"], $template["blind_copy_to"], 1, "", 1);
			} else {
				email_log(0, $subject, $body, $email, $template["copyto"], $template["blind_copy_to"]);
			}
		} else {
			if ($this->is_admin) {
				email_log(0, $subject, $body, $email, $template["copyto"], $template["blind_copy_to"], 0, $result["msg"], 1);
			} else {
				email_log(0, $subject, $body, $email, $template["copyto"], $template["blind_copy_to"], 0, $result["msg"]);
			}
		}
	}
	public function batchSendEmailBase($relids, $type, $subject = "", $cc = "", $bcc = "", $message = "", $attachments = "")
	{
		if (!is_array($relids)) {
			$relids = [$relids];
		}
		$allresult = [];
		foreach ($relids as $relid) {
			$result = $this->sendActivationMailBase($relid, $subject, $type, $cc, $bcc, $message, $attachments);
			if ($result) {
				$result = date("Y-m-d H:i:s") . "添加一个新的队列任务";
				\think\facade\Log::log(1, date("Y-m-d H:i:s") . "一个新的队列任务<br>");
			} else {
				$result = date("Y-m-d H:i:s") . "添加队列出错";
				\think\facade\Log::log(1, date("Y-m-d H:i:s") . "添加队列出错<br>");
			}
			array_push($allresult, $result);
		}
		return $allresult;
	}
	protected function sendActivationMailBase($relid, $subject = "", $type = "", $cc = "", $bcc = "", $message = "", $attachments = "")
	{
		$jobName = "app\\common\\job\\SendActivationMailBase";
		$data = ["relid" => $relid, "subject" => $subject, "type" => $type, "cc" => $cc, "bcc" => $bcc, "message" => $message, "attachments" => $attachments];
		$jobQueueName = "sendActivationMailBase";
		$result = \think\Queue::push($jobName, $data, $jobQueueName);
		return $result;
	}
	public function dealAttachements()
	{
	}
	/**
	 * @title 批量发送邮件(异步发送)
	 * @description 接口说明:批量发送邮件,
	 * @author wyh
	 * @param
	 *
	 *
	 */
	public function batchSendEmail($emails)
	{
		$allresult = [];
		foreach ($emails as $email) {
			$result = $this->sendActivationMail($email);
			if ($result) {
				$result = date("Y-m-d H:i:s") . "添加一个新的队列任务";
				\think\facade\Log::log(1, date("Y-m-d H:i:s") . "一个新的队列任务<br>");
			} else {
				$result = date("Y-m-d H:i:s") . "添加队列出错";
				\think\facade\Log::log(1, date("Y-m-d H:i:s") . "添加队列出错<br>");
			}
			array_push($allresult, $result);
		}
		return $allresult;
	}
	/**
	 * @param string $email 邮箱账号
	 */
	private function sendActivationMail($email = "")
	{
		$jobName = "app\\common\\job\\SendActivationMail";
		$data = ["email" => $email, "code" => mt_rand(100000, 999999), "time" => date("Y-m-d H:i:s")];
		$jobQueueName = "sendActivationMail";
		$result = \think\Queue::push($jobName, $data, $jobQueueName);
		return $result;
	}
	public function getBaseArg()
	{
		return [["name" => lang("EMAIL_TEMPLATE_COMPANY_NAME"), "arg" => "{SYSTEM_COMPANYNAME}"], ["name" => lang("EMAIL_TEMPLATE_CODE"), "arg" => "{CODE}"], ["name" => lang("EMAIL_TEMPLATE_SEND_TIME"), "arg" => "{SEND_TIME}"], ["name" => lang("EMAIL_TEMPLATE_SYSTEM_URL"), "arg" => "{SYSTEM_URL}"], ["name" => lang("EMAIL_TEMPLATE_SYSTEM_WEB_URL"), "arg" => "{SYSTEM_WEB_URL}"], ["name" => lang("EMAIL_TEMPLATE_COMPANY_LOGO_URL"), "arg" => "{SYSTEM_EMAIL_LOGO_URL}"]];
	}
	public function getReplaceArg($type)
	{
		switch ($type) {
			case "general":
				$args = [["name" => lang("EMAIL_TEMPLATE_CLIENT_NAME"), "arg" => "{USERNAME}"], ["name" => lang("EMAIL_TEMPLATE_CLIENT_COMPANY_NAME"), "arg" => "{USER_COMPANY}"], ["name" => lang("EMAIL_TEMPLATE_CLIENT_EMAIL"), "arg" => "{ACCOUNT_EMAIL}"], ["name" => lang("EMAIL_TEMPLATE_CLIENT_LOGIN_DATA_TIME"), "arg" => "{LOGIN_DATA_TIME}"], ["name" => lang("EMAIL_TEMPLATE_CLIENT_ACTION_IP"), "arg" => "{ACTION_IP}"]];
				break;
			case "product":
				$args = [["name" => lang("EMAIL_TEMPLATE_PRODUCT_NAME"), "arg" => "{PRODUCT_NAME}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_HOSTNAME"), "arg" => "{HOSTNAME}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_USER"), "arg" => "{PRODUCT_USER}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_PRODUCT_MAINIP"), "arg" => "{PRODUCT_MAINIP}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_PASSWD"), "arg" => "{PRODUCT_PASSWD}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_DCIMBMS_OS"), "arg" => "{PRODUCT_DCIMBMS_OS}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_ADDONIP"), "arg" => "{PRODUCT_ADDONIP}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_FIRST_TIME"), "arg" => "{PRODUCT_FIRST_TIME}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_END_TIME"), "arg" => "{PRODUCT_END_TIME}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_BINLLY_CYCLE"), "arg" => "{PRODUCT_BINLLY_CYCLE}"], ["name" => lang("EMAIL_TEMPLATE_ORDER_CREATE_TIME"), "arg" => "{ORDER_CREATE_TIME}"], ["name" => lang("EMAIL_TEMPLATE_ORDER_ID"), "arg" => "{ORDER_ID}"], ["name" => lang("EMAIL_TEMPLATE_ORDER_TOTAL_FEE"), "arg" => "{ORDER_TOTAL_FEE}"], ["name" => lang("EMAIL_TEMPLATE_INVOICE_PAID_TIME"), "arg" => "{INVOICE_PAID_TIME}"], ["name" => lang("EMAIL_TEMPLATE_INVOICE_ID"), "arg" => "{INVOICE_ID}"], ["name" => lang("EMAIL_TEMPLATE_INVOICE_TOTAL_FEE"), "arg" => "{INVOICE_TOTAL_FEE}"]];
				break;
			case "invoice":
				$args = [["name" => lang("EMAIL_TEMPLATE_PRODUCT_NAME"), "arg" => "{PRODUCT_NAME}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_HOSTNAME"), "arg" => "{HOSTNAME}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_USER"), "arg" => "{PRODUCT_USER}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_PRODUCT_MAINIP"), "arg" => "{PRODUCT_MAINIP}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_PASSWD"), "arg" => "{PRODUCT_PASSWD}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_DCIMBMS_OS"), "arg" => "{PRODUCT_DCIMBMS_OS}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_ADDONIP"), "arg" => "{PRODUCT_ADDONIP}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_FIRST_TIME"), "arg" => "{PRODUCT_FIRST_TIME}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_END_TIME"), "arg" => "{PRODUCT_END_TIME}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_BINLLY_CYCLE"), "arg" => "{PRODUCT_BINLLY_CYCLE}"], ["name" => lang("EMAIL_TEMPLATE_ORDER_CREATE_TIME"), "arg" => "{ORDER_CREATE_TIME}"], ["name" => lang("EMAIL_TEMPLATE_ORDER_ID"), "arg" => "{ORDER_ID}"], ["name" => lang("EMAIL_TEMPLATE_ORDER_TOTAL_FEE"), "arg" => "{ORDER_TOTAL_FEE}"], ["name" => lang("EMAIL_TEMPLATE_INVOICE_PAID_TIME"), "arg" => "{INVOICE_PAID_TIME}"], ["name" => lang("EMAIL_TEMPLATE_INVOICE_ID"), "arg" => "{INVOICE_ID}"], ["name" => lang("EMAIL_TEMPLATE_INVOICE_TOTAL_FEE"), "arg" => "{INVOICE_TOTAL_FEE}"]];
				break;
			case "support":
				$args = [["name" => lang("管理员名称"), "arg" => "{ADMIN_ACCOUNT_NAME}"], ["name" => lang("EMAIL_TEMPLATE_CLIENT_NAME"), "arg" => "{USERNAME}"], ["name" => lang("EMAIL_TEMPLATE_TICKET_REPLY_TIME"), "arg" => "{TICKET_REPLY_TIME}"], ["name" => lang("EMAIL_TEMPLATE_TICKET_DEPARTMENT"), "arg" => "{TICKET_DEPARTMENT}"], ["name" => lang("EMAIL_TEMPLATE_TICKET_ATUO_CLOSE_TIME"), "arg" => "{AUTO_TICKET_CLOSE_TIME}"], ["name" => lang("EMAIL_TEMPLATE_TICKET_TITLE"), "arg" => "{TICKETNUMBER_TICKETTITLE}"], ["name" => lang("EMAIL_TEMPLATE_TICKET_CREATETIME"), "arg" => "{TICKET_CREATETIME}"], ["name" => lang("EMAIL_TEMPLATE_TICKET_PRIORITY"), "arg" => "{TICKET_LEVEL}"], ["name" => lang("工单内容"), "arg" => "{TICKET_CONTNET}"], ["name" => lang("工单回复"), "arg" => "{TICKET_REPLY}"]];
				break;
			case "notification":
				$args = [];
				break;
			case "admin":
				$args = [["name" => lang("管理员名称"), "arg" => "{ADMIN_ACCOUNT_NAME}"], ["name" => lang("登录时间"), "arg" => "{ADMIN_LOGIN_DATA_TIME}"], ["name" => lang("登录IP"), "arg" => "{ADMIN_ACTION_IP}"]];
				break;
			case "credit_limit":
				$args = [["name" => lang("EMAIL_TEMPLATE_PRODUCT_NAME"), "arg" => "{PRODUCT_NAME}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_HOSTNAME"), "arg" => "{HOSTNAME}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_USER"), "arg" => "{PRODUCT_USER}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_PRODUCT_MAINIP"), "arg" => "{PRODUCT_MAINIP}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_PASSWD"), "arg" => "{PRODUCT_PASSWD}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_DCIMBMS_OS"), "arg" => "{PRODUCT_DCIMBMS_OS}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_ADDONIP"), "arg" => "{PRODUCT_ADDONIP}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_FIRST_TIME"), "arg" => "{PRODUCT_FIRST_TIME}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_END_TIME"), "arg" => "{PRODUCT_END_TIME}"], ["name" => lang("EMAIL_TEMPLATE_PRODUCT_BINLLY_CYCLE"), "arg" => "{PRODUCT_BINLLY_CYCLE}"], ["name" => lang("EMAIL_TEMPLATE_ORDER_CREATE_TIME"), "arg" => "{ORDER_CREATE_TIME}"], ["name" => lang("EMAIL_TEMPLATE_ORDER_ID"), "arg" => "{ORDER_ID}"], ["name" => lang("EMAIL_TEMPLATE_ORDER_TOTAL_FEE"), "arg" => "{ORDER_TOTAL_FEE}"], ["name" => lang("EMAIL_TEMPLATE_INVOICE_PAID_TIME"), "arg" => "{INVOICE_PAID_TIME}"], ["name" => lang("EMAIL_TEMPLATE_INVOICE_ID"), "arg" => "{INVOICE_ID}"], ["name" => lang("EMAIL_TEMPLATE_INVOICE_TOTAL_FEE"), "arg" => "{INVOICE_TOTAL_FEE}"]];
				break;
		}
		return $args;
	}
	protected function replaceArg($content, $uid, $type, $relid)
	{
		if ($type == "admin") {
			$client = [];
		} else {
			$client = \think\Db::name("clients")->where("id", $uid)->find();
		}
		$searchAdd1 = array_column($this->getReplaceArg("general"), "arg");
		$searchAdd2 = array_column($this->getBaseArg(), "arg");
		$searchBase = array_merge($searchAdd1, $searchAdd2);
		$replaceBase = [$client["username"] ?? "", $client["companyname"] ?? "", $client["email"] ?? "", !empty($client["lastlogin"]) ? date("Y-m-d H:i:s", $client["lastlogin"]) : "", $client["lastloginip"] ?? "", configuration("company_name") ?? "智简魔方", mt_rand(100000, 999999), date("Y-m-d"), configuration("domain"), configuration("system_url"), configuration("domain") . configuration("logo_url")];
		$content = str_replace($searchBase, $replaceBase, $content);
		$result = $this->getContent($content, $type, $relid);
		return $result;
	}
	protected function getContent($content, $type, $relid)
	{
		$searchAdd = array_column($this->getReplaceArg($type), "arg");
		if ($type == "admin") {
			$admin = \think\Db::name("user")->where("id", $relid)->find();
			$replaceAdd = [$admin["user_login"] ?? "admin", date("Y-m-d H:i:s", $admin["last_login_time"]), $admin["last_login_ip"]];
			$result = str_replace($searchAdd, $replaceAdd, $content);
		} elseif ($type == "general") {
			$result = $content;
		} elseif ($type == "product" || $type == "invoice" || $type == "credit_limit") {
			$host = \think\Db::name("host")->alias("h")->field("h.port")->field("p.name as product_name,o.id,o.create_time,o.amount,h.os,o.pay_time,i.paid_time,h.domain,h.payment,h.firstpaymentamount,h.billingcycle,h.nextduedate,h.dedicatedip,h.assignedips,h.username,h.password,h.os,cu.prefix,cu.suffix")->leftJoin("products p", "p.id = h.productid")->leftJoin("orders o", "o.id = h.orderid")->leftJoin("clients c", "h.uid = c.id")->leftJoin("currencies cu", "c.currency = cu.id")->leftJoin("invoices i", "i.id = o.invoiceid")->where("h.id", $relid)->find();
			$invoice = \think\Db::name("invoices")->where("id", $relid)->find();
			$terminate_time = $host["nextduedate"] + ((configuration("cron_host_terminate_time") ?? 0) + 1) * 24 * 3600;
			$replaceAdd = [$host["product_name"], $host["domain"], $host["username"], $host["dedicatedip"], cmf_decrypt($host["password"]), $host["os"], $host["assignedips"], date("Y-m-d", $host["create_time"]), $host["nextduedate"] > 0 ? date("Y-m-d", $host["nextduedate"]) : "永久", config("billing_cycle")[$host["billingcycle"]], date("Y-m-d", $host["create_time"]), $host["id"], $host["prefix"] . $host["amount"] . $host["suffix"], !empty($host["paid_time"]) ? date("Y-m-d", $host["paid_time"]) : date("Y-m-d"), date("Y-m-d", $terminate_time), $invoice["id"], $invoice["total"]];
			$replaceAdd[3] .= $host["port"] ? ":" . $host["port"] : "";
			$result = str_replace($searchAdd, $replaceAdd, $content);
		} elseif ($type == "support") {
			$ticket = \think\Db::name("ticket")->alias("t")->field("t.id as ticket_id,td.name as ticket_department,t.create_time,t.title,t.content,t.status,t.priority,t.last_reply_time,t.uid,t.admin_id")->leftJoin("ticket_department td", "td.id = t.dptid")->where("t.id", $relid)->find();
			$user_name = "匿名";
			if ($ticket["uid"] > 0) {
				$user_name = \think\Db::name("clients")->where("id", $ticket["uid"])->value("username");
			}
			if ($ticket["admin_id"] > 0) {
				$admin_name = \think\Db::name("user")->where("id", $ticket["admin_id"])->value("user_nickname");
			} else {
				$admin_name = \think\Db::name("user")->where("id", 1)->value("user_nickname");
			}
			$cron_reply_time = date("Y-m-d H:i:s", $ticket["last_reply_time"] + 3600 * configuration("cron_ticket_close_time"));
			$priority = ["high" => "高", "low" => "低", "medium" => "中"];
			$reply = \think\Db::name("ticket_reply")->field("content")->where("tid", $relid)->order("id", "desc")->find();
			$ticket_content = htmlspecialchars_decode($ticket["content"], ENT_QUOTES);
			preg_match_all("/<p>(.*?)<\\/p>/", $ticket_content, $out);
			if ($out[1][0]) {
				$ticket_content = $out[1][0];
				$ticket_content = preg_replace("/<\\s*img\\s+[^>]*?src\\s*=\\s*('|\\\")(.*?)\\1[^>]*?\\/?\\s*>/i", "", $ticket_content);
			}
			$reply_content = htmlspecialchars_decode($reply["content"], ENT_QUOTES);
			preg_match_all("/<p>(.*?)<\\/p>/", $reply_content, $out2);
			if ($out2[1][0]) {
				$reply_content = $out2[1][0];
				$reply_content = preg_replace("/<\\s*img\\s+[^>]*?src\\s*=\\s*('|\\\")(.*?)\\1[^>]*?\\/?\\s*>/i", "", $reply_content);
			}
			$replaceAdd = [$admin_name, $user_name, date("Y-m-d", $ticket["last_reply_time"]), $ticket["ticket_department"], $cron_reply_time, $ticket["title"], date("Y-m-d", $ticket["create_time"]), $priority[$ticket["priority"]], $ticket_content, $reply_content];
			$result = str_replace($searchAdd, $replaceAdd, $content);
		} else {
			$result = $content;
		}
		return $result;
	}
	public function replaceEmailContentParams($email_content = "", $client = [])
	{
		$client_info = \think\Db::name("clients")->where("id", $client["id"])->find();
		$host = [];
		$product = [];
		$order = [];
		$server = [];
		if ($client["host_id"]) {
			$host = \think\Db::name("host")->where("id", $client["host_id"])->find();
			$order = \think\Db::name("orders")->alias("o")->leftJoin("invoices i", "i.id = o.invoiceid")->leftJoin("invoice_items im", "i.id = im.invoice_id")->where("im.rel_id", $host["id"])->field("o.*,i.paid_time")->find();
			if ($host && $host["serverid"]) {
				$server = \think\Db::name("servers")->where("id", $host["serverid"])->find();
			}
			if (!$client["productid"]) {
				$client["productid"] = $host["productid"];
			}
		}
		if ($client["productid"]) {
			$product = \think\Db::name("products")->where("id", $client["productid"])->find();
		}
		if ($client["invoiceid"]) {
			$invoice = \think\Db::name("invoices")->where("id", $client["invoiceid"])->find();
		}
		preg_match_all("/(?<=\\{)[^\\}]+/", $email_content, $matches);
		$var = $matches[0];
		foreach ($var as $key => $value) {
			switch ($value) {
				case "SYSTEM_COMPANYNAME":
					$email_content = preg_replace("/\\{SYSTEM_COMPANYNAME\\}/", configuration("company_name"), $email_content);
					break;
				case "COMPANY_DOMAIN":
					$email_content = preg_replace("/\\{COMPANY_DOMAIN\\}/", configuration("system_url"), $email_content);
					break;
				case "SYSTEM_EMAIL_LOGO_URL":
					$email_content = preg_replace("/\\{SYSTEM_EMAIL_LOGO_URL\\}/", configuration("domain") . configuration("logo_url"), $email_content);
					break;
				case "TEMPLATE_DATE":
					$email_content = preg_replace("/\\{TEMPLATE_DATE\\}/", date("Y-m-d"), $email_content);
					break;
				case "TEMPLATE_TIME":
					$email_content = preg_replace("/\\{TEMPLATE_TIME\\}/", date("H:i:s"), $email_content);
					break;
				case "CODE":
					$email_content = preg_replace("/\\{CODE\\}/", mt_rand(100000, 999999), $email_content);
					break;
				case "SEND_TIME":
					$email_content = preg_replace("/\\{SEND_TIME\\}/", date("Y-m-d H:i:s"), $email_content);
					break;
				case "SYSTEM_URL":
					$email_content = preg_replace("/\\{SYSTEM_URL\\}/", configuration("domain"), $email_content);
					break;
				case "SYSTEM_WEB_URL":
					$email_content = preg_replace("/\\{SYSTEM_WEB_URL\\}/", configuration("system_url"), $email_content);
					break;
				case "CLIENT_ID":
					$email_content = preg_replace("/\\{CLIENT_ID\\}/", $client_info["id"], $email_content);
					break;
				case "USERNAME":
					$email_content = preg_replace("/\\{USERNAME\\}/", $client_info["username"], $email_content);
					break;
				case "USER_COMPANY":
					$email_content = preg_replace("/\\{USER_COMPANY\\}/", $client_info["companyname"], $email_content);
					break;
				case "ACCOUNT_EMAIL":
					$email_content = preg_replace("/\\{ACCOUNT_EMAIL\\}/", $client_info["email"], $email_content);
					break;
				case "CLIENT_LOGIN_DATA_TIME":
					$email_content = preg_replace("/\\{CLIENT_LOGIN_DATA_TIME\\}/", date("Y-m-d H:i:s", $client_info["lastlogin"]), $email_content);
					break;
				case "CLIENT_ACTION_IP":
					$email_content = preg_replace("/\\{CLIENT_ACTION_IP\\}/", $client_info["lastloginip"], $email_content);
					break;
				case "CLIENT_ADDRESS1":
					$email_content = preg_replace("/\\{CLIENT_ADDRESS1\\}/", $client_info["address1"], $email_content);
					break;
				case "CLIENT_CITY":
					$email_content = preg_replace("/\\{CLIENT_CITY\\}/", $client_info["city"], $email_content);
					break;
				case "CLIENT_PROVINCE":
					$email_content = preg_replace("/\\{CLIENT_PROVINCE\\}/", $client_info["province"], $email_content);
					break;
				case "CLIENT_POSTCODE":
					$email_content = preg_replace("/\\{CLIENT_POSTCODE\\}/", $client_info["postcode"], $email_content);
					break;
				case "CLIENT_COUNTRY":
					$email_content = preg_replace("/\\{CLIENT_COUNTRY\\}/", $client_info["country"], $email_content);
					break;
				case "CLIENT_PHONENUMBER":
					$email_content = preg_replace("/\\{CLIENT_PHONENUMBER\\}/", $client_info["phonenumber"], $email_content);
					break;
				case "CLIENT_SIGNUP_DATE":
					$email_content = preg_replace("/\\{CLIENT_SIGNUP_DATE\\}/", date("Y-m-d H:i:s", $client_info["create_time"]), $email_content);
					break;
				case "CLIENT_CREDIT":
					$email_content = preg_replace("/\\{CLIENT_CREDIT\\}/", $client_info["credit"], $email_content);
					break;
				case "CLIENT_CC_TYPE":
					$email_content = preg_replace("/\\{CLIENT_CC_TYPE\\}/", $client_info["cardtype"], $email_content);
					break;
				case "CLIENT_CC_NUMBER":
					$email_content = preg_replace("/\\{CLIENT_CC_NUMBER\\}/", $client_info["cardlastfour"], $email_content);
					break;
				case "CLIENT_CC_EXPIRY":
					$email_content = preg_replace("/\\{CLIENT_CC_EXPIRY\\}/", $client_info["expdate"], $email_content);
					break;
				case "CLIENT_GROUP_ID":
					$email_content = preg_replace("/\\{CLIENT_GROUP_ID\\}/", $client_info["groupid"], $email_content);
					break;
				case "CLIENT_GROUP_NAME":
					$group_name = \think\Db::name("client_groups")->where("id", $client_info["groupid"])->value("group_name");
					$email_content = preg_replace("/\\{CLIENT_GROUP_NAME\\}/", $group_name, $email_content);
					break;
				case "CLIENT_DUE_INVOICES_BALANCE":
					$amount = \think\Db::name("invoices")->where("uid", $client_info["id"])->where("status", "Overdue")->sum("subtotal");
					$email_content = preg_replace("/\\{CLIENT_DUE_INVOICES_BALANCE\\}/", $amount, $email_content);
					break;
				case "CLIENT_STATUS":
					if ($client_info["status"] == 1) {
						$client_status = "激活";
					} elseif ($client_info["status"] == 0) {
						$client_status = "未激活";
					} elseif ($client_info["status"] == 2) {
						$client_status = "关闭";
					} else {
						$client_status = "未知";
					}
					$email_content = preg_replace("/\\{CLIENT_STATUS\\}/", $client_status, $email_content);
					break;
				case "PRODUCT_USER":
					$PRODUCT_USER = $host["username"] ?? "";
					$email_content = preg_replace("/\\{PRODUCT_USER\\}/", $PRODUCT_USER, $email_content);
					break;
				case "PRODUCT_PASSWD":
					$PRODUCT_PASSWD = $host["password"] ? cmf_decrypt($host["password"]) : "";
					$email_content = preg_replace("/\\{PRODUCT_PASSWD\\}/", $PRODUCT_PASSWD, $email_content);
					break;
				case "PRODUCT_TERMINATE_TIME":
					$PRODUCT_TERMINATE_TIME = date("Y-m-d H:i:s", $host["nextduedate"]) ?? "";
					$email_content = preg_replace("/\\{PRODUCT_TERMINATE_TIME\\}/", $PRODUCT_TERMINATE_TIME, $email_content);
					break;
				case "PRODUCT_BINLLY_CYCLE":
					$config_billing_cycle = config("billing_cycle");
					$PRODUCT_BINLLY_CYCLE = $config_billing_cycle[$host["billingcycle"]] ?? "";
					$email_content = preg_replace("/\\{PRODUCT_BINLLY_CYCLE\\}/", $PRODUCT_BINLLY_CYCLE, $email_content);
					break;
				case "PRODUCT_NAME":
					$PRODUCT_NAME = $product["name"] ?? "";
					$email_content = preg_replace("/\\{PRODUCT_NAME\\}/", $PRODUCT_NAME, $email_content);
					break;
				case "HOSTNAME":
					$HOSTNAME = $host["domain"] ?? "";
					$email_content = preg_replace("/\\{HOSTNAME\\}/", $HOSTNAME, $email_content);
					break;
				case "PRODUCT_MAINIP":
					$PRODUCT_MAINIP = $host["dedicatedip"] ?? "";
					if ($PRODUCT_MAINIP) {
						$PRODUCT_MAINIP .= $host["port"] ? ":" . $host["port"] : "";
					}
					$email_content = preg_replace("/\\{PRODUCT_MAINIP\\}/", $PRODUCT_MAINIP, $email_content);
					break;
				case "PRODUCT_DCIMBMS_OS":
					$email_content = preg_replace("/\\{PRODUCT_DCIMBMS_OS\\}/", $host["os"] ?? "", $email_content);
					break;
				case "PRODUCT_ADDONIP":
					$email_content = preg_replace("/\\{PRODUCT_ADDONIP\\}/", $host["assignedips"] ?? "", $email_content);
					break;
				case "PRODUCT_FIRST_TIME":
					$email_content = preg_replace("/\\{PRODUCT_FIRST_TIME\\}/", $host["create_time"] ? date("Y-m-d H:i:s", $host["create_time"]) : "", $email_content);
					break;
				case "PRODUCT_END_TIME":
					$nextduedate = $host["nextduedate"] ? date("Y-m-d H:i:s", $host["nextduedate"]) : "";
					$email_content = preg_replace("/\\{PRODUCT_END_TIME\\}/", $nextduedate, $email_content);
					break;
				case "PRODUCT_BINLLY_CYCLE":
					$email_content = preg_replace("/\\{PRODUCT_BINLLY_CYCLE\\}/", $host["billingcycle"] ?? "", $email_content);
					break;
				case "ORDER_CREATE_TIME":
					$ORDER_CREATE_TIME = $order["create_time"] ? date("Y-m-d H:i:s", $order["create_time"]) : "";
					$email_content = preg_replace("/\\{ORDER_CREATE_TIME\\}/", $ORDER_CREATE_TIME, $email_content);
					break;
				case "ORDER_ID":
					$email_content = preg_replace("/\\{ORDER_ID\\}/", $order["id"] ?? "", $email_content);
					break;
				case "ORDER_TOTAL_FEE":
					$email_content = preg_replace("/\\{ORDER_TOTAL_FEE\\}/", ($order["amount"] ?? 0) . " 元", $email_content);
					break;
				case "INVOICE_ID":
					$email_content = preg_replace("/\\{INVOICE_ID\\}/", $invoice["id"] ?? "", $email_content);
					break;
				case "INVOICE_TOTAL_FEE":
					$email_content = preg_replace("/\\{INVOICE_TOTAL_FEE\\}/", ($invoice["total"] ?? 0) . " 元", $email_content);
					break;
				case "INVOICE_PAID_TIME":
					$INVOICE_PAID_TIME = $order["paid_time"] ? date("Y-m-d H:i:s", $order["paid_time"]) : "";
					$email_content = preg_replace("/\\{INVOICE_PAID_TIME\\}/", $INVOICE_PAID_TIME, $email_content);
					break;
				case "REG_DATE":
					$REG_DATE = $host["regdate"] ? date("Y-m-d H:i:s", $host["regdate"]) : "";
					$email_content = preg_replace("/\\{REG_DATE\\}/", $REG_DATE, $email_content);
					break;
				case "SERVICE_STATUS":
					$email_content = preg_replace("/\\{SERVICE_STATUS\\}/", $server["link_status"] ? "成功" : "失败", $email_content);
					break;
				case "PRODUCT_DESCRIPTION":
					$email_content = preg_replace("/\\{PRODUCT_DESCRIPTION\\}/", $product["description"], $email_content);
					break;
				case "SERVER_NAME":
					$email_content = preg_replace("/\\{SERVER_NAME\\}/", $server["name"], $email_content);
					break;
				case "SERVER_IP":
					$email_content = preg_replace("/\\{SERVER_IP\\}/", $server["ip_address"], $email_content);
					break;
				case "SERVICE_SUSPENSION_REASON":
					$email_content = preg_replace("/\\{SERVICE_SUSPENSION_REASON\\}/", $host["suspendreason"], $email_content);
					break;
				case "SERVER_HOSTNAME":
					$email_content = preg_replace("/\\{SERVER_HOSTNAME\\}/", $server["name"], $email_content);
					break;
				default:
					break;
			}
		}
		return $email_content;
	}
	public function replaceEmailTpl($table_name = "email_tpl_replace")
	{
		return \think\Db::transaction(function () use($table_name) {
			$replace_model = \think\Db::name($table_name)->where("custom", 0)->select()->toArray();
			$model = \think\Db::name("email_templates")->where("custom", 0)->select()->toArray();
			if (empty($replace_model) || empty($model)) {
				return true;
			}
			$replace_model = array_column($replace_model, null, "id");
			$model = array_column($replace_model, null, "id");
			foreach ($replace_model as $key => $val) {
				if (!isset($model[$key]) || $val["message"] == $model[$key]["message"]) {
					continue;
				}
				\think\Db::name("email_templates")->where("id", $key)->update(["message" => $val["message"]]);
			}
			\think\Db::query("DROP TABLE `" . $table_name . "`");
			return true;
		});
	}
}