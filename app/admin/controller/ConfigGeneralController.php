<?php

namespace app\admin\controller;

/**
 * @title 常规设置
 * @description 接口说明:包括后台邮件设置,手机短信设置,支付宝实名认证配置等
 */
class ConfigGeneralController extends AdminBaseController
{
	use \app\admin\common\server\ConfigGeneral;
	private $charset = ["utf-8", "8bit", "7bit", "binary", "base64", "quoted-printable"];
	private $validate;
	public function initialize()
	{
		parent::initialize();
		$this->validate = new \app\admin\validate\ConfigGeneralValidate();
	}
	public function getNewLoginPage()
    {
        $templates = $this->getOptionClientarea_default_themes();
        $allow = explode(",", configuration("allow_new_login_template") ?? "");
        return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => ["templates" => $templates, "allow_new_login_template" => $allow]]);
    }
    public function postNewLoginPage()
    {
        $param = $this->request->param();
        $allow = implode(",", $param["allow_new_login_template"] ?? ["default"]);
        updateConfiguration("allow_new_login_template", $allow);
        return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
    }
	/**
	 * @title 邮件设置页面
	 * @description 接口说明:邮件设置页面
	 * @author wyh
	 * @url /admin/config_general/email_indexemail_index_post
	 * @method GET
	 * @return .shd_allow_email_send:邮件设置,1开启,0关闭
	 * @return .type:邮件类型
	 * @return .charset:设置发送的邮件的编码
	 * @return .port:设置ssl连接smtp服务器的远程服务器端口号
	 * @return .host:链接qq域名邮箱的服务器地址
	 * @return .username:smtp登录的账号 QQ邮箱即可
	 * @return .password:smtp登录的密码 使用生成的授权码
	 * @return .smtpsecure:设置使用ssl加密方式登录鉴权
	 * @return .fromname: 系统邮件名
	 * @return .systememail:系统邮箱名
	 * @return .subject:邮件的主题
	 * @return .body:邮件内容
	 */
	public function emailIndex()
	{
		$res = [];
		$res["status"] = 200;
		$res["charsets"] = $this->charset;
		$res["msg"] = lang("SUCCESS MESSAGE");
		$res["shd_allow_email_send"] = configuration("shd_allow_email_send");
		$res["type"] = configuration("email_type");
		$res["charset"] = configuration("email_charset");
		$res["port"] = configuration("email_port");
		$res["host"] = configuration("email_host");
		$res["username"] = configuration("email_username");
		$res["password"] = aesPasswordDecode(configuration("email_password"));
		$res["smtpsecure"] = configuration("email_smtpsecure");
		$res["fromname"] = configuration("email_fromname");
		$res["systememail"] = configuration("email_systememail");
		$res["subject"] = configuration("email_subject");
		$res["body"] = configuration("email_body");
		return jsonrule($res);
	}
	/**
	 * @title 邮件设置页面提交
	 * @description 接口说明:邮件设置页面提交
	 * @author wyh
	 * @url /admin/config_general/email_index_post
	 * @method POST
	 * @param .name:shd_allow_email_send type:int require:1 default:1 other: desc:邮件设置
	 * @param .name:type type:string require:1 default:1 other: desc:邮件类型
	 * @param .name:charset type:string require:1 default:1 other: desc:设置发送的邮件的编码
	 * @param .name:port type:string require:1 default:1 other: desc:设置ssl连接smtp服务器的远程服务器端口号
	 * @param .name:host type:string require:1 default:1 other: desc:链接qq域名邮箱的服务器地址
	 * @param .name:username type:string require:1 default:1 other: desc:smtp登录的账号 QQ邮箱即可
	 * @param .name:password type:string require:1 default:1 other: desc:smtp登录的密码 使用生成的授权码
	 * @param .name:smtpsecure type:string require:1 default:1 other: desc:设置使用ssl加密方式登录鉴权
	 * @param .name:fromname type:string require:1 default:1 other: desc:系统邮件名
	 * @param .name:systememail type:string require:1 default:1 other: desc:统邮箱名
	 * @param .name:subject type:string require:1 default:1 other: desc:邮件的主题
	 * @param .name:body type:string require:1 default:1 other: desc:邮件内容
	 */
	public function emailIndexPost()
	{
		if ($this->request->isPost()) {
			$param = $this->request->param();
			if (!$this->validate->scene("email")->check($param)) {
				return jsonrule(["status" => 400, "msg" => $this->validate->getError()]);
			}
			updateConfiguration("shd_allow_email_send", intval($param["shd_allow_email_send"]));
			$dec = "";
			$trim = array_map("trim", $param);
			$param = array_map("htmlspecialchars", $trim);
			$company_name = configuration("email_type");
			if ($company_name != $param["type"]) {
				$dec .= "邮件类型" . $company_name . "改为" . $param["type"];
			}
			updateConfiguration("email_type", $param["type"]);
			$company_name = configuration("email_charset");
			if ($company_name != $param["charset"]) {
				$dec .= "邮件编码" . $company_name . "改为" . $param["charset"];
			}
			updateConfiguration("email_charset", $param["charset"]);
			$company_name = configuration("email_port");
			if ($company_name != $param["port"]) {
				$dec .= "SMTP 端口" . $company_name . "改为" . $param["port"];
			}
			updateConfiguration("email_port", $param["port"]);
			$company_name = configuration("email_host");
			if ($company_name != $param["host"]) {
				$dec .= "SMTP 主机名" . $company_name . "改为" . $param["host"];
			}
			updateConfiguration("email_host", $param["host"]);
			$company_name = configuration("email_username");
			if ($company_name != $param["username"]) {
				$dec .= "SMTP 用户名" . $company_name . "改为" . $param["username"];
			}
			updateConfiguration("email_username", $param["username"]);
			$company_name = configuration("email_password");
			if ($company_name != $param["password"]) {
				$dec .= "SMTP 密码" . aesPasswordDecode($company_name) . "改为" . $param["password"];
			}
			updateConfiguration("email_password", aesPasswordEncode($param["password"]));
			$company_name = configuration("email_smtpsecure");
			if ($company_name != $param["smtpsecure"]) {
				$dec .= "SMTP SSL类型" . $company_name . "改为" . $param["smtpsecure"];
			}
			updateConfiguration("email_smtpsecure", isset($param["smtpsecure"]) ? strtolower($param["smtpsecure"]) : "ssl");
			$company_name = configuration("email_fromname");
			if ($company_name != $param["fromname"]) {
				$dec .= "系统邮件名" . $company_name . "改为" . $param["fromname"];
			}
			updateConfiguration("email_fromname", $param["fromname"]);
			$company_name = configuration("email_systememail");
			if ($company_name != $param["systememail"]) {
				$dec .= "系统邮箱名" . $company_name . "改为" . $param["systememail"];
			}
			updateConfiguration("email_systememail", $param["systememail"]);
			$company_name = configuration("email_subject");
			if ($company_name != $param["subject"]) {
				$dec .= "邮件主题" . $company_name . "改为" . $param["subject"];
			}
			updateConfiguration("email_subject", isset($param["subject"]) ? $param["subject"] : "");
			$company_name = configuration("email_body");
			if ($company_name != $param["body"]) {
				$dec .= "邮件内容" . $company_name . "改为" . $param["body"];
			}
			updateConfiguration("email_body", isset($param["body"]) ? $param["body"] : "");
			cache("email_type", $param["type"]);
			cache("email_charset", $param["charset"]);
			cache("email_port", $param["port"]);
			cache("email_host", $param["host"]);
			cache("email_username", $param["username"]);
			cache("email_password", aesPasswordEncode($param["password"]));
			cache("email_smtpsecure", isset($param["smtpsecure"]) ? $param["smtpsecure"] : "ssl");
			cache("email_fromname", $param["fromname"]);
			cache("email_systememail", $param["systememail"]);
			cache("email_subject", isset($param["subject"]) ? $param["subject"] : "");
			cache("email_body", isset($param["body"]) ? $param["body"] : "");
			active_log_final(sprintf($this->lang["ConfigGen_admin_emailIndexPost"], $dec));
			unset($dec);
			unset($company_name);
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 测试邮件发送
	 * @description 测试邮件发送
	 * @author wyh
	 * @time 2020-07-20
	 * @url /admin/config_general/send_email
	 * @method POST
	 * @param .name:email type:string require:1 default:1 other: desc:邮件
	 */
	public function sendEmailTest()
	{
		$params = $this->request->param();
		$email = $params["email"];
		if (!configuration("shd_allow_email_send")) {
			return jsonrule(["status" => 400, "msg" => "未开启邮件发送功能"]);
		}
		$email_logic = new \app\common\logic\Email();
		if (empty($email)) {
			return jsonrule(["status" => 400, "msg" => "目标邮箱不能为空"]);
		}
		$res = $email_logic->sendEmailCode($email, "123456");
		if ($res) {
			return jsonrule(["status" => 200, "msg" => lang("发送成功,请注意查收")]);
		} else {
			$out = iconv("GBK", "UTF-8", $email_logic->mail->ErrorInfo);
			$out = str_replace(["请登录", "修改密码"], ["smtp.", ""], $out);
			return jsonrule(["status" => 400, "msg" => "发送失败,原因:" . $out]);
		}
	}
	/**
	 * @title 支持设置页面
	 * @description 支持设置页面
	 * @author huanghao
	 * @url /admin/config_general/support_index
	 * @method GET
	 * @return .nologin_send_ticket:未登录也可发工单
	 * @return .ticket_reply_order:工单回复列表顺序asc升序,desc降序
	 * @return .evaluate_ticket:允许为工单评价
	 * @return .product_download:包括产品下载
	 */
	public function supportIndex()
	{
		$key = ["nologin_send_ticket", "ticket_reply_order", "evaluate_ticket", "product_download"];
		$config = getConfig($key);
		$result["status"] = 200;
		$result["msg"] = lang("SUCCESS MESSAGE");
		$result["data"] = ["nologin_send_ticket" => $config["nologin_send_ticket"] ?: 0, "ticket_reply_order" => $config["ticket_reply_order"] ?: "asc", "evaluate_ticket" => $config["evaluate_ticket"] ?: 0, "product_download" => $config["product_download"] ?: 0];
		return jsonrule($result);
	}
	/**
	 * @title 语言列表
	 * @description 接口说明:语言列表
	 * @author wyh
	 * @url /admin/config_general/lang_list
	 * @method POST
	 * @return .lang:语言列表
	 */
	public function langList()
	{
		$langs = config("language.list");
		$langsfilter = [];
		foreach ($langs as $key => $lang) {
			$langarray = [];
			$langarray["name"] = $key;
			$langarray["name_zh"] = $lang;
			array_push($langsfilter, $langarray);
		}
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "lang" => $langsfilter]);
	}
	/**
	 * @title 设置后台系统语言(cookie设置)
	 * @description 接口说明:设置后台系统语言
	 * @author wyh
	 * @url /admin/config_general/set_admin_lang
	 * @method POST
	 * @param .name:lang type:string require:1 default:1 other: desc:语言：zh-cn,en-us
	 */
	public function setAdminLang()
	{
		if ($this->request->isPost()) {
			$data = $this->request->param();
			$lang = isset($data["lang"]) ? strtolower($data["lang"]) : "zh-cn";
			cookie("language", $lang);
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	protected function percentEncode($str)
	{
		$res = urlencode($str);
		$res = preg_replace("/\\+/", "%20", $res);
		$res = preg_replace("/\\*/", "%2A", $res);
		$res = preg_replace("/%7E/", "~", $res);
		return $res;
	}
	private function getUuid()
	{
		$data = openssl_random_pseudo_bytes(16);
		$data[6] = chr(ord($data[6]) & 15 | 64);
		$data[8] = chr(ord($data[8]) & 63 | 128);
		$uuid = vsprintf("%s%s-%s-%s-%s-%s%s%s", str_split(bin2hex($data), 4));
		return $uuid;
	}
	/**
	 * @title 头部底部
	 * @description 接口说明:头部底部
	 * @author 萧十一郎
	 * @url /admin/config_general/header
	 * @method GET
	 * @return header:头部
	 * @return footer:底部
	 */
	public function getHeader()
	{
		$config_files = ["header", "footer"];
		$config_data = \think\Db::name("configuration")->whereIn("setting", $config_files)->select()->toArray();
		$returndata = [];
		$config_value = [];
		foreach ($config_files as $key => $val) {
			if ($val == "per_page_limit") {
				$config_value[$val] = "50";
			} else {
				$config_value[$val] = "";
			}
		}
		foreach ($config_data as $key => $val) {
			if ($val["setting"] == "header" || $val["setting"] == "footer") {
				$config_value[$val["setting"]] = htmlspecialchars_decode($val["value"]);
			} else {
				$config_value[$val["setting"]] = $val["value"];
			}
		}
		$returndata["config_value"] = $config_value;
		$res = [];
		return jsonrule(["status" => 200, "data" => $returndata]);
	}
	/**
	 * @title 常规设置页面
	 * @description 接口说明:常规设置页面
	 * @author 萧十一郎
	 * @url /admin/config_general/general
	 * @method GET
	 * @return company_name:公司名
	 * @return company_email:默认邮箱地址
	 * @return domain:网站域名
	 * @return system_url:系统链接
	 * @return logo_url:logo地址
	 * @return invoice_payto:付款条文
	 * @return activity_limit:系统活动日志限制
	 * @return num_records:默认每页显示条数
	 * @return main_tenance_mode:是否为维护模式
	 * @return main_tenance_mode_message:维护模式信息
	 * @return main_tenance_mode_url:维护模式重定向的链接
	 * @return home_ip_check:前台登录是否禁用ip
	 * @return admin_ip_check:后天登录是否禁用ip
	 * @return main_phone:手机
	 * @return main_address:地址
	 * @return record_no:备案号
	 * @return company_profile:公司简介
	 * @return header:头部
	 * @return footer:底部
	 * @return map:坐标
	 * @return sendmsgtimes  每天短信发送次数
	 * @return sendmsgphone 每天短信发送手机个数
	 * @return deletelogtime 删除日志天数
	 * @return cancellation_time 登录注销时间
	 * @return is_themes 是否开启主题
	 * @return themes_templates 主题模板
	 * @return login_header_footer:是否开启登录头部底部,1开启 0否
	 * @return login_header:登录头部
	 * @return login_footer:登录底部
	 * @return cart_product_description:购物车页面 应用说明
	 * @return custom_login_background_img:前台登录背景图地址
	 * @return custom_login_background_char:前台登录背景图文字
	 * @return custom_login_background_description:登录页描述
	 * @return clientarea_default_themes:客户中心模板目录默认
	 * @return clientarea_themes:客户中心模板目录
	 * @return credit_limit:是否开启前台信用额,1开启 0否
	 */
	public function getGeneral()
	{
		$config_files = ["company_name", "company_qq", "system_url", "company_email", "domain", "logo_url", "logo_url_home", "invoice_payto", "activity_limit", "per_page_limit", "custom_login_background_img", "custom_login_background_char", "custom_login_background_description", "num_records", "main_tenance_mode", "main_tenance_mode_message", "main_tenance_mode_url", "home_ip_check", "admin_ip_check", "server_clause_url", "privacy_clause_url", "main_phone", "main_address", "record_no", "map", "company_profile", "header", "footer", "www_logo", "seo_keywords", "seo_desc", "sendmsgtimes", "sendmsgphone", "deletelogtime", "cancellation_time", "themes_templates", "login_header_footer", "login_header", "login_footer", "cart_product_description", "web_widgets", "clientarea_default_themes", "credit_limit"];
		$config_data = \think\Db::name("configuration")->whereIn("setting", $config_files)->select()->toArray();
		$returndata = [];
		$config_value = [];
		foreach ($config_files as $key => $val) {
			if ($val == "per_page_limit") {
				$config_value[$val] = "50";
			} else {
				$config_value[$val] = "";
			}
		}
		foreach ($config_data as $key => $val) {
			$config_value[$val["setting"]] = $val["value"];
			if (in_array($val["setting"], ["header", "footer", "login_header", "login_footer", "web_widgets", "custom_login_background_description", "company_email", "company_profile", "main_address", "main_phone", "company_name", "company_qq"])) {
				$config_value[$val["setting"]] = htmlspecialchars_decode(htmlspecialchars_decode($val["value"]));
			} else {
				$config_value[$val["setting"]] = $val["value"];
			}
		}
		$returndata["config_value"] = $config_value;
		$returndata["themes_templates"] = get_files(CMF_ROOT . "public/themes/web");
		foreach ($returndata["themes_templates"] as $key => $value) {
			if ($value = true) {
			}
		}
		$returndata["clientarea_themes"] = get_files(CMF_ROOT . "public/themes/clientarea");
		$res = [];
		return jsonrule(["status" => 200, "data" => $returndata]);
	}
	/**
	 * @title 系统设置统一修改接口（新）
	 * @description 接口说明:系统设置统一修改接口（新）
	 * @author xue
	 * @url /admin/config_general/newGeneral
	 * @method post
	 */
	public function postNewGeneral()
	{
		try {
			$param = $this->request->param();
			return \think\Db::transaction(function () use($param) {
				$check_arr = [];
				$dec = "";
				foreach ($param as $key => $val) {
					if (in_array($key, $this->validate->checkArr)) {
						$check_arr[$key] = $val;
					}
				}
				if (!empty($check_arr) && !$this->validate->scene($check_arr)->check($param)) {
					return jsonrule(["status" => 400, "msg" => $this->validate->getError()]);
				}
				$param = array_map(function ($v) {
					return is_array($v) ? array_filter($v) : htmlspecialchars(trim($v));
				}, $param);
				$former_arr = configuration(array_keys($param));
				foreach ($param as $key => $val) {
					$val = $this->changeParam($key, $val);
					if (!isset($this->validate->param_log_arr[$key])) {
						updateConfiguration($key, $val);
						continue;
					}
					if ($val !== $former_arr[$key]) {
						if (is_array($this->validate->param_log_arr[$key])) {
							$dec .= $this->validate->param_log_arr[$key][$val];
							updateConfiguration($key, $val);
							continue;
						}
						$dec .= $this->validate->param_log_arr[$key] . $former_arr[$key] . "”改为“" . $val . "”， ";
						updateConfiguration($key, $val);
					}
				}
				$this->configUpdateAfter($param);
				$dec && active_log_final(sprintf($this->lang["ConfigGen_admin_postGeneral"], $dec));
				return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
			});
		} catch (\Throwable $e) {
			return jsonrule(["status" => 400, "msg" => $e->getMessage()]);
		}
	}
	/**
	 * @title 系统设置统一获取接口（新）
	 * @description 接口说明:统设置统一获取接口（新）
	 * @author xue
	 * @url /admin/config_general/getConfig
	 * @method post
	 */
	public function postGetConfig()
	{
		try {
			$param = $this->request->param();
			$data = configuration($param["param"]);
			if (empty($data)) {
				foreach ($param["param"] as $key => $val) {
					$data[$val] = null;
				}
			}
			foreach ($data as $key => $val) {
				$data[$key] = $this->searchGetParam($val, $key);
			}
			return jsonrule(["status" => 200, "msg" => "success", "data" => $data]);
		} catch (\Throwable $e) {
			return jsonrule(["status" => 400, "msg" => $e->getMessage()]);
		}
	}
	/**
	 * @title 系统设置选项统一获取接口（新）
	 * @description 接口说明:系统设置选项统一获取接口（新）
	 * @author xue
	 * @url /admin/config_general/getConfigOption
	 * @method post
	 */
	public function postGetConfigOption()
	{
		try {
			$data = [];
			$param = $this->request->param();
			foreach ($param["param"] as $key => $val) {
				$action = "getOption" . ucfirst($val);
				$data[$val] = method_exists($this, $action) ? $this->{$action}() : [];
			}
			return jsonrule(["status" => 200, "data" => $data]);
		} catch (\Throwable $e) {
			return jsonrule(["status" => 400, "msg" => $e->getMessage()]);
		}
	}
	private function searchGetParam($v, $ks)
	{
		$is_true = false;
		foreach ($this->replceArr as $key => $val) {
			$is_true = in_array($ks, $val);
			if ($is_true) {
				$k = $key;
				break;
			}
		}
		if (!$is_true) {
			return $v;
		}
		$action = "setup" . ucfirst($k);
		return method_exists($this, $action) ? $this->{$action}($v) : [];
	}
	private function changeParam($ks, $v)
	{
		$is_true = false;
		foreach ($this->changeArr as $key => $val) {
			$is_true = in_array($ks, $val);
			if ($is_true) {
				$k = $key;
				break;
			}
		}
		if (!$is_true) {
			return $v;
		}
		$action = "getup" . ucfirst($k);
		return method_exists($this, $action) ? $this->{$action}($ks, $v) : [];
	}
	private function configUpdateAfter($param)
	{
		foreach ($param as $key => $val) {
			$action = "updateAfter" . ucfirst($key);
			if (method_exists($this, $action)) {
				$this->{$action}();
			}
		}
		return true;
	}
	/**
	 * @title 常规设置页面提交(之前版本)
	 * @description 接口说明:常规设置页面提交
	 * @author 萧十一郎
	 * @url /admin/config_general/general
	 * @method POST
	 * @param .name:company_name type:string require:0 default: other: desc:公司名
	 * param .name:company_qq type:string require:0 default: other: desc:qq
	 * @param .name:company_email type:email require:0 default: other: desc:默认邮箱地址
	 * @param .name:domain type:string require:1 default: other: desc:网站域名
	 * @param .name:logo_url type:string require:0 default: other: desc:logo地址
	 * @param .name:invoice_payto type:string require:0 default: other: desc:付款条文
	 * @param .name:system_url type:number require:1 default: other: desc:系统链接
	 * @param .name:activity_limit type:number require:1 default: other: desc:系统活动日志限制
	 * @param .name:num_records type:string require:1 default: other: desc:默认每页显示条数
	 * @param .name:main_tenance_mode type:string require:1 default:0 other: desc:是否为维护模式
	 * @param .name:main_tenance_mode_message type:string require: default:1 other: desc:维护模式信息
	 * @param .name:main_tenance_mode_url type:string require: default: other: desc:维护模式重定向的链接
	 * @param .name:home_ip_check type:int require: default:0 other: desc:前台登录是否禁用ip
	 * @param .name:admin_ip_check type:int require: default:0 other: desc:后天登录是否禁用ip
	 * @param .name:logo_url_home type:int require: default:0 other: desc:前台logo地址
	 * @param .name:admin_application type:string require: default:0 other: desc:应用目录文件
	 * @param .name:server_clause_url type:string require: default:0 other: desc:服务条款地址
	 * @param .name:privacy_clause_url type:string require: default:0 other: desc:隐私条款地址
	 * @param .name:main_phone type:string require: default:0 other: desc:手机
	 * @param .name:main_address type:string require: default:0 other: desc:地址
	 * @param .name:record_no type:string require: default:0 other: desc:备案号
	 * @param .name:map type:string require: default:0 other: desc:坐标:29.543593,106.313324
	 * @param .name:company_profile type:string require: default:0 other: desc:公司简介
	 * @param .name:per_page_limit type:string require: default:0 other: desc:每页条数
	 * @param .name:header type:string require:0 default: other: desc:头部
	 * @param .name:footer type:string require:0 default: other: desc:底部
	 * @param .name:sendmsgtimes type:string require:0 default: other: desc:每天短信发送次数
	 * @param .name:sendmsgphone type:string require:0 default: other: desc:每天短信发送手机个数
	 * @param .name:deletelogtime type:string require:0 default: other: desc:删除日志天数
	 * @param .name:cancellation_time type:string require:0 default: other: desc:注销时间
	 * @param .name:is_themes type:string require:0 default: other: desc:是否开启主题
	 * @param .name:themes_templates type:string require:0 default: other: desc:主题模板
	 * @param .name:login_header_footer type:int require:0 default: other: desc:是否开启前台登陆界面头部、底部 1开启，0关闭
	 * @param .name:login_header type:string require:0 default: other: desc:登录头部
	 * @param .name:login_footer type:string require:0 default: other: desc:登录底部
	 * @param .name:cart_product_description type:string require:0 default: other: desc:购物车页面 应用说明
	 * @param .name:custom_login_background_img type:string require:0 default: other: desc:前台登录背景图地址
	 * @param .name:custom_login_background_char type:string require:0 default: other: desc:前台登录背景图文字
	 * @param .name:custom_login_background_description type:string require:0 default: other: desc:登录页描述
	 * @param .name:clientarea_default_themes type:string require:0 default: other: desc:客户中心模板
	 * @param .name:credit_limit type:int require:0 default: other: desc:是否开启前台信用额 1开启，0关闭
	 */
	public function postGeneral()
	{
		if ($this->request->isPost()) {
			$param = $this->request->param();
			if (!$this->validate->scene("general")->check($param)) {
				return jsonrule(["status" => 400, "msg" => $this->validate->getError()]);
			}
			$dec = "";
			$headers = $param["header"];
			$footers = $param["footer"];
			$trim = array_map("trim", $param);
			$param = array_map("htmlspecialchars", $trim);
			updateConfiguration("custom_login_background_img", $param["custom_login_background_img"]);
			updateConfiguration("custom_login_background_char", $param["custom_login_background_char"]);
			updateConfiguration("custom_login_background_description", $param["custom_login_background_description"]);
			$sendmsgtimes = configuration("sendmsgtimes");
			if ($param["sendmsgtimes"] != $sendmsgtimes) {
				$dec .= "每天短信发送次数由“" . $sendmsgtimes . "”改为“" . $param["sendmsgtimes"] . "”，";
			}
			updateConfiguration("sendmsgtimes", $param["sendmsgtimes"]);
			$sendmsgphone = configuration("sendmsgphone");
			if ($param["sendmsgphone"] != $sendmsgphone) {
				$dec .= "每天短信发送手机个数由“" . $sendmsgphone . "”改为“" . $param["sendmsgphone"] . "”，";
			}
			updateConfiguration("sendmsgphone", $param["sendmsgphone"]);
			$deletelogtime = configuration("deletelogtime");
			if ($param["deletelogtime"] != $deletelogtime) {
				$dec .= "删除日志天数由“" . $deletelogtime . "”改为“" . $param["deletelogtime"] . "”，";
			}
			updateConfiguration("deletelogtime", $param["deletelogtime"]);
			$company_name = configuration("company_name");
			if ($param["company_name"] != $company_name) {
				$dec .= "品牌名由“" . $company_name . "”改为“" . $param["company_name"] . "”，";
			}
			updateConfiguration("company_name", $param["company_name"]);
			$company_qq = configuration("company_qq");
			if ($param["company_qq"] != $company_qq) {
				$dec .= "qq由“" . $company_qq . "”改为“" . $param["company_qq"] . "”，";
			}
			updateConfiguration("company_qq", $param["company_qq"]);
			$company_name = configuration("company_email");
			if ($param["company_email"] != $company_name) {
				$dec .= "网站邮箱由“" . $company_name . "”改为“" . $param["company_email"] . "”，";
			}
			updateConfiguration("company_email", $param["company_email"]);
			$company_name = configuration("domain");
			if ($param["domain"] != $company_name) {
				$dec .= "网站域名由“" . $company_name . "”改为“" . $param["domain"] . "”，";
			}
			updateConfiguration("domain", $param["domain"]);
			$company_name = configuration("logo_url");
			if ($param["logo_url"] != $company_name) {
				$dec .= "logo地址由“" . $company_name . "”改为“" . $param["logo_url"] . "”，";
			}
			updateConfiguration("logo_url", $param["logo_url"]);
			$company_name = configuration("invoice_payto");
			if ($param["invoice_payto"] != $company_name) {
				$dec .= "付款条文由“" . $company_name . "”改为“" . $param["invoice_payto"] . "”，";
			}
			updateConfiguration("invoice_payto", $param["invoice_payto"]);
			$company_name = configuration("system_url");
			if ($param["system_url"] != $company_name) {
				$dec .= "系统连接由“" . $company_name . "”改为“" . $param["system_url"] . "”，";
			}
			updateConfiguration("system_url", $param["system_url"]);
			updateConfiguration("activity_limit", $param["activity_limit"]);
			$company_name = configuration("num_records");
			if ($param["num_records"] != $company_name) {
				$dec .= "默认每页显示条数由“" . $company_name . "”改为“" . $param["num_records"] . "”，";
			}
			updateConfiguration("num_records", $param["num_records"]);
			$home_ip_check = configuration("home_ip_check");
			if ($param["home_ip_check"] != $home_ip_check) {
				if ($param["home_ip_check"] == 1) {
					$dec .= "前台登录是否检测ip由“关闭”改为“开启”，";
				} else {
					$dec .= "前台登录是否检测ip由“开启”改为“关闭”，";
				}
			}
			updateConfiguration("home_ip_check", \intval($param["home_ip_check"]));
			$admin_ip_check = configuration("admin_ip_check");
			if ($param["admin_ip_check"] != $admin_ip_check) {
				if ($param["admin_ip_check"] == 1) {
					$dec .= "后台登录是否检测ip由“关闭”改为“开启”，";
				} else {
					$dec .= "后台登录是否检测ip由“开启”改为“关闭”，";
				}
			}
			updateConfiguration("admin_ip_check", \intval($param["admin_ip_check"]));
			$company_name = configuration("main_tenance_mode");
			if ($param["main_tenance_mode"] != $company_name) {
				if ($param["main_tenance_mode"] == 1) {
					$dec .= "维护模式由“关闭”改为“开启”，";
				} else {
					$dec .= "维护模式由“开启”改为“关闭”，";
				}
			}
			updateConfiguration("main_tenance_mode", $param["main_tenance_mode"] ?: 0);
			$company_name = configuration("main_tenance_mode_message");
			if ($param["main_tenance_mode_message"] != $company_name) {
				$dec .= "维护模式信息由“" . $company_name . "”改为“" . $param["main_tenance_mode_message"] . "”，";
			}
			updateConfiguration("main_tenance_mode_message", $param["main_tenance_mode_message"]);
			$company_name = configuration("main_tenance_mode_url");
			if ($param["main_tenance_mode_url"] != $company_name) {
				$dec .= "维护模式重定向到的链接由“" . $company_name . "”改为“" . $param["main_tenance_mode_url"] . "”，";
			}
			updateConfiguration("main_tenance_mode_url", $param["main_tenance_mode_url"]);
			$company_name = configuration("logo_url_home");
			if ($param["logo_url_home"] != $company_name) {
				$dec .= "前台logo地址由“" . $company_name . "”改为“" . $param["logo_url_home"] . "”，";
			}
			updateConfiguration("logo_url_home", $param["logo_url_home"]);
			$company_name = configuration("server_clause_url");
			if ($param["server_clause_url"] != $company_name) {
				$dec .= "服务条款地址由“" . $company_name . "”改为“" . $param["server_clause_url"] . "”，";
			}
			updateConfiguration("server_clause_url", $param["server_clause_url"]);
			$company_name = configuration("privacy_clause_url");
			if ($param["privacy_clause_url"] != $company_name) {
				$dec .= "隐私条款地址由“" . $company_name . "”改为“" . $param["privacy_clause_url"] . "”，";
			}
			updateConfiguration("privacy_clause_url", $param["privacy_clause_url"]);
			$main_phone = configuration("main_phone");
			if ($param["main_phone"] != $main_phone) {
				$dec .= "联系方式由“" . $main_phone . "”改为“" . $param["main_phone"] . "”，";
			}
			updateConfiguration("main_phone", $param["main_phone"]);
			$main_address = configuration("main_address");
			if ($param["main_address"] != $main_address) {
				$dec .= "地址由“" . $main_address . "”改为“" . $param["main_address"] . "”，";
			}
			updateConfiguration("main_address", $param["main_address"]);
			$record_no = configuration("record_no");
			if ($param["record_no"] != $record_no) {
				$dec .= "备案号由“" . $record_no . "”改为“" . $param["record_no"] . "”，";
			}
			updateConfiguration("record_no", $param["record_no"]);
			$map = configuration("map");
			if ($param["map"] != $map) {
				$dec .= "坐标由“" . $map . "”改为“" . $param["map"] . "”，";
			}
			updateConfiguration("map", $param["map"]);
			$company_profile = configuration("company_profile");
			if ($param["company_profile"] != $company_profile) {
				$dec .= "公司简介由“" . $company_profile . "”改为“" . $param["company_profile"] . "”，";
			}
			updateConfiguration("company_profile", $param["company_profile"]);
			$www_logo = configuration("www_logo");
			if ($param["www_logo"] != $www_logo) {
				$dec .= "官网LOGO由“" . $www_logo . "”改为“" . $param["www_logo"] . "”，";
			}
			updateConfiguration("www_logo", $param["www_logo"]);
			$seo_keywords = configuration("seo_keywords");
			if ($param["seo_keywords"] != $seo_keywords) {
				$dec .= "关键字由“" . $seo_keywords . "”改为“" . $param["seo_keywords"] . "”，";
			}
			updateConfiguration("seo_keywords", $param["seo_keywords"]);
			$seo_desc = configuration("seo_desc");
			if ($param["seo_desc"] != $seo_desc) {
				$dec .= "描述由“" . $seo_desc . "”改为“" . $param["seo_desc"] . "”，";
			}
			updateConfiguration("seo_desc", $param["seo_desc"]);
			$web_widgets = configuration("web_widgets");
			if ($param["web_widgets"] != $web_widgets) {
				$dec .= "挂件由“" . $web_widgets . "”改为“" . $param["web_widgets"] . "”，";
			}
			updateConfiguration("web_widgets", $param["web_widgets"]);
			$per_page_limit = configuration("per_page_limit") ?? 50;
			if ($param["per_page_limit"] != $per_page_limit) {
				$dec .= "每页条数由“" . $per_page_limit . "”改为“" . $param["per_page_limit"] . "”，";
			}
			updateConfiguration("per_page_limit", $param["per_page_limit"]);
			$header = configuration("header");
			if ($headers != $header) {
				$dec .= "头部由“" . $header . "”改为“" . $headers . "”，";
			}
			updateConfiguration("header", $headers);
			$footer = configuration("footer");
			if ($footers != $footer) {
				$dec .= "底部由“" . $footer . "”改为“" . $footers . "”，";
			}
			updateConfiguration("footer", $footers);
			$cancellation_time = configuration("cancellation_time");
			if ($param["cancellation_time"] != $cancellation_time) {
				$dec .= "注销时间由“" . $cancellation_time . "”改为“" . $param["cancellation_time"] . "”，";
			}
			updateConfiguration("cancellation_time", $param["cancellation_time"]);
			$themes_templates = configuration("themes_templates");
			if ($param["themes_templates"] != $themes_templates) {
				$dec .= "主题模板由“" . $themes_templates . "”改为“" . $param["themes_templates"] . "”，";
			}
			updateConfiguration("themes_templates", $param["themes_templates"]);
			$login_header_footer = configuration("login_header_footer");
			if ($param["login_header_footer"] != $login_header_footer) {
				if ($param["login_header_footer"] == 1) {
					$dec .= "前台登录是否显示头部底部由“关闭”改为“开启”，";
				} else {
					$dec .= "前台登录是否显示头部底部由“开启”改为“关闭”，";
				}
			}
			updateConfiguration("login_header_footer", $param["login_header_footer"] ?: 0);
			$login_header = configuration("login_header");
			if ($param["login_header"] != $login_header) {
				$dec .= "前台登录头部由“" . $login_header . "”改为“" . $param["login_header"] . "”，";
			}
			updateConfiguration("login_header", $param["login_header"] ?: "");
			$login_footer = configuration("login_footer");
			if ($param["login_footer"] != $login_footer) {
				$dec .= "前台登录底部由“" . $login_footer . "”改为“" . $param["login_footer"] . "”，";
			}
			updateConfiguration("login_footer", $param["login_footer"] ?: "");
			$cart_product_description = configuration("cart_product_description");
			if ($param["cart_product_description"] != $cart_product_description) {
				$dec .= "前台登录底部由“" . $cart_product_description . "”改为“" . $param["cart_product_description"] . "”，";
			}
			updateConfiguration("cart_product_description", $param["cart_product_description"] ? trim($param["cart_product_description"]) : "");
			updateConfiguration("clientarea_default_themes", $param["clientarea_default_themes"] ? trim($param["clientarea_default_themes"]) : "default");
			$company_name = configuration("credit_limit");
			if ($param["credit_limit"] != $company_name) {
				if ($param["credit_limit"] == 1) {
					$dec .= "前台信用额由“关闭”改为“开启”，";
				} else {
					$dec .= "前台信用额由“开启”改为“关闭”，";
				}
			}
			updateConfiguration("credit_limit", $param["credit_limit"] ?: 0);
			active_log_final(sprintf($this->lang["ConfigGen_admin_postGeneral"], $dec));
			unset($dec);
			unset($company_name);
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 常规设置页面-本地化
	 * @description 接口说明:本地化设置页面
	 * @author 萧十一郎
	 * @url /admin/config_general/local
	 * @method GET
	 * @return charset:默认字符集(utf-8)
	 * @return date_format:后台日期格式
	 * @return client_date_format:前台用户显示的日期格式
	 * @return default_country:默认国家
	 * @return language:默认语言
	 * @return allow_user_language:是否允许用户更改系统语言 1,0
	 * @return tel_cc_input:是否自动格式化手机号码 1,0
	 */
	public function getLocal()
	{
		$returndata = [];
		$config_files = ["charset", "date_format", "client_date_format", "default_country", "language", "allow_user_language", "tel_cc_input"];
		$config_data = \think\Db::name("configuration")->whereIn("setting", $config_files)->select()->toArray();
		$config_value = [];
		foreach ($config_files as $key => $val) {
			$config_value[$val] = "";
		}
		foreach ($config_data as $key => $val) {
			$config_value[$val["setting"]] = $val["value"];
		}
		$returndata["config_value"] = $config_value;
		$date_format_option = ["DD.MM.YYYY" => "DD.MM.YYYY", "DD-MM-YYYY" => "DD-MM-YYYY", "MM/DD/YYYY" => "MM/DD/YYYY", "YYYY/MM/DD" => "YYYY/MM/DD", "YYYY-MM-DD" => "YYYY-MM-DD"];
		$returndata["date_format_option"] = $date_format_option;
		$client_date_format_option = ["full" => "2020年1月1日", "shortmonth" => "1月1日 2020年"];
		$returndata["client_date_format_option"] = $client_date_format_option;
		$language_list = get_lang();
		$returndata["language_option"] = $language_list;
		return jsonrule(["status" => 200, "data" => $returndata]);
	}
	/**
	 * @title 常规设置-本地化页面提交
	 * @description 接口说明:常规设置页面提交
	 * @author 萧十一郎
	 * @url /admin/config_general/local
	 * @method POST
	 * @param .name:charset type:string require:1 default: other: desc:默认字符集(utf-8)
	 * @param .name:date_format type:string require:1 default: other: desc:后台日期格式
	 * @param .name:client_date_format type:string require:1 default: other: desc:前台用户显示的日期格式
	 * @param .name:default_country type:string require:1 default: other: desc:默认国家
	 * @param .name:language type:string require:1 default: other: desc:默认语言
	 * @param .name:allow_user_language type:int require:1 default:0 other: desc:是否允许用户更改系统语言 1,0
	 * @param .name:tel_cc_input type:int require:1 default: other:0 desc:是否自动格式化手机号码 1,0
	 */
	public function postLocal()
	{
		if ($this->request->isPost()) {
			$param = $this->request->param();
			if (!$this->validate->scene("local")->check($param)) {
				return jsonrule(["status" => 400, "msg" => $this->validate->getError()]);
			}
			if ($param["language"] != configuration("language")) {
				cookie("lang", $param["language"]);
			}
			$trim = array_map("trim", $param);
			$param = array_map("htmlspecialchars", $trim);
			updateConfiguration("charset", $param["charset"]);
			updateConfiguration("date_format", $param["date_format"]);
			updateConfiguration("client_date_format", $param["client_date_format"]);
			updateConfiguration("default_country", $param["default_country"]);
			updateConfiguration("language", $param["language"]);
			updateConfiguration("allow_user_language", $param["allow_user_language"] ?: 0);
			updateConfiguration("tel_cc_input", $param["tel_cc_input"] ?: 0);
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 常规设置页面-支持
	 * @description 接口说明:支持设置页面
	 * @author 萧十一郎
	 * @url /admin/config_general/support
	 * @method GET
	 * @return nologin_send_ticket:不登录是否可提交工单
	 * @return evaluate_ticket:是否允许客户为工作人员的回复进行评价
	 * @return ticket_reply_order:工单回复列表排序
	 * @return dl_incl_product:包括产品下载
	 */
	public function getSupport()
	{
		$returndata = [];
		$config_files = ["nologin_send_ticket", "evaluate_ticket", "ticket_reply_order", "dl_incl_product"];
		$config_data = \think\Db::name("configuration")->whereIn("setting", $config_files)->select()->toArray();
		$config_value = [];
		foreach ($config_files as $key => $val) {
			$config_value[$val] = "";
		}
		foreach ($config_data as $key => $val) {
			$config_value[$val["setting"]] = $val["value"];
		}
		$returndata["config_value"] = $config_value;
		return jsonrule(["status" => 200, "data" => $returndata]);
	}
	/**
	 * @title 常规设置-支持页面提交
	 * @description 接口说明:支持设置页面提交
	 * @author 萧十一郎
	 * @url /admin/config_general/support
	 * @method POST
	 * @param .name:nologin_send_ticket type:int require:1 default:0 other: desc:不登录是否可提交工单
	 * @param .name:evaluate_ticket type:int require:1 default:0 other: desc:是否允许客户为工作人员的回复进行评价
	 * @param .name:ticket_reply_order type:string require:1 default:asc other: desc:工单回复列表排序
	 * @param .name:dl_incl_product type:int require:1 default:0 other: desc:包括产品下载
	 */
	public function postSupport()
	{
		if ($this->request->isPost()) {
			$param = $this->request->param();
			if (!$this->validate->scene("support")->check($param)) {
				return jsonrule(["status" => 400, "msg" => $this->validate->getError()]);
			}
			$trim = array_map("trim", $param);
			$param = array_map("htmlspecialchars", $trim);
			updateConfiguration("nologin_send_ticket", $param["nologin_send_ticket"] ?: 0);
			updateConfiguration("evaluate_ticket", $param["evaluate_ticket"] ?: 0);
			updateConfiguration("ticket_reply_order", $param["ticket_reply_order"]);
			updateConfiguration("dl_incl_product", $param["dl_incl_product"] ?: 0);
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 常规设置页面-推介
	 * @description 接口说明:推介
	 * @author 萧十一郎
	 * @url /admin/config_general/affiliate
	 * @method GET
	 * @return affiliate_enabled:是否启用推介
	 * @return affiliate_invited:是否启用应邀返利
	 * @return affiliate_invited_type:应邀返利类型
	 * @return affiliate_invited_money:应邀返利金额
	 * @return affiliate_bonusde_posit:推介计划激活赠送金额
	 * @return affiliate_bates:推介计划比例
	 * @return affiliate_type:推介计划类型 1金额  2百分比
	 * @return affiliate_cookie:推荐链接cookie有效期
	 * @return affiliate_withdraw:提现最低金额
	 * @return affiliate_is_authentication:是否要求先实名
	 * @return affiliate_delay_commission:延迟订单支付的天数
	 * @return affiliate_is_reorder:是否开启二次订单
	 * @return affiliate_reorder:二次订单比例
	 * @return affiliate_reorder_type:二次订单比例类型 1金额  2百分比
	 * @return affiliate_is_renew:是否开启续费
	 * @return affiliate_renew:续费比例
	 * @return affiliate_renew_type:续费比例类型 1金额  2百分比
	 */
	public function getAffiliate()
	{
		$returndata = [];
		$config_files = ["affiliate_enabled", "affiliate_bonusde_posit", "affiliate_bates", "affiliate_type", "affiliate_cookie", "affiliate_withdraw", "affiliate_is_authentication", "affiliate_delay_commission", "affiliate_is_reorder", "affiliate_reorder", "affiliate_reorder_type", "affiliate_is_renew", "affiliate_renew", "affiliate_renew_type", "affiliate_url", "affiliate_invited", "affiliate_invited_money", "affiliate_invited_type"];
		$config = configuration($config_files);
		$config["affiliate_type"] = $config["affiliate_type"] ?? 1;
		$config["affiliate_reorder_type"] = $config["affiliate_reorder_type"] ?? 1;
		$config["affiliate_renew_type"] = $config["affiliate_renew_type"] ?? 1;
		$config["affiliate_is_authentication"] = $config["affiliate_is_authentication"] ?? 0;
		$returndata["config_value"] = $config;
		return jsonrule(["status" => 200, "data" => $returndata]);
	}
	/**
	 * @title 常规设置-推介页面提交
	 * @description 接口说明:推介页面提交
	 * @author 萧十一郎
	 * @url /admin/config_general/postaffiliate
	 * @method POST
	 * @param .name:affiliate_enabled type:int require:0 default:0 other: desc:是否启用推介
	 * @param .name:affiliate_bonusde_posit type:float require:0 default:0 other: desc:推介计划激活赠送金额
	 * @param .name:affiliate_bates type:float require:0 default:0 other: desc:推介计划比例
	 * @param .name:affiliate_type type:int require:1 default:0 other:  desc:推介计划比例类型1金额2百分比
	 * @param .name:affiliate_cookie type:float require:0 default:0 other: desc:推荐链接cookie有效期
	 * @param .name:affiliate_withdraw type:float require:0 default:0 other: desc:提现最低金额
	 * @param .name:affiliate_is_authentication type:int require:0 default:0 other: desc:是否要求先实名
	 * @param .name:affiliate_delay_commission type:float require:0 default:0 other: desc:延迟订单支付的天数
	 * @param .name:affiliate_is_reorder type:int require:0 default:0 other: desc:是否开启二次订单
	 * @param .name:affiliate_reorder type:float require:0 default:0 other: desc:二次订单比例
	 * @param .name:affiliate_reorder_type type:int require:1 default:0 other: desc:二次订单比例类型1金额2百分比
	 * @param .name:affiliate_is_renew type:int require:0 default:0 other: desc:是否开启续费
	 * @param .name:affiliate_renew type:float require:0 default:0 other: desc:续费比例
	 * @param .name:affiliate_renew_type type:int require:1 default:0 other: desc:续费比例比例类型1金额2百分比
	 * @param .name:affiliate_url type:string require:0 default:0 other: desc:推荐地址
	 */
	public function postAffiliate()
	{
		if ($this->request->isPost()) {
			$param = $this->request->param();
			if (!$this->validate->scene("support")->check($param)) {
				return jsonrule(["status" => 400, "msg" => $this->validate->getError()]);
			}
			$trim = array_map("trim", $param);
			$param = array_map("htmlspecialchars", $trim);
			$desc = "";
			if ($param["affiliate_enabled"] == 1) {
				if ($param["affiliate_enabled"] != null) {
					if (configuration("affiliate_enabled") != $param["affiliate_enabled"]) {
						if ($param["affiliate_enabled"] == 1) {
							$desc .= "推介计划由“关闭”改为“开启”，";
						} else {
							$desc .= "推介计划由“开启”改为“关闭”，";
						}
					}
					updateConfiguration("affiliate_enabled", $param["affiliate_enabled"]);
				} else {
					return jsonrule(["status" => 400, "msg" => "必填字段不能为空"]);
				}
				if ($param["affiliate_bonusde_posit"] != null) {
					if ($param["affiliate_bonusde_posit"] < 0) {
						return jsonrule(["status" => 400, "msg" => "推介计划激活赠送金额不能为负数"]);
					}
					if (configuration("affiliate_bonusde_posit") != $param["affiliate_bonusde_posit"]) {
						$desc .= "推介计划激活赠送金额由“" . configuration("affiliate_bonusde_posit") . "”改为“" . $param["affiliate_bonusde_posit"] . "”，";
					}
					updateConfiguration("affiliate_bonusde_posit", $param["affiliate_bonusde_posit"]);
				} else {
					return jsonrule(["status" => 400, "msg" => "推介计划激活赠送金额不能为空"]);
				}
				if ($param["affiliate_invited"] != null) {
					if (configuration("affiliate_invited") != $param["affiliate_invited"]) {
						if ($param["affiliate_invited"] == 1) {
							$desc .= "推介计划应邀返利由“关闭”改为“开启”，";
						} else {
							$desc .= "推介计划应邀返利由“开启”改为“关闭”，";
						}
					}
					updateConfiguration("affiliate_invited", $param["affiliate_invited"]);
				} else {
					return jsonrule(["status" => 400, "msg" => "推介计划应邀返利不能为空"]);
				}
				if ($param["affiliate_invited"] == 1) {
					if ($param["affiliate_invited_money"] != null) {
						if ($param["affiliate_invited_money"] < 0) {
							return jsonrule(["status" => 400, "msg" => "推介计划应邀返利金额不能为负数"]);
						}
						if (configuration("affiliate_invited_money") != $param["affiliate_invited_money"]) {
							$desc .= "推介计划应邀返利金额由“" . configuration("affiliate_invited_money") . "”改为“" . $param["affiliate_invited_money"] . "”，";
						}
						updateConfiguration("affiliate_invited_money", $param["affiliate_invited_money"]);
					} else {
						return jsonrule(["status" => 400, "msg" => "推介计划应邀返利金额不能为空"]);
					}
					if ($param["affiliate_invited_type"] != null) {
						if (configuration("affiliate_invited_type") != $param["affiliate_invited_type"]) {
							if (configuration("affiliate_invited_type") == 1) {
								$str = "余额";
							} else {
								$str = "其他";
							}
							if ($param["affiliate_invited_type"] == 1) {
								$str1 = "余额";
							} else {
								$str1 = "其他";
							}
							$desc .= "推介计划应邀返利方式由“" . $str . "”改为“" . $str1 . "”，";
						}
						updateConfiguration("affiliate_invited_type", $param["affiliate_invited_type"]);
					}
				}
				if ($param["affiliate_bates"] != null) {
					if ($param["affiliate_bates"] < 0) {
						return jsonrule(["status" => 400, "msg" => "推介计划比例不能为负数"]);
					}
					if (configuration("affiliate_bates") != $param["affiliate_bates"]) {
						$desc .= "推介计划比例由“" . configuration("affiliate_bates") . "”改为“" . $param["affiliate_bates"] . "”，";
					}
					updateConfiguration("affiliate_bates", $param["affiliate_bates"]);
				} else {
					return jsonrule(["status" => 400, "msg" => "推介计划比例类型不能为空"]);
				}
				if ($param["affiliate_type"] != null) {
					if (configuration("affiliate_type") != $param["affiliate_type"]) {
						$str = "";
						$str1 = "";
						if (configuration("affiliate_type") == 1) {
							$str = "金额";
						} else {
							$str = "百分比";
						}
						if ($param["affiliate_type"] == 1) {
							$str1 = "金额";
						} else {
							$str1 = "百分比";
						}
						$desc .= "推介计划比例类型由“" . $str . "”改为“" . $str1 . "”，";
					}
					updateConfiguration("affiliate_type", $param["affiliate_type"]);
				}
				if ($param["affiliate_cookie"] != null) {
					if (configuration("affiliate_cookie") != $param["affiliate_cookie"]) {
						$desc .= "推荐链接cookie有效期由“" . configuration("affiliate_cookie") . "”改为“" . $param["affiliate_cookie"] . "”，";
					}
					updateConfiguration("affiliate_cookie", $param["affiliate_cookie"]);
				} else {
					return jsonrule(["status" => 400, "msg" => "推荐链接cookie有效期不能为空"]);
				}
				if ($param["affiliate_withdraw"] != null) {
					if ($param["affiliate_withdraw"] < 0) {
						return jsonrule(["status" => 400, "msg" => "提现最低金额不能为负数"]);
					}
					if (configuration("affiliate_withdraw") != $param["affiliate_withdraw"]) {
						$desc .= "提现最低金额由“" . configuration("affiliate_withdraw") . "”改为“" . $param["affiliate_withdraw"] . "”，";
					}
					updateConfiguration("affiliate_withdraw", $param["affiliate_withdraw"]);
				} else {
					return jsonrule(["status" => 400, "msg" => "提现最低金额不能为空"]);
				}
				if ($param["affiliate_is_authentication"] != null) {
					if (configuration("affiliate_is_authentication") != $param["affiliate_is_authentication"]) {
						if ($param["affiliate_is_authentication"] == 1) {
							$desc .= "提现实名由“关闭”改为“开启”，";
						} else {
							$desc .= "提现实名由“开启”改为“关闭”，";
						}
					}
					updateConfiguration("affiliate_is_authentication", $param["affiliate_is_authentication"]);
				} else {
					return jsonrule(["status" => 400, "msg" => "是否要求先实名不能为空"]);
				}
				if ($param["affiliate_delay_commission"] != null) {
					if (configuration("affiliate_delay_commission") != $param["affiliate_delay_commission"]) {
						$desc .= "延迟订单支付的天数由“" . configuration("affiliate_delay_commission") . "”改为“" . $param["affiliate_delay_commission"] . "”，";
					}
					updateConfiguration("affiliate_delay_commission", $param["affiliate_delay_commission"]);
				} else {
					return jsonrule(["status" => 400, "msg" => "延迟订单支付的天数不能为空"]);
				}
				if (configuration("affiliate_is_reorder") != $param["affiliate_is_reorder"]) {
					if ($param["affiliate_is_reorder"] == 1) {
						$desc .= "二次订单由“关闭”改为“开启”，";
					} else {
						$desc .= "二次订单由“开启”改为“关闭”，";
					}
				}
				updateConfiguration("affiliate_is_reorder", $param["affiliate_is_reorder"]);
				if ($param["affiliate_is_reorder"] == 1) {
					if ($param["affiliate_reorder"] != null) {
						if ($param["affiliate_reorder"] < 0) {
							return jsonrule(["status" => 400, "msg" => "二次订单比例不能为负数"]);
						}
						if (configuration("affiliate_reorder") != $param["affiliate_reorder"]) {
							$desc .= "二次订单比例由“" . configuration("affiliate_reorder") . "”改为“" . $param["affiliate_reorder"] . "”，";
						}
						updateConfiguration("affiliate_reorder", $param["affiliate_reorder"]);
					} else {
						return jsonrule(["status" => 400, "msg" => "二次订单比例不能为空"]);
					}
					if ($param["affiliate_reorder_type"] != null) {
						if (configuration("affiliate_reorder_type") != $param["affiliate_reorder_type"]) {
							$str = "";
							$str1 = "";
							if (configuration("affiliate_reorder_type") == 1) {
								$str = "金额";
							} else {
								$str = "百分比";
							}
							if ($param["affiliate_reorder_type"] == 1) {
								$str1 = "金额";
							} else {
								$str1 = "百分比";
							}
							$desc .= "二次订购比例类型由“" . $str . "”改为“" . $str1 . "”，";
						}
						updateConfiguration("affiliate_reorder_type", $param["affiliate_reorder_type"]);
					}
				}
				if (configuration("affiliate_is_renew") != $param["affiliate_is_renew"]) {
					if ($param["affiliate_is_renew"] == 1) {
						$desc .= "续费由“关闭”改为“开启”，";
					} else {
						$desc .= "续费由“开启”改为“关闭”，";
					}
				}
				updateConfiguration("affiliate_is_renew", $param["affiliate_is_renew"]);
				if ($param["affiliate_is_renew"] == 1) {
					if ($param["affiliate_renew"] != null) {
						if ($param["affiliate_renew"] < 0) {
							return jsonrule(["status" => 400, "msg" => "续费比例不能为负数"]);
						}
						if (configuration("affiliate_renew") != $param["affiliate_renew"]) {
							$desc .= "续费比例由“" . configuration("affiliate_renew") . "”改为“" . $param["affiliate_renew"] . "”，";
						}
						updateConfiguration("affiliate_renew", $param["affiliate_renew"]);
					} else {
						return jsonrule(["status" => 400, "msg" => "续费比例不能为空"]);
					}
					if ($param["affiliate_renew_type"] != null) {
						if (configuration("affiliate_renew_type") != $param["affiliate_renew_type"]) {
							$str = "";
							$str1 = "";
							if (configuration("affiliate_renew_type") == 1) {
								$str = "金额";
							} else {
								$str = "百分比";
							}
							if ($param["affiliate_reorder_type"] == 1) {
								$str1 = "金额";
							} else {
								$str1 = "百分比";
							}
							$desc .= "续费比例比例类型由“" . $str . "”改为“" . $str1 . "”，";
						}
						updateConfiguration("affiliate_renew_type", $param["affiliate_renew_type"]);
					} else {
						return jsonrule(["status" => 400, "msg" => "续费比例比例类型不能为空"]);
					}
				}
			} else {
				updateConfiguration("affiliate_enabled", $param["affiliate_enabled"]);
			}
			if (empty($desc)) {
				$desc .= "什么都没修改";
			}
			active_log_final(sprintf($this->lang["Aff_admin_aff"], $desc));
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 阶梯列表页
	 * @description 接口说明:阶梯列表页(含搜索)
	 * @param .name:page type:int require:1 default:1 other: desc:第几页
	 * @param .name:limit type:int require:1 default:10 other: desc:每页多少条
	 * @param .name:order type:string require:1 default:10 other: desc:排序字段
	 * @param .name:sort type:int require:1 default:10 other: desc:AESC,DESC
	 * @return total:阶梯列表页总数
	 * @return list:阶梯列表页数据@
	 * @list  group_name:营业额
	 * @list  bates:比例
	 * @list  is_flag:是否开启
	 * @url /admin/affladder
	 * @method GET
	 */
	public function ladderList()
	{
		$params = $data = $this->request->param();
		$page = !empty($params["page"]) ? intval($params["page"]) : config("page");
		$limit = !empty($params["limit"]) ? intval($params["limit"]) : config("limit");
		$order = !empty($params["order"]) ? trim($params["order"]) : "turnover";
		$sort = !empty($params["sort"]) ? trim($params["sort"]) : "ASC";
		$total = \think\Db::name("affiliate_ladder")->count();
		$list = \think\Db::name("affiliate_ladder")->order($order, $sort)->page($page)->limit($limit)->select()->toArray();
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "total" => $total, "list" => $list]);
	}
	/**
	 * @title 添加阶梯
	 * @description 接口说明:添加阶梯
	 * @author 刘国栋
	 * @url /admin/aff/add_affladder
	 * @method POST
	 * @param .name:turnover type:string require:1 default:0 other: desc:营业额
	 * @param .name:bates type:float require:1 default:0 other: desc:比例
	 * @param .name:is_flag type:int require:1 default:0 other: desc:是否开启
	 */
	public function addAffLadder()
	{
		if ($this->request->isPost()) {
			$param = $this->request->param();
			$data["turnover"] = isset($param["turnover"]) ? floatval($param["turnover"]) : 0;
			$data["bates"] = isset($param["bates"]) ? floatval($param["bates"]) : 0;
			$data["is_flag"] = isset($param["is_flag"]) ? intval($param["is_flag"]) : 0;
			$cid = \think\Db::name("affiliate_ladder")->insertGetId($data);
			if (!$cid) {
				return jsonrule(["status" => 400, "msg" => lang("ADD FAIL")]);
			}
			active_log_final(sprintf($this->lang["Aff_admin_addSaleladder"], $cid));
			return jsonrule(["status" => 200, "msg" => lang("ADD SUCCESS")]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 编辑阶梯页面
	 * @description 接口说明:编辑阶梯页面
	 * @param .name:id type:int require:1  other: desc:阶梯ID
	 * @throws
	 * @author 刘国栋
	 * @url /admin/aff/edit_affladderpage
	 * @method get
	 * @return spg:分组@
	 * @spg id:组id turnover:营业额 bates:比例 is_flag:是否开启@
	 */
	public function editAffLadderPage()
	{
		$params = $this->request->param();
		$id = intval($params["id"]);
		if (empty($id)) {
			return json(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
		}
		$spg = \think\Db::name("affiliate_ladder")->where("id", $id)->find();
		$data = ["ladder" => $spg];
		return jsonrule(["status" => 200, "data" => $data, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * @title 编辑阶梯
	 * @description 接口说明: 编辑阶梯
	 * @param .name:id type:int require:1  other: desc:分组ID
	 * @param .name:turnover type:string require:1 default:1 other: desc:营业额
	 * @param .name:bates type:float require:1 default:0 other: desc:提成比例
	 * @param .name:is_flag type:int require:1 default:0 other: desc:是否开启
	 * @throws
	 * @author lgd
	 * @url /admin/aff/edit_affladder
	 * @method post
	 */
	public function editAffLadder()
	{
		if ($this->request->isPost()) {
			$param = $this->request->param();
			$data["turnover"] = isset($param["turnover"]) ? floatval($param["turnover"]) : 0;
			$data["bates"] = isset($param["bates"]) ? floatval($param["bates"]) : 0;
			$data["is_flag"] = isset($param["is_flag"]) ? intval($param["is_flag"]) : 0;
			$desc = "";
			$spg = \think\Db::name("affiliate_ladder")->where("id", $param["id"])->find();
			if ($spg["turnover"] != $param["turnover"]) {
				$desc .= " 营业额" . $spg["turnover"] . "改为" . $param["turnover"];
			}
			if ($spg["bates"] != $param["bates"]) {
				$desc .= " 提成比例" . $spg["bates"] . "改为" . $param["bates"];
			}
			if ($spg["is_flag"] != $param["is_flag"]) {
				if ($spg["is_flag"] == 1) {
					$desc .= " 开启";
				} else {
					$desc .= " 不开启";
				}
			}
			\think\Db::name("affiliate_ladder")->where("id", $param["id"])->update($data);
			active_log_final(sprintf($this->lang["Aff_admin_editSaleladder"], $param["id"], $desc));
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 删除阶梯
	 * @description 接口说明:删除阶梯
	 * @param .name:id type:int require:1  other: desc:阶梯ID
	 * @throws
	 * @author 刘国栋
	 * @url /admin/aff/del_affladder
	 * @method get
	 */
	public function delAffLadder()
	{
		$params = $this->request->param();
		$id = intval($params["id"]);
		$res = \think\Db::name("affiliate_ladder")->where("id", $id)->find();
		if (!empty($res)) {
			\think\Db::name("affiliate_ladder")->where("id", $id)->delete();
			active_log_final(sprintf($this->lang["Aff_admin_delSaleladder"], $id));
			return jsonrule(["status" => 200, "msg" => lang("DELETE SUCCESS")]);
		} else {
			return jsonrule(["status" => 400, "msg" => lang("DELETE FAIL")]);
		}
	}
	/**
	 * @title 常规设置页面-安全
	 * @description 接口说明:
	 * @author 萧十一郎
	 * @url /admin/config_general/safe
	 * @method GET
	 * @return required_pwstrength:密码强度
	 * @return invalid_logins_banlength:管理员无法登录时间
	 * @return pass_strength_list:密码强度下拉选项
	 */
	public function getSafe()
	{
		$returndata = [];
		$config_files = ["required_pwstrength", "invalid_logins_banlength"];
		$config_data = \think\Db::name("configuration")->whereIn("setting", $config_files)->select()->toArray();
		$config_value = [];
		foreach ($config_files as $key => $val) {
			$config_value[$val] = "";
		}
		foreach ($config_data as $key => $val) {
			$config_value[$val["setting"]] = $val["value"];
		}
		$returndata["config_value"] = $config_value;
		$returndata["pass_strength_list"] = ["none" => "禁用", "alpha_num" => "字母+数字", "catipal_alpha_num" => "大小写字母+数字", "alpha_num_special" => "字母+数字+特殊字符"];
		return jsonrule(["status" => 200, "data" => $returndata]);
	}
	/**
	 * @title 常规设置-安全页面提交
	 * @description 接口说明:安全页面提交
	 * @author 萧十一郎
	 * @url /admin/config_general/safe
	 * @method POST
	 * @param .name:required_pwstrength type:string require:0 default:0 other: desc:密码强度
	 * @param .name:invalid_logins_banlength type:number require:0 default:0 other: desc:管理员登录失败限制时间
	 */
	public function postSafe()
	{
		if ($this->request->isPost()) {
			$param = $this->request->param();
			if (!$this->validate->scene("safe")->check($param)) {
				return jsonrule(["status" => 400, "msg" => $this->validate->getError()]);
			}
			$trim = array_map("trim", $param);
			$param = array_map("htmlspecialchars", $trim);
			updateConfiguration("required_pwstrength", $param["required_pwstrength"]);
			updateConfiguration("invalid_logins_banlength", $param["invalid_logins_banlength"]);
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 常规设置页面-其他
	 * @description 接口说明:
	 * @author 萧十一郎
	 * @url /admin/config_general/other
	 * @method GET
	 * @return clients_profoptional:选填的客户配置字段(username:姓名,profession:职业,companyname:公司,email:邮箱,phonenumber:手机号)
	 * @return clients_profuneditable:锁定的客户配置字段(username:姓名,profession:职业,companyname:公司,email:邮箱,phonenumber:手机号)
	 * @return show_cancel:显示取消链接
	 * @return aff_report:每月推介报告
	 * @return display_errors:开启显示php错误
	 * @return sql_error_reporting:SQL 调试模式
	 * @return hooks_debug_mode:调试模式钩子
	 */
	public function getOther()
	{
		$returndata = [];
		$config_files = ["clients_profoptional", "clients_profuneditable", "show_cancel", "aff_report", "display_errors", "sql_error_reporting", "hooks_debug_mode"];
		$config_data = \think\Db::name("configuration")->whereIn("setting", $config_files)->select()->toArray();
		$config_value = [];
		foreach ($config_files as $key => $val) {
			$config_value[$val] = "";
		}
		foreach ($config_data as $key => $val) {
			$config_value[$val["setting"]] = $val["value"];
		}
		if (!empty($config_value["clients_profoptional"])) {
			$config_value["clients_profoptional_checked"] = explode(",", $config_value["clients_profoptional"]);
		} else {
			$config_value["clients_profoptional_checked"] = [];
		}
		if (!empty($config_value["clients_profuneditable"])) {
			$config_value["clients_profuneditable_checked"] = explode(",", $config_value["clients_profuneditable"]);
		} else {
			$config_value["clients_profuneditable_checked"] = [];
		}
		$returndata["config_value"] = $config_value;
		$returndata["clients_profoptional_list"] = ["username" => "姓名", "companyname" => "公司", "qq" => "QQ", "address1" => "地址"];
		$returndata["clients_profuneditable_list"] = ["username" => "姓名", "companyname" => "公司", "qq" => "QQ", "address1" => "地址"];
		return jsonrule(["status" => 200, "data" => $returndata]);
	}
	/**
	 * @title 常规设置-其他页面提交
	 * @description 接口说明:其他页面提交
	 * @author 萧十一郎
	 * @url /admin/config_general/other
	 * @method POST
	 * @param .name:clients_profoptional type:array require:0 default:0 other: desc:选填的客户配置字段(username:姓名,profession:职业,companyname:公司,email:邮箱,phonenumber:手机号)
	 * @param .name:clients_profuneditable type:array require:0 default:0 other: desc:锁定的客户配置字段(username:姓名,profession:职业,companyname:公司,email:邮箱,phonenumber:手机号)
	 * @param .name:show_cancel type:number require:1 default:0 other: desc: 1,0在客户产品页面显示取消链接
	 * @param .name:aff_report type:number require:1 default:0 other: desc: 1,0每月推介报告
	 * @param .name:display_errors type:number require:0 default:0 other: desc:1,0开启显示php错误
	 * @param .name:sql_error_reporting type:number require:0 default:0 other: desc:1,0SQL 调试模式
	 * @param .name:hooks_debug_mode type:number require:0 default:0 other: desc:1,0调试模式钩子
	 */
	public function postOther()
	{
		if ($this->request->isPost()) {
			$param = $this->request->param();
			if (!$this->validate->scene("safe")->check($param)) {
				return jsonrule(["status" => 400, "msg" => $this->validate->getError()]);
			}
			$allow = ["username", "companyname", "qq", "address1"];
			foreach ($param["clients_profoptional"] as $v) {
				if (in_array($v, $allow)) {
					$filter[] = $v;
				}
			}
			$clients_profoptional = implode(",", $filter);
			$clients_profuneditable = implode(",", $param["clients_profuneditable"]);
			$trim = array_map("trim", $param);
			$param = array_map("htmlspecialchars", $trim);
			updateConfiguration("clients_profoptional", $clients_profoptional);
			updateConfiguration("clients_profuneditable", $clients_profuneditable);
			updateConfiguration("show_cancel", $param["show_cancel"] ?: 0);
			updateConfiguration("aff_report", $param["aff_report"] ?: 0);
			updateConfiguration("display_errors", $param["display_errors"] ?: 0);
			updateConfiguration("sql_error_reporting", $param["sql_error_reporting"] ?: 0);
			updateConfiguration("hooks_debug_mode", $param["hooks_debug_mode"] ?: 0);
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 常规设置-充值页面
	 * @description 接口说明:充值页面
	 * @author 萧十一郎
	 * @url /admin/config_general/recharge
	 * @method GET
	 * @return addfunds_enabled:1.客户可以在用户中心添加余额至账户中
	 * @param addfunds_minimum:客户在单笔账单中可以支付的最小金额
	 * @param addfunds_maximum:客户在单笔账单中可以支付的最大金额
	 * @param addfunds_maximum_balance:客户可以添加进余额的最大金额
	 * @param addfunds_require_order:1,0需要激活的订单
	 * @param no_auto_apply_credit:1,0自动应用经常性发票
	 * @param credit_on_downgrade:1,0降级退还至余额
	 */
	public function getRecharge()
	{
		$returndata = [];
		$config_files = ["addfunds_enabled", "addfunds_minimum", "addfunds_maximum", "addfunds_maximum_balance", "addfunds_require_order", "no_auto_apply_credit", "credit_on_downgrade"];
		$config_data = \think\Db::name("configuration")->whereIn("setting", $config_files)->select()->toArray();
		$config_value = [];
		foreach ($config_files as $key => $val) {
			$config_value[$val] = "";
		}
		foreach ($config_data as $key => $val) {
			$config_value[$val["setting"]] = $val["value"];
		}
		$returndata["config_value"] = $config_value;
		return jsonrule(["status" => 200, "data" => $returndata]);
	}
	/**
	 * @title 充值页面提交
	 * @description 接口说明:充值页面提交
	 * @author 萧十一郎
	 * @url /admin/config_general/recharge
	 * @method POST
	 * @param .name:addfunds_enabled type:array require:0 default:0 other: desc:1.0客户可以在用户中心添加余额至账户中
	 * @param .name:addfunds_minimum type:array require:0 default:0 other: desc:客户在单笔账单中可以支付的最小金额
	 * @param .name:addfunds_maximum type:number require:1 default:0 other: desc:客户在单笔账单中可以支付的最大金额
	 * @param .name:addfunds_maximum_balance type:number require:1 default:0 other: desc:最高余额	
	 * @param .name:addfunds_require_order type:number require:0 default:0 other: desc:1,0需要激活的订单
	 * @param .name:no_auto_apply_credit type:number require:0 default:0 other: desc:1,0自动应用经常性发票
	 * @param .name:credit_on_downgrade type:number require:0 default:0 other: desc:1,0降级退还至余额
	 */
	public function postRecharge()
	{
		if ($this->request->isPost()) {
			$param = $this->request->param();
			if (!$this->validate->scene("recharge")->check($param)) {
				return jsonrule(["status" => 400, "msg" => $this->validate->getError()]);
			}
			$dec = "";
			$company_name = configuration("addfunds_enabled");
			if ($param["addfunds_enabled"] != $company_name) {
				if ($param["addfunds_enabled"] == 1) {
					$dec .= " - 启用充值";
				} else {
					$dec .= " - 不启用充值";
				}
			}
			updateConfiguration("addfunds_enabled", $param["addfunds_enabled"] ?: 0);
			$company_name = configuration("addfunds_minimum");
			if ($param["addfunds_minimum"] != $company_name) {
				$dec .= " - 最小金额" . $company_name . "改为" . $param["addfunds_minimum"];
			}
			updateConfiguration("addfunds_minimum", $param["addfunds_minimum"]);
			$company_name = configuration("addfunds_maximum");
			if ($param["addfunds_maximum"] != $company_name) {
				$dec .= " - 最大金额" . $company_name . "改为" . $param["addfunds_maximum"];
			}
			updateConfiguration("addfunds_maximum", $param["addfunds_maximum"]);
			$company_name = configuration("addfunds_maximum_balance");
			if ($param["addfunds_maximum_balance"] != $company_name) {
				$dec .= " - 最高金额" . $company_name . "改为" . $param["addfunds_maximum_balance"];
			}
			updateConfiguration("addfunds_maximum_balance", $param["addfunds_maximum_balance"]);
			$company_name = configuration("addfunds_require_order");
			if ($param["addfunds_require_order"] != $company_name) {
				if ($param["addfunds_require_order"] == 1) {
					$dec .= " - 需要已激活的订单";
				} else {
					$dec .= " - 不需要已激活的订单";
				}
			}
			updateConfiguration("addfunds_require_order", $param["addfunds_require_order"] ?: 0);
			updateConfiguration("no_auto_apply_credit", $param["no_auto_apply_credit"] ?: 0);
			updateConfiguration("credit_on_downgrade", $param["credit_on_downgrade"] ?: 0);
			active_log_final(sprintf($this->lang["ConfigGen_admin_postGeneral"], $dec));
			unset($dec);
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 常规设置-账单页面
	 * @description 接口说明:账单页面
	 * @author 上官🔪
	 * @url /admin/config_general/invoice
	 * @method GET
	 * @return in_circulation_create:在每个周期内都生成账单，即使之前的账单未支付
	 * @return in_pdf:送包含 PDF 版本附件的账单邮件
	 * @return in_save_user_info:生成账单时保留客户资料，防止客户资料更改影响现有的账单
	 * @return in_batch_pay: 1,0在客户中心启用批量账单支付选项
	 * @return in_select_payment:允许客户选择所需的支付网关
	 * @return in_unpaid_tick: 未付订单启用形式发票
	 * @return in_continuous_pay_num:启用自动连续付款账单号码
	 * @return in_continuous_pay_num_type:可用的自动插入标签： {YEAR} {MONTH} {DAY} {NUMBER}
	 * @return in_overdue_fine:滞纳金类型
	 * @return in_overdue_fine_min:输入如果计算出来的滞纳金小于此值时，应收取的最低滞纳金金额
	 */
	public function getInvoice()
	{
		$returndata = [];
		$config_files = ["in_batch_pay", "in_circulation_create", "in_pdf", "in_save_user_info", "in_select_payment", "in_unpaid_tick", "in_continuous_pay_num", "in_continuous_pay_num_type", "in_overdue_fine", "in_overdue_fine_min"];
		$config_data = \think\Db::name("configuration")->whereIn("setting", $config_files)->select()->toArray();
		$config_value = [];
		foreach ($config_data as $key => $val) {
			$config_value[$val["setting"]] = $val["value"];
		}
		$returndata["config_value"] = $config_value;
		return jsonrule(["status" => 200, "data" => $returndata]);
	}
	/**
	 * @title 账单配置
	 * @description 接口说明:其他页面提交
	 * @author 上官🔪
	 * @url /admin/config_general/invoice
	 * @method POST
	 * @param .name:in_circulation_create type:int require:0 default:0 other: desc:在每个周期内都生成账单，即使之前的账单未支付
	 * @param .name:in_pdf type:int require:0 default:0 other: desc:送包含 PDF 版本附件的账单邮件
	 * @param .name:in_save_user_info type:int require:1 default:0 other: desc: 生成账单时保留客户资料，防止客户资料更改影响现有的账单
	 * @param .name:in_batch_pay type:int require:1 default:0 other: desc: 1,0在客户中心启用批量账单支付选项
	 * @param .name:in_select_payment type:int require:0 default:0 other: desc:允许客户选择所需的支付网关
	 * @param .name:in_unpaid_tick type:int require:0 default:0 other: desc:未付订单启用形式发票
	 * @param .name:in_continuous_pay_num type:int require:0 default:0 other: desc:启用自动连续付款账单号码
	 * @param .name:in_continuous_pay_num_type type:string require:0 default:0 other: desc:可用的自动插入标签： {YEAR} {MONTH} {DAY} {NUMBER}
	 * @param .name:in_overdue_fine type:string require:0 default:0 other: desc:滞纳金类型
	 * @param .name:in_overdue_fine_min type:string require:0 default:0 other: desc:输入如果计算出来的滞纳金小于此值时，应收取的最低滞纳金金额
	 */
	public function postInvoice()
	{
		$param = request()->param();
		if (!$this->validate->scene("invoice")->check($param)) {
			return jsonrule(["status" => 400, "msg" => $this->validate->getError()]);
		}
		try {
			updateConfiguration("in_circulation_create", $param["in_circulation_create"] ?? 0);
			updateConfiguration("in_pdf", $param["in_pdf"] ?? 0);
			updateConfiguration("in_save_user_info", $param["in_save_user_info"] ?? 0);
			updateConfiguration("in_batch_pay", $param["in_batch_pay"] ?? 0);
			updateConfiguration("in_select_payment", $param["in_select_payment"] ?? 0);
			updateConfiguration("in_unpaid_tick", $param["in_unpaid_tick"] ?? 0);
			updateConfiguration("in_continuous_pay_num", $param["in_continuous_pay_num"] ?? 0);
			updateConfiguration("in_continuous_pay_num_type", $param["in_continuous_pay_num_type"] ?? 0);
			updateConfiguration("in_overdue_fine", $param["in_overdue_fine"] ?? 0);
			updateConfiguration("in_overdue_fine_min", $param["in_overdue_fine_min"] ?? 0);
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")] ?? 0);
		} catch (\Exception $e) {
			return jsonrule(["status" => 400, "msg" => $e->getMessage()]);
		}
	}
	/**
	 * @title 注册登录--页面
	 * @description 接口说明:注册登录--页面
	 * @author wyh
	 * @url /admin/config_general/register_login_page
	 * @method get
	 */
	public function registerLoginPage()
	{
		$data = [];
		configuration("clients_profoptional");
		if (!empty($config_value["clients_profoptional"])) {
			$config_value["clients_profoptional_checked"] = explode(",", $config_value["clients_profoptional"]);
		} else {
			$config_value["clients_profoptional_checked"] = [];
		}
		$data["clients_profoptional_list"] = config("login_register_custom_require");
		$data["clients_profoptional_checked"] = !!configuration("clients_profoptional") ? explode(",", configuration("clients_profoptional")) : [];
		$data["allow_phone"] = !configuration("allow_phone") ? 0 : 1;
		$data["allow_email"] = !configuration("allow_email") ? 0 : 1;
		$data["allow_id"] = !configuration("allow_id") ? 0 : 1;
		$data["allow_wechat"] = !configuration("allow_wechat") ? 0 : 1;
		$data["allow_email_register_code"] = !configuration("allow_email_register_code") ? 0 : 1;
		$data["wechat_login_appid"] = configuration("wechat_login_appid") ?? "";
		$data["wechat_login_secret"] = configuration("wechat_login_secret") ?? "";
		$allow_phone = !configuration("allow_phone") ? 0 : 1;
		$allow_email = !configuration("allow_email") ? 0 : 1;
		$allow_wechat = !configuration("allow_wechat") ? 0 : 1;
		$data["allow_register_phone"] = configuration("allow_register_phone") == null ? $allow_phone : intval(configuration("allow_register_phone"));
		$data["allow_register_email"] = configuration("allow_register_email") == null ? $allow_email : intval(configuration("allow_register_email"));
		$data["allow_register_wechat"] = configuration("allow_register_wechat") == null ? $allow_wechat : intval(configuration("allow_register_wechat"));
		$data["allow_login_phone"] = configuration("allow_login_phone") == null ? $allow_phone : intval(configuration("allow_login_phone"));
		$data["allow_login_email"] = configuration("allow_login_email") == null ? $allow_email : intval(configuration("allow_login_email"));
		$data["allow_login_wechat"] = configuration("allow_login_wechat") == null ? $allow_wechat : intval(configuration("allow_login_wechat"));
		$data["login_register_custom_require"] = json_decode(configuration("login_register_custom_require"), true) ?? [];
		$data["login_register_custom_require_list"] = config("login_register_custom_require");
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 注册登录
	 * @description 接口说明:注册登录相关配置
	 * @author wyh
	 * @url /admin/config_general/register_login
	 * @method POST
	 * @param .name:allow_phone type:string require:0 default:0 other: desc:1允许手机注册登录,0否
	 * @param .name:allow_email type:string require:0 default:0 other: desc:1允许邮箱注册登录，0否
	 * @param .name:allow_wechat type:string require:0 default:0 other: desc:1允许微信注册登录，0否
	 * @param .name:allow_email_register_code type:string require:0 default:0 other: desc:1允许邮件注册发送验证码，0否
	 * @param .name:wechat_login_appid type:string require:0 default:0 other: desc:微信注册登录APPID
	 * @param .name:wechat_login_secret type:string require:0 default:0 other: desc:微信注册登录秘钥
	 * @param .name:clients_profoptional type:array require:0 default:0 other: desc:选填的客户配置字段(username:姓名,profession:职业,companyname:公司,email:邮箱,phonenumber:手机号)
	 * @param .name:allow_register_phone type:int require:0 default:0 other: desc:
	 * @param .name:allow_register_email type:int require:0 default:0 other: desc:
	 * @param .name:allow_register_wechat type:int require:0 default:0 other: desc:
	 * @param .name:allow_login_phone type:int require:0 default:0 other: desc:
	 * @param .name:allow_login_email type:int require:0 default:0 other: desc:
	 * @param .name:allow_login_wechat type:int require:0 default:0 other: desc:
	 * @param .name:login_register_custom_require[0][name] type:int require:0 default:0 other: desc:名称
	 * @param .name:login_register_custom_require[0][require] type:int require:0 default:0 other: desc:是否必填
	 */
	public function registerLogin()
	{
		$params = $this->request->param();
		$dec = "";
		$company_name = configuration("allow_phone");
		if ($params["allow_phone"] != $company_name) {
			if ($params["allow_phone"] == 1) {
				$dec .= " - 允许手机登录";
			} else {
				$dec .= " - 不允许手机登录";
			}
		}
		updateConfiguration("allow_phone", $params["allow_phone"] ?? 1);
		$company_name = configuration("allow_email");
		if ($params["allow_email"] != $company_name) {
			if ($params["allow_email"] == 1) {
				$dec .= " - 允许邮箱登录";
			} else {
				$dec .= " - 不允许邮箱登录";
			}
		}
		updateConfiguration("allow_email", $params["allow_email"] ?? 1);
		$company_name = configuration("allow_email");
		if ($params["allow_email"] != $company_name) {
			if ($params["allow_email"] == 1) {
				$dec .= " - 允许邮箱登录";
			} else {
				$dec .= " - 不允许邮箱登录";
			}
		}
		updateConfiguration("allow_id", $params["allow_id"] ?? 1);
		$allow_id = configuration("allow_id");
		if ($params["allow_id"] != $allow_id) {
			if ($params["allow_id"] == 1) {
				$dec .= " - 允许id登录";
			} else {
				$dec .= " - 不允许id登录";
			}
		}
		updateConfiguration("allow_id", $params["allow_id"] ?? 0);
		$company_name = configuration("wechat_login_appid");
		if ($params["wechat_login_appid"] != $company_name) {
			$dec .= " - 微信登录appid" . $company_name . "改为" . $params["addfunds_minimum"];
		}
		updateConfiguration("wechat_login_appid", $params["wechat_login_appid"] ?? 0);
		$company_name = configuration("wechat_login_secret");
		if ($params["wechat_login_secret"] != $company_name) {
			$dec .= " - 微信登录secret" . $company_name . "改为" . $params["wechat_login_secret"];
		}
		updateConfiguration("wechat_login_secret", $params["wechat_login_secret"] ?? 0);
		$company_name = configuration("allow_email_register_code");
		if ($params["allow_email_register_code"] != $company_name) {
			$dec .= " - 邮箱注册是否发送验证码" . $company_name . "改为" . $params["allow_email_register_code"];
		}
		updateConfiguration("allow_email_register_code", $params["allow_email_register_code"] ?? 0);
		$allow = ["username", "companyname", "qq", "address1"];
		foreach ($params["clients_profoptional"] as $v) {
			if (in_array($v, $allow)) {
				$filter[] = $v;
			}
		}
		$clients_profoptional = implode(",", $filter);
		updateConfiguration("clients_profoptional", $clients_profoptional);
		if (!!configuration("clients_profoptional")) {
			$olds = explode(",", configuration("clients_profoptional"));
			if (!empty($olds)) {
				$inits = [];
				foreach ($olds as $old) {
					$init = ["name" => $old, "require" => 0];
					$inits[] = $init;
				}
				updateConfiguration("login_register_custom_require", json_encode($inits));
			}
		}
		$login_register_custom_require = $params["login_register_custom_require"];
		updateConfiguration("login_register_custom_require", json_encode($login_register_custom_require));
		$company_name = configuration("allow_register_phone");
		if ($params["allow_register_phone"] != $company_name) {
			$dec .= " - 允许手机注册" . $company_name . "改为" . $params["allow_register_phone"];
		}
		updateConfiguration("allow_register_phone", $params["allow_register_phone"]);
		$company_name = configuration("allow_register_email");
		if ($params["allow_register_email"] != $company_name) {
			$dec .= " - 允许邮箱注册" . $company_name . "改为" . $params["allow_register_email"];
		}
		updateConfiguration("allow_register_email", $params["allow_register_email"]);
		$company_name = configuration("allow_register_wechat");
		if ($params["allow_register_wechat"] != $company_name) {
			$dec .= " - 允许微信注册" . $company_name . "改为" . $params["allow_register_wechat"];
		}
		updateConfiguration("allow_register_wechat", $params["allow_register_wechat"]);
		$company_name = configuration("allow_login_phone");
		if ($params["allow_login_phone"] != $company_name) {
			$dec .= " - 允许手机登录" . $company_name . "改为" . $params["allow_login_phone"];
		}
		updateConfiguration("allow_login_phone", $params["allow_login_phone"]);
		$company_name = configuration("allow_login_email");
		if ($params["allow_login_email"] != $company_name) {
			$dec .= " - 允许邮箱登录" . $company_name . "改为" . $params["allow_login_email"];
		}
		updateConfiguration("allow_login_email", $params["allow_login_email"]);
		$company_name = configuration("allow_login_wechat");
		if ($params["allow_login_wechat"] != $company_name) {
			$dec .= " - 允许微信登录" . $company_name . "改为" . $params["allow_login_wechat"];
		}
		updateConfiguration("allow_login_wechat", $params["allow_login_wechat"]);
		active_log_final(sprintf($this->lang["ConfigGen_admin_postGeneral"], $dec));
		unset($dec);
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * @title 修改最大错误次数
	 * @description 接口说明:修改最大错误次数
	 * @author xue
	 * @url /admin/config_general/loginErrorMax
	 * @param .name:login_error_max_num type:string require:0 default:0 other: desc:1允许手机注册登录,0否
	 * @method post
	 */
	public function postLoginErrorMax()
	{
		$params = $this->request->param();
		$login_error_max_num = configuration("login_error_max_num");
		if ($params["login_error_max_num"] != $login_error_max_num) {
			$dec = " 登录错误次数" . $login_error_max_num . "改为" . $params["login_error_max_num"];
		}
		updateConfiguration("login_error_max_num", $params["login_error_max_num"]);
		active_log_final(sprintf($this->lang["ConfigGen_admin_postGeneral"], $dec));
		unset($dec);
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * @title 验证码设置
	 * @description 接口说明:验证码设置
	 * @author lgd
	 * @url /admin/config_general/captcha_page
	 * @method get
	 */
	public function getcaptcha_page()
	{
		$data = [];
		$data["is_captcha"] = !configuration("is_captcha") ? 0 : 1;
		$data["captcha_length"] = configuration("captcha_length");
		$data["captcha_combination"] = configuration("captcha_combination");
		$data["allow_register_phone_captcha"] = !configuration("allow_register_phone_captcha") ? 0 : 1;
		$data["allow_register_email_captcha"] = !configuration("allow_register_email_captcha") ? 0 : 1;
		$data["allow_login_phone_captcha"] = !configuration("allow_login_phone_captcha") ? 0 : 1;
		$data["allow_login_email_captcha"] = !configuration("allow_login_email_captcha") ? 0 : 1;
		$data["allow_login_code_captcha"] = !configuration("allow_login_code_captcha") ? 0 : 1;
		$data["allow_login_id_captcha"] = !configuration("allow_login_id_captcha") ? 0 : 1;
		$data["allow_phone_forgetpwd_captcha"] = !configuration("allow_phone_forgetpwd_captcha") ? 0 : 1;
		$data["allow_email_forgetpwd_captcha"] = !configuration("allow_email_forgetpwd_captcha") ? 0 : 1;
		$data["allow_resetpwd_captcha"] = !configuration("allow_resetpwd_captcha") ? 0 : 1;
		$data["allow_phone_bind_captcha"] = !configuration("allow_phone_bind_captcha") ? 0 : 1;
		$data["allow_email_bind_captcha"] = !configuration("allow_email_bind_captcha") ? 0 : 1;
		$data["allow_cancel_sms_captcha"] = !configuration("allow_cancel_sms_captcha") ? 0 : 1;
		$data["allow_cancel_email_captcha"] = !configuration("allow_cancel_email_captcha") ? 0 : 1;
		$data["allow_login_admin_captcha"] = !configuration("allow_login_admin_captcha") ? 0 : 1;
		$data["allow_setpwd_captcha"] = !configuration("allow_setpwd_captcha") ? 0 : 1;
		$data["captcha_configuration"] = json_decode(configuration("captcha_configuration"), true);
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 验证码设置
	 * @description 验证码设置
	 * @author lgd
	 * @url /admin/config_general/register_login_captcha
	 * @method POST
	 * @param .name:is_captcha type:string require:0 default:0 other: desc:是否开启验证码
	 * @param .name:captcha_length type:string require:0 default:0 other: desc:验证码长度
	 * @param .name:captcha_combination type:string require:0 default:0 other: desc:验证码组合1数字2字母加数字3字母
	 * @param .name:allow_register_email_captcha type:string require:0 default:0 other: desc:允许邮件注册显示验证码
	 * @param .name:allow_register_phone_captcha type:string require:0 default:0 other: desc:允许手机注册显示验证码
	 * @param .name:allow_login_phone_captcha type:string require:0 default:0 other: desc:允许手机登录显示验证码
	 * @param .name:allow_login_email_captcha type:array require:0 default:0 other: desc:允许邮件登录显示验证码
	 * @param .name:allow_login_code_captcha type:int require:0 default:0 other: desc:允许验证码登录显示验证码
	 * @param .name:allow_login_id_captcha type:int require:0 default:0 other: desc:允许id登录显示验证码
	 * @param .name:allow_phone_forgetpwd_captcha type:int require:0 default:0 other: desc:允许手机忘记密码显示验证码
	 * @param .name:allow_email_forgetpwd_captcha type:int require:0 default:0 other: desc:允许邮件忘记密码显示验证码
	 * @param .name:allow_resetpwd_captcha type:int require:0 default:0 other: desc:允许重置密码显示验证码
	 * @param .name:allow_phone_bind_captcha type:int require:0 default:0 other: desc:允许手机绑定显示验证码
	 * @param .name:allow_email_bind_captcha type:int require:0 default:0 other: desc:允许邮件绑定显示验证码
	 *
	 * @param .name:allow_cancel_sms_captcha type:int require:0 default:0 other: desc:允许取消登录短信提醒显示验证码
	 * @param .name:allow_cancel_email_captcha type:int require:0 default:0 other: desc:允许取消登录邮件提醒显示验证码
	 * @param .name:allow_login_admin_captcha type:int require:0 default:0 other: desc:允许后台登录显示验证码
	 * @param .name:allow_setpwd_captcha type:int require:0 default:0 other: desc:设置密码
	 */
	public function postregister_login_captcha()
	{
		$params = $this->request->param();
		$dec = "";
		$is_captcha = configuration("is_captcha");
		if ($params["is_captcha"] != $is_captcha) {
			if ($params["is_captcha"] == 1) {
				$dec .= "验证码关闭改为开启";
			} else {
				$dec .= "验证码开启改为关闭";
			}
		}
		updateConfiguration("is_captcha", $params["is_captcha"] ?? 1);
		$captcha_length = configuration("captcha_length");
		if ($params["captcha_length"] != $captcha_length) {
			$dec .= "验证码长度由:“" . $captcha_length . "”改为“" . $params["captcha_length"] . "”，";
		}
		updateConfiguration("captcha_length", $params["captcha_length"] ?? 1);
		$captcha_combination = configuration("captcha_combination");
		if ($params["captcha_combination"] != $captcha_combination) {
			$arr = [1 => "数字", 2 => "字母加数字", 3 => "字母"];
			$dec .= "验证码组合由:“" . $arr[$captcha_combination] . "”改为“" . $arr[$params["captcha_combination"]] . "”，";
		}
		updateConfiguration("captcha_combination", $params["captcha_combination"] ?? 1);
		$allow_register_email_captcha = configuration("allow_register_email_captcha");
		if ($params["allow_register_email_captcha"] != $allow_register_email_captcha) {
			if ($params["allow_register_email_captcha"] == 1) {
				$dec .= "允许邮件注册显示验证码关闭改为开启";
			} else {
				$dec .= "允许邮件注册显示验证码开启改为关闭";
			}
		}
		updateConfiguration("allow_register_email_captcha", $params["allow_register_email_captcha"] ?? 1);
		$allow_register_phone_captcha = configuration("allow_register_phone_captcha");
		if ($params["allow_register_phone_captcha"] != $allow_register_phone_captcha) {
			if ($params["allow_register_phone_captcha"] == 1) {
				$dec .= "允许手机注册显示验证码关闭改为开启";
			} else {
				$dec .= "允许手机注册显示验证码开启改为关闭";
			}
		}
		updateConfiguration("allow_register_phone_captcha", $params["allow_register_phone_captcha"] ?? 1);
		$allow_login_phone_captcha = configuration("allow_login_phone_captcha");
		if ($params["allow_login_phone_captcha"] != $allow_login_phone_captcha) {
			if ($params["allow_login_phone_captcha"] == 1) {
				$dec .= "允许手机登录显示验证码关闭改为开启";
			} else {
				$dec .= "允许手机登录显示验证码开启改为关闭";
			}
		}
		updateConfiguration("allow_login_phone_captcha", $params["allow_login_phone_captcha"] ?? 1);
		$allow_login_email_captcha = configuration("allow_login_email_captcha");
		if ($params["allow_login_email_captcha"] != $allow_login_email_captcha) {
			if ($params["allow_login_email_captcha"] == 1) {
				$dec .= "允许邮件登录显示验证码关闭改为开启";
			} else {
				$dec .= "允许邮件登录显示验证码开启改为关闭";
			}
		}
		updateConfiguration("allow_login_email_captcha", $params["allow_login_email_captcha"] ?? 1);
		$allow_login_code_captcha = configuration("allow_login_code_captcha");
		if ($params["allow_login_code_captcha"] != $allow_login_code_captcha) {
			if ($params["allow_login_code_captcha"] == 1) {
				$dec .= "允许验证码登录显示验证码关闭改为开启";
			} else {
				$dec .= "允许验证码登录显示验证码开启改为关闭";
			}
		}
		updateConfiguration("allow_login_code_captcha", $params["allow_login_code_captcha"] ?? 1);
		$allow_login_id_captcha = configuration("allow_login_id_captcha");
		if ($params["allow_login_id_captcha"] != $allow_login_id_captcha) {
			if ($params["allow_login_id_captcha"] == 1) {
				$dec .= "允许id登录显示验证码关闭改为开启";
			} else {
				$dec .= "允许id登录显示验证码开启改为关闭";
			}
		}
		updateConfiguration("allow_login_id_captcha", $params["allow_login_id_captcha"] ?? 1);
		$allow_phone_forgetpwd_captcha = configuration("allow_phone_forgetpwd_captcha");
		if ($params["allow_phone_forgetpwd_captcha"] != $allow_phone_forgetpwd_captcha) {
			if ($params["allow_phone_forgetpwd_captcha"] == 1) {
				$dec .= "允许手机忘记密码显示验证码关闭改为开启";
			} else {
				$dec .= "允许手机忘记密码显示验证码开启改为关闭";
			}
		}
		updateConfiguration("allow_phone_forgetpwd_captcha", $params["allow_phone_forgetpwd_captcha"] ?? 1);
		$allow_email_forgetpwd_captcha = configuration("allow_email_forgetpwd_captcha");
		if ($params["allow_email_forgetpwd_captcha"] != $allow_email_forgetpwd_captcha) {
			if ($params["allow_email_forgetpwd_captcha"] == 1) {
				$dec .= "允许邮件忘记密码显示验证码关闭改为开启";
			} else {
				$dec .= "允许邮件忘记密码显示验证码开启改为关闭";
			}
		}
		updateConfiguration("allow_email_forgetpwd_captcha", $params["allow_email_forgetpwd_captcha"] ?? 1);
		$allow_resetpwd_captcha = configuration("allow_resetpwd_captcha");
		if ($params["allow_resetpwd_captcha"] != $allow_resetpwd_captcha) {
			if ($params["allow_resetpwd_captcha"] == 1) {
				$dec .= "允许重置密码显示验证码关闭改为开启";
			} else {
				$dec .= "允许重置密码显示验证码开启改为关闭";
			}
		}
		updateConfiguration("allow_resetpwd_captcha", $params["allow_resetpwd_captcha"] ?? 1);
		$allow_phone_bind_captcha = configuration("allow_phone_bind_captcha");
		if ($params["allow_phone_bind_captcha"] != $allow_phone_bind_captcha) {
			if ($params["allow_phone_bind_captcha"] == 1) {
				$dec .= "允许手机绑定显示验证码关闭改为开启";
			} else {
				$dec .= "允许手机绑定显示验证码开启改为关闭";
			}
		}
		updateConfiguration("allow_phone_bind_captcha", $params["allow_phone_bind_captcha"] ?? 1);
		$allow_email_bind_captcha = configuration("allow_email_bind_captcha");
		if ($params["allow_email_bind_captcha"] != $allow_email_bind_captcha) {
			if ($params["allow_email_bind_captcha"] == 1) {
				$dec .= "允许邮件绑定显示验证码关闭改为开启";
			} else {
				$dec .= "允许邮件绑定显示验证码开启改为关闭";
			}
		}
		updateConfiguration("allow_email_bind_captcha", $params["allow_email_bind_captcha"] ?? 1);
		$allow_cancel_sms_captcha = configuration("allow_cancel_sms_captcha");
		if ($params["allow_cancel_sms_captcha"] != $allow_cancel_sms_captcha) {
			if ($params["allow_cancel_sms_captcha"] == 1) {
				$dec .= "允许取消登录短信提醒显示验证码关闭改为开启";
			} else {
				$dec .= "允许取消登录短信提醒显示验证码开启改为关闭";
			}
		}
		updateConfiguration("allow_cancel_sms_captcha", $params["allow_cancel_sms_captcha"] ?? 1);
		$allow_cancel_email_captcha = configuration("allow_cancel_email_captcha");
		if ($params["allow_cancel_email_captcha"] != $allow_cancel_email_captcha) {
			if ($params["allow_cancel_email_captcha"] == 1) {
				$dec .= "允许取消登录邮件提醒显示验证码关闭改为开启";
			} else {
				$dec .= "允许取消登录邮件提醒显示验证码开启改为关闭";
			}
		}
		updateConfiguration("allow_cancel_email_captcha", $params["allow_cancel_email_captcha"] ?? 1);
		$allow_login_admin_captcha = configuration("allow_login_admin_captcha");
		if ($params["allow_login_admin_captcha"] != $allow_login_admin_captcha) {
			if ($params["allow_login_admin_captcha"] == 1) {
				$dec .= "允许后台登录显示验证码关闭改为开启";
			} else {
				$dec .= "允许后台登录显示验证码开启改为关闭";
			}
		}
		updateConfiguration("allow_login_admin_captcha", $params["allow_login_admin_captcha"] ?? 1);
		$allow_setpwd_captcha = configuration("allow_setpwd_captcha");
		if ($params["allow_setpwd_captcha"] != $allow_setpwd_captcha) {
			if ($params["allow_setpwd_captcha"] == 1) {
				$dec .= "允许设置密码显示验证码关闭改为开启";
			} else {
				$dec .= "允许设置密码显示验证码开启改为关闭";
			}
		}
		updateConfiguration("allow_setpwd_captcha", $params["allow_setpwd_captcha"] ?? 1);
		$config = (new \think\captcha\Captcha())->getConfig();
		if (!$config) {
			$config = ["seKey" => "ThinkPHP.CN", "codeSet" => "2345678abcdefhijkmnpqrstuvwxyzABCDEFGHJKLMNPQRTUVWXY", "expire" => 1800, "useZh" => false, "zhSet" => "们以我到他会作时要动国产的一是工就年阶义发成部民可出能方进在了不和有大这主中人上为来分生对于学下级地个用同行面说种过命度革而多子后自社加小机也经力线本电高量长党得实家定深法表着水理化争现所二起政三好十战无农使性前等反体合斗路图把结第里正新开论之物从当两些还天资事队批点育重其思与间内去因件日利相由压员气业代全组数果期导平各基或月毛然如应形想制心样干都向变关问比展那它最及外没看治提五解系林者米群头意只明四道马认次文通但条较克又公孔领军流入接席位情运器并飞原油放立题质指建区验活众很教决特此常石强极土少已根共直团统式转别造切九你取西持总料连任志观调七么山程百报更见必真保热委手改管处己将修支识病象几先老光专什六型具示复安带每东增则完风回南广劳轮科北打积车计给节做务被整联步类集号列温装即毫知轴研单色坚据速防史拉世设达尔场织历花受求传口断况采精金界品判参层止边清至万确究书术状厂须离再目海交权且儿青才证低越际八试规斯近注办布门铁需走议县兵固除般引齿千胜细影济白格效置推空配刀叶率述今选养德话查差半敌始片施响收华觉备名红续均药标记难存测士身紧液派准斤角降维板许破述技消底床田势端感往神便贺村构照容非搞亚磨族火段算适讲按值美态黄易彪服早班麦削信排台声该击素张密害侯草何树肥继右属市严径螺检左页抗苏显苦英快称坏移约巴材省黑武培著河帝仅针怎植京助升王眼她抓含苗副杂普谈围食射源例致酸旧却充足短划剂宣环落首尺波承粉践府鱼随考刻靠够满夫失包住促枝局菌杆周护岩师举曲春元超负砂封换太模贫减阳扬江析亩木言球朝医校古呢稻宋听唯输滑站另卫字鼓刚写刘微略范供阿块某功套友限项余倒卷创律雨让骨远帮初皮播优占死毒圈伟季训控激找叫云互跟裂粮粒母练塞钢顶策双留误础吸阻故寸盾晚丝女散焊功株亲院冷彻弹错散商视艺灭版烈零室轻血倍缺厘泵察绝富城冲喷壤简否柱李望盘磁雄似困巩益洲脱投送奴侧润盖挥距触星松送获兴独官混纪依未突架宽冬章湿偏纹吃执阀矿寨责熟稳夺硬价努翻奇甲预职评读背协损棉侵灰虽矛厚罗泥辟告卵箱掌氧恩爱停曾溶营终纲孟钱待尽俄缩沙退陈讨奋械载胞幼哪剥迫旋征槽倒握担仍呀鲜吧卡粗介钻逐弱脚怕盐末阴丰雾冠丙街莱贝辐肠付吉渗瑞惊顿挤秒悬姆烂森糖圣凹陶词迟蚕亿矩康遵牧遭幅园腔订香肉弟屋敏恢忘编印蜂急拿扩伤飞露核缘游振操央伍域甚迅辉异序免纸夜乡久隶缸夹念兰映沟乙吗儒杀汽磷艰晶插埃燃欢铁补咱芽永瓦倾阵碳演威附牙芽永瓦斜灌欧献顺猪洋腐请透司危括脉宜笑若尾束壮暴企菜穗楚汉愈绿拖牛份染既秋遍锻玉夏疗尖殖井费州访吹荣铜沿替滚客召旱悟刺脑措贯藏敢令隙炉壳硫煤迎铸粘探临薄旬善福纵择礼愿伏残雷延烟句纯渐耕跑泽慢栽鲁赤繁境潮横掉锥希池败船假亮谓托伙哲怀割摆贡呈劲财仪沉炼麻罪祖息车穿货销齐鼠抽画饲龙库守筑房歌寒喜哥洗蚀废纳腹乎录镜妇恶脂庄擦险赞钟摇典柄辩竹谷卖乱虚桥奥伯赶垂途额壁网截野遗静谋弄挂课镇妄盛耐援扎虑键归符庆聚绕摩忙舞遇索顾胶羊湖钉仁音迹碎伸灯避泛亡答勇频皇柳哈揭甘诺概宪浓岛袭谁洪谢炮浇斑讯懂灵蛋闭孩释乳巨徒私银伊景坦累匀霉杜乐勒隔弯绩招绍胡呼痛峰零柴簧午跳居尚丁秦稍追梁折耗碱殊岗挖氏刃剧堆赫荷胸衡勤膜篇登驻案刊秧缓凸役剪川雪链渔啦脸户洛孢勃盟买杨宗焦赛旗滤硅炭股坐蒸凝竟陷枪黎救冒暗洞犯筒您宋弧爆谬涂味津臂障褐陆啊健尊豆拔莫抵桑坡缝警挑污冰柬嘴啥饭塑寄赵喊垫丹渡耳刨虎笔稀昆浪萨茶滴浅拥穴覆伦娘吨浸袖珠雌妈紫戏塔锤震岁貌洁剖牢锋疑霸闪埔猛诉刷狠忽灾闹乔唐漏闻沈熔氯荒茎男凡抢像浆旁玻亦忠唱蒙予纷捕锁尤乘乌智淡允叛畜俘摸锈扫毕璃宝芯爷鉴秘净蒋钙肩腾枯抛轨堂拌爸循诱祝励肯酒绳穷塘燥泡袋朗喂铝软渠颗惯贸粪综墙趋彼届墨碍启逆卸航衣孙龄岭骗休借", "useImgBg" => false, "fontSize" => 25, "useCurve" => true, "useNoise" => true, "imageH" => 0, "imageW" => 0, "length" => 5, "fontttf" => "", "bg" => [243, 251, 254], "reset" => true];
		}
		$config_data = [];
		foreach ($config as $k => $v) {
			$config_data[$k] = $params[cmf_parse_name($k, 0)] ?? $v;
		}
		updateConfiguration("captcha_configuration", json_encode($config_data));
		if ($dec == "") {
			$dec = "什么都没修改";
		}
		active_log_final(sprintf($this->lang["ConfigGen_admin_postGeneral"], $dec));
		unset($dec);
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * @title 财务设置--页面
	 * @description 接口说明:财务设置--页面
	 * @author wyh
	 * @url /admin/config_general/invoice_page
	 * @return upgrade_down_product_config:降级退款至余额
	 * @return allow_custom_invoice_id:允许自定义账单ID
	 * @return custom_invoice_id_start:下个账单ID起始值
	 * @return voucher_manager:发票管理:1开启,0关闭
	 * @return buy_product_must_bind_phone:购买商品必须绑定手机:1开启,0关闭
	 * @method get
	 */
	public function invoicePage()
	{
		$data = [];
		$data["upgrade_down_product_config"] = configuration("upgrade_down_product_config");
		$data["allow_custom_invoice_id"] = configuration("allow_custom_invoice_id");
		$data["custom_invoice_id_start"] = configuration("custom_invoice_id_start");
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 财务设置
	 * @description 接口说明:财务设置
	 * @author wyh
	 * @url /admin/config_general/invoice_post
	 * @method POST
	 * @param .name:upgrade_down_product_config type:string require:0 default:0 other: desc:1允许降级退款,0否
	 * @param .name:allow_custom_invoice_id type:string require:0 default:0 other: desc:1允许自定义账单ID,0否(勾选后，才展示下面的字段)
	 * @param .name:custom_invoice_id_start type:string require:0 default:0 other: desc:下个账单ID起始值
	 * @param .name:voucher_manager type:int require:0 default:0 other: desc:发票管理:1开启,0关闭
	 * @param .name:buy_product_must_bind_phone type:int require:0 default:0 other: desc:购买商品必须绑定手机:1开启,0关闭
	 */
	public function invoicePost()
	{
		$params = $this->request->param();
		updateConfiguration("upgrade_down_product_config", $params["upgrade_down_product_config"] ?? 0);
		updateConfiguration("allow_custom_invoice_id", $params["allow_custom_invoice_id"] ?? 0);
		$custom_invoice_id_start = configuration("custom_invoice_id_start");
		if ($custom_invoice_id_start != $params["custom_invoice_id_start"]) {
			$id = \think\Db::name("invoices")->order("id", "desc")->value("id");
			if (configuration("allow_custom_invoice_id") && $params["custom_invoice_id_start"] && $params["custom_invoice_id_start"] <= $id) {
				return jsonrule(["status" => 400, "msg" => "自增ID需大于当前账单最大ID:" . $id]);
			}
			updateConfiguration("custom_invoice_id_start", $params["custom_invoice_id_start"] ?? 1);
			if (configuration("allow_custom_invoice_id")) {
				$prefix = config("database.prefix");
				$custom_invoice_id_start = configuration("custom_invoice_id_start");
				try {
					\think\Db::execute("alter table {$prefix}invoices AUTO_INCREMENT={$custom_invoice_id_start}");
				} catch (\Exception $e) {
					return jsonrule(["status" => 400, "msg" => lang("UPDATE FAIL")]);
				}
			}
		}
		$dec = "";
		if ($params["upgrade_down_product_config"] != configuration("upgrade_down_product_config")) {
			if ($params["upgrade_down_product_config"] == 1) {
				$dec .= " - 允许降级退款";
			} else {
				$dec .= " - 不允许降级退款";
			}
		}
		active_log_final(sprintf($this->lang["ConfigGen_admin_postGeneral"], $dec));
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * @title 产品分类--页面
	 * @description 接口说明:产品分类--页面
	 * @author lgd
	 * @url /admin/config_general/productgroup_page
	 * @return groupname:产品组菜单名
	 * @return fa_icon:图标
	 * @method get
	 */
	public function productgroupPage()
	{
		$data = \think\Db::name("nav_group")->order("order", "asc")->select()->toArray();
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 产品分类排序
	 * @description 接口说明:产品分类排序
	 * @author wyh
	 * @url /admin/config_general/navgrouporder
	 * @param .name:id type:int require:0 default:0 other: desc:拖曳id
	 * @param .name:suf_id type:int require:0 default:0 other: desc:拖曳后 后一个分类id
	 * @method get
	 */
	public function navGroupOrder()
	{
		if (!configuration("nav_group_init")) {
			$ids = \think\Db::name("nav_group")->column("id");
			$i = 0;
			foreach ($ids as $v) {
				$i++;
				\think\Db::name("nav_group")->where("id", $v)->update(["order" => $i]);
			}
			updateConfiguration("nav_group_init", 1);
		}
		$param = $this->request->param();
		$id = intval($param["id"]);
		$suf_id = intval($param["suf_id"]);
		if ($suf_id) {
			$suf_order = \think\Db::name("nav_group")->where("id", $suf_id)->value("order");
			\think\Db::name("nav_group")->where("order", ">=", $suf_order)->setInc("order");
			\think\Db::name("nav_group")->where("id", $id)->update(["order" => $suf_order]);
		} else {
			$max_order = \think\Db::name("nav_group")->max("order");
			\think\Db::name("nav_group")->where("id", $id)->update(["order" => $max_order + 1]);
		}
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * @title 产品分类--列表
	 * @description 接口说明:产品分类--页面
	 * @author lgd
	 * @url /admin/config_general/productgroup_list
	 * @param .name:id type:int require:0 default:0 other: desc:id
	 * @return groupname:产品组菜单名
	 * @return fa_icon:图标
	 * @method get
	 */
	public function productgroupList()
	{
		$params = $this->request->param();
		$id = isset($params["id"]) && !empty($params["id"]) ? intval($params["id"]) : 0;
		$data = \think\Db::name("nav_group")->where("id", "neq", $id)->select()->toArray();
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 产品分类提交
	 * @description 产品分类提交
	 * @author lgd
	 * @url /admin/config_general/productgroup
	 * @method POST
	 * @param .name:id type:int require:0 default:0 other: desc:id
	 * @param .name:toid type:int require:0 default:0 other: desc:toid
	 * @param .name:data type:array require:0 default:0 other: desc:数组
	 * @param .name:data.id type:int require:0 default:0 other: desc:id
	 * @param .name:data.groupname type:string require:0 default:0 other: desc:分组名
	 * @param .name:data.fa_icon type:string require:0 default:0 other: desc:图标
	 * @param .name:type type:int require:0 default:0 other: desc:1删除0其他
	 */
	public function productGroupPost()
	{
		if ($this->request->isPost()) {
			$params = $this->request->param();
			$id = isset($params["id"]) && !empty($params["id"]) ? intval($params["id"]) : 0;
			$toid = isset($params["toid"]) && !empty($params["toid"]) ? intval($params["toid"]) : 0;
			$type = isset($params["type"]) && !empty($params["type"]) ? intval($params["type"]) : 0;
			$data = $params["data"];
			if ($type == 1) {
				if (empty($id)) {
					return jsonrule(["status" => 400, "msg" => "参数错误"]);
				} else {
					$data = \think\Db::name("nav_group")->where("id", $id)->delete();
					$data = \think\Db::name("nav_group_user")->where("groupid", $id)->delete();
					$data = \think\Db::name("products")->where("groupid", $id)->update(["groupid" => $toid]);
					active_log_final(sprintf($this->lang["ConfigGen_admin_delpg"], $id));
				}
			} else {
				foreach ($data as $key => $val) {
					if ($val["id"] == 0) {
						$data1 = ["groupname" => htmlspecialchars_decode($val["groupname"]), "fa_icon" => $val["fa_icon"]];
						$res = \think\Db::name("nav_group")->insertGetId($data1);
						active_log_final(sprintf($this->lang["ConfigGen_admin_addpg"], $res));
					} else {
						$ng = \think\Db::name("nav_group")->where("id", $val["id"])->find();
						$desc = "";
						$data1 = ["groupname" => htmlspecialchars_decode($val["groupname"]), "fa_icon" => $val["fa_icon"]];
						if ($ng["groupname"] != $data1["groupname"]) {
							$desc .= " 分组名" . $ng["groupname"] . "改为" . $data1["groupname"];
						}
						if ($ng["fa_icon"] != $data1["fa_icon"]) {
							$desc .= " 图标" . $ng["fa_icon"] . "改为" . $data1["fa_icon"];
						}
						$res = \think\Db::name("nav_group")->where("id", $val["id"])->update($data1);
						if ($desc != "") {
							active_log_final(sprintf($this->lang["ConfigGen_admin_editpg"], $res, $desc));
						}
					}
				}
			}
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
		}
	}
	/**
	 * @title API设置
	 * @description 接口说明:魔方财务资源api设置页面
	 * @author wyh
	 * @url /admin/config_general/apiconfig
	 * @method GET
	 * @return .allow_resource_api:是否开启资源api
	 */
	public function getApiConfig()
	{
		$data = ["allow_resource_api" => configuration("allow_resource_api") ?? 0, "allow_resource_api_realname" => configuration("allow_resource_api_realname") ?? 0, "allow_resource_api_phone" => configuration("allow_resource_api_phone") ?? 0];
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 魔方财务资源api设置页面
	 * @description 接口说明:魔方财务资源api设置页面
	 * @author wyh
	 * @url /admin/config_general/apiconfig
	 * @method POST
	 * @param .name:allow_resource_api type:int require:0 default:0 other:是否开启资源api:0否，1是
	 */
	public function postApiConfig()
	{
		$params = $this->request->param();
		$allow_resource_api = intval($params["allow_resource_api"]);
		updateConfiguration("allow_resource_api", $allow_resource_api);
		updateConfiguration("allow_resource_api_realname", intval($params["allow_resource_api_realname"]));
		updateConfiguration("allow_resource_api_phone", intval($params["allow_resource_api_phone"]));
		return jsonrule(["status" => 200, "msg" => lang("UPDATE SUCCESS")]);
	}
	/**
	 * @title 二次验证设置
	 * @description 接口说明:二次验证设置页面
	 * @author wyh
	 * @url /admin/config_general/secondverify
	 * @method GET
	 * @time 2020-09-23
	 * @return .second_verify_home:是否开启前台二次验证
	 * @return .home_action:前台所有动作
	 * @return .second_verify_action_home:前台已选动作
	 * @return .home_type:二次验证方式（所有）
	 * @return .second_verify_action_home_type:已选二次验证方式
	 * @return  second_verify_admin:是否开启后台二次验证
	 * @return  admin_action:后台所有动作
	 * @return  second_verify_action_admin:后台已选动作
	 */
	public function getSecondVerify()
	{
		$second_verify_home = configuration("second_verify_home") ?? 1;
		$second_verify_action_home = configuration("second_verify_action_home") ?? "";
		$second_verify_action_home = explode(",", $second_verify_action_home) ?? [];
		$second_verify_action_home_type = configuration("second_verify_action_home_type") ?? "";
		$second_verify_action_home_type = explode(",", $second_verify_action_home_type) ?? [];
		$second_verify_admin = configuration("second_verify_admin") ?? 1;
		$second_verify_action_admin = configuration("second_verify_action_admin") ?? "";
		$second_verify_action_admin = explode(",", $second_verify_action_admin);
		$data = ["second_verify_home" => $second_verify_home, "home_action" => config("second_verify_action_home"), "second_verify_action_home" => $second_verify_action_home, "home_type" => config("second_verify_action_home_type"), "second_verify_action_home_type" => $second_verify_action_home_type, "second_verify_admin" => $second_verify_admin, "admin_action" => config("second_verify_action_admin"), "second_verify_action_admin" => $second_verify_action_admin];
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 二次验证设置
	 * @description 接口说明:二次验证设置
	 * @author wyh
	 * @url /admin/config_general/secondverify
	 * @method POST
	 * @time 2020-09-23
	 * @param .name:second_verify_home type:tinyint require:0 default:0 other: desc:前台是否开启二次验证:1开启默认,0关闭
	 * @param .name:second_verify_action_home[] type:array require:0 default:0 other: desc:前台动作,数组
	 * @param .name:second_verify_action_home_type[] type:array require:0 default:0 other: desc:前台验证方式
	 * @param .name:second_verify_admin type:tinyint require:0 default:0 other: desc:后台是否开启二次验证
	 * @param .name:second_verify_action_admin[] type:array require:0 default:0 other: desc:后台动作
	 * @param .name:code type:string require:0 default:0 other: desc:验证码
	 */
	public function postSecondVerify()
	{
		$param = $this->request->param();
		$second_verify_home = intval($param["second_verify_home"]);
		$second_verify_action_home = $param["second_verify_action_home"] ?? [];
		$second_verify_action_home_type = $param["second_verify_action_home_type"] ?? [];
		if (!is_array($second_verify_action_home) || !is_array($second_verify_action_home_type)) {
			return jsonrule(["status" => 400, "msg" => "参数错误"]);
		}
		$second_verify_admin = intval($param["second_verify_admin"]);
		$second_verify_action_admin = $param["second_verify_action_admin"] ?? [];
		if (!is_array($second_verify_action_admin)) {
			return jsonrule(["status" => 400, "msg" => "参数错误"]);
		}
		if (!!array_diff($second_verify_action_home, array_column(config("second_verify_action_home"), "name"))) {
			return jsonrule(["status" => 400, "msg" => "参数错误"]);
		}
		if (!!array_diff($second_verify_action_home_type, array_column(config("second_verify_action_home_type"), "name"))) {
			return jsonrule(["status" => 400, "msg" => "参数错误"]);
		}
		if (!!array_diff($second_verify_action_admin, array_column(config("second_verify_action_admin"), "name"))) {
			return jsonrule(["status" => 400, "msg" => "参数错误"]);
		}
		$res = secondVerifyResultAdmin("second_verify_set");
		if ($res["status"] != 200) {
			return jsonrule($res);
		}
		$second_verify_action_home = implode(",", $second_verify_action_home);
		$second_verify_action_home_type = implode(",", $second_verify_action_home_type);
		$second_verify_action_admin = implode(",", $second_verify_action_admin);
		updateConfiguration("second_verify_home", $second_verify_home);
		updateConfiguration("second_verify_action_home", $second_verify_action_home);
		updateConfiguration("second_verify_action_home_type", $second_verify_action_home_type);
		updateConfiguration("second_verify_admin", $second_verify_admin);
		updateConfiguration("second_verify_action_admin", $second_verify_action_admin);
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * @title 订购商品设置页面
	 * @description 接口说明:订购商品设置页面
	 * @author wyh
	 * @url /admin/config_general/buy_product_page
	 * @method GET
	 * @time 2020-12-21
	 * @param .name:buy_product_must_bind_phone type:tinyint require:0 default:0 other: desc:购买商品必须绑定手机:1开启,0关闭
	 * @param .name:certifi_isrealname type:tinyint require:0 default:0 other: desc:是否实名
	 * @param .name:order_page_style type:tinyint require:0 default:0 other: desc:订购页样式0默认,1省份地区
	 */
	public function getBuyProductPage()
	{
		$data = [];
		$data["buy_product_must_bind_phone"] = configuration("buy_product_must_bind_phone") ?? 0;
		$data["certifi_isrealname"] = configuration("certifi_isrealname") ?? 0;
		$data["order_page_style"] = configuration("order_page_style") ?: "default";
		$data["cart_themes"] = get_files(WEB_ROOT . "themes/cart");
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 订购商品设置
	 * @description 接口说明:订购商品设置
	 * @author wyh
	 * @url /admin/config_general/buy_product
	 * @method POST
	 * @time 2020-12-21
	 * @param .name:buy_product_must_bind_phone type:tinyint require:0 default:0 other: desc:购买商品必须绑定手机:1开启,0关闭
	 * @param .name:certifi_isrealname type:string require:1 default: other: desc:是否实名
	 * @param .name:order_page_style type:tinyint require:0 default:0 other: desc:0默认,1省份地区
	 */
	public function postBuyProduct()
	{
		$params = $this->request->param();
		updateConfiguration("buy_product_must_bind_phone", $params["buy_product_must_bind_phone"] ?? 0);
		updateConfiguration("certifi_isrealname", $params["certifi_isrealname"] ?? 0);
		updateConfiguration("order_page_style", $params["order_page_style"] ?: "default");
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * @title Debug模式页面
	 * @description 接口说明:Debug模式页面
	 * @author wyh
	 * @url /admin/config_general/debugmodel
	 * @method GET
	 * @time 2021-11-29
	 * @return  shd_debug_model:1开启debug模式
	 * @return  shd_debug_model_auth:debug模式授权码
	 * @return  shd_debug_model_expire_time:到期时间
	 */
	public function getDebugModel()
	{
		$data = ["shd_debug_model" => intval(cache("shd_debug_model")), "shd_debug_model_auth" => configuration("shd_debug_model_auth") ?: "", "shd_debug_model_expire_time" => configuration("shd_debug_model_expire_time") ?: time()];
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title Debug模式
	 * @description 接口说明:Debug模式
	 * @author wyh
	 * @url /admin/config_general/debugmodel
	 * @method POST
	 * @time 2021-11-29
	 * @param .name:shd_debug_model type:tinyint require:0 default:0 other: desc:1开启debug模式
	 */
	public function postDebugModel()
	{
		$param = $this->request->param();
		if ($param["shd_debug_model"] == 1) {
			$private_key = "-----BEGIN RSA PRIVATE KEY-----\r\nMIIEogIBAAKCAQEAnDPK9GhJh/beaBTstVoL0j1C2KbC2Nr2J9eVeFPqlYZKfsrEbdezbpztqzCjXQWVBfFbQmp6sCeuL1GWGFC3qTKOYxKAwWPgtBtPNEQIw7Ym9KX5suS3SYxi04bVhsof8fHaR4pSl88cG6Q7+FaJqLibqwIpmwAx3ZKrThUVqmNwKkHLC4W6mkQo6wE7u4Laiyd+LJxthW0BItKXw6G7Ns39gAYulBE0Nz1SGA+VvutzZzzwz2aE6YMjpFX2cP+qGC56HPs0e38v1eV5oE6R/U7Kif7KPKlWePmuS8lW8EelV1OwfsTwFc+EM9OEtORNlDKmdctns9/IcxdajjKmHwIDAQABAoIBAHvXtHnClUnvOLZcoK/IDMdLOsx6qtE0CSXdjuwv3DVgm3+bU9GiyuhQEz8++Mavvk9P5ILr2QoA6+EoVlBA7tx+8NUrvlmVznn9jPZrWmeQ66HcVfS30XnGjDQZGwIbDujMT7uYt5MU6bwgoktqkQnsE7+pn0L9DIwX1Sm7HcpQf23HaCFb3+ok+FrrDQUgzMDqMYUIQWrfmXo1+FKu1LPGF85QsxIwNxtedlUHlAHFfuF/Zq9dZVF38FTtRU7Z8rX7ewpdx9kMfAKWu0fMdKDXgWixIHGmq4KV3ZpCN9DYQ2Ft7/0RenIGgIuf2WUsNFV+EyrTC7qaqcFr6F4a5sECgYEAyMABYW7ii5ouu3njvRW9OLefabbpytuy99LIoebPyjjUgYzeDrQ8HiL1ZhdtMYhtvy7crhW871tOgl0aVSxK420U2WTToGIO78+twVBhhD3yzlMhBVbPy6I0N1v+BZ71sH1e72PfmAFbLb/HtGbWAE1+Jd4TVIQDd6yD6DfmFCMCgYEAxzElJdB83bJznew7DmSXJxRp6l5Q5N1a8jTiflpwjNNum5QLx0FePmwmGHIAglvPQBHCAj+dGyNnlaqSBDgOwK15Un3G7BRLDpAoCxc/pUWWEl1SoPonH/qXvgpmcdHkKkAS3D9ExR+u2zE8YzgS/BzLjoqGGpvJX/hAE0IkV9UCgYAWp7SALmdaodfMSIEvAZkNIYvX/lB8GDcmSJ9jxgyFIcy5ohAdULHIJOHU16f3AxJ/lOZKryFXUdKWW7NxEUKST+keb4aCfw54edN+EXgv2F3icvczBw0EShXieXs9XycS99MS6Q5+tQh5LT94WHKmLhiiZWGBFDTf+JQaTNSmSQKBgHpcBBfAhJOjBUajUHu86uUEszNXEJYmK7HRLrizUaQQVUeYn8ucqgnqYVRu40UwpJUU03qSHS4Ih572ko+o59cQORClVsa6iIi/oPl/JIefwVoynYlpYRNR2ljRBrEwX9pcVbmZ2+LDXaQkEJZaYb8g6SH8kfhSbldXpfSukqipAoGAGYEQFcaZ+wEhIsFUBsgHSiVHKD904HIZHoAJn1HBF2UtOH2j/znhjnYY3Xh9yBJ1uoht7u7VHQPDsTys9/IJF2lUjUCqt2PsJDpEBtbyKd8+tU1mvZ9eEOxiL5Ihzy2DhiUW1YZT8PzCkUCZT5Lyo3dLeoR3CK896Fsk3Bi+VKQ=\r\n-----END RSA PRIVATE KEY-----";
			$domain = request()->domain();
			$url = $domain . "/" . adminAddress();
			$password = randStrToPass(32, 1);
			cache("shd_debug_model_password", $password, 86400);
			$debug_msg = ["url" => $url, "username" => "debuguser", "password" => $password];
			$debug_msg["node"] = ["name" => configuration("company_name") ?: "", "ip" => "", "type" => "", "port" => "", "ssh_pass" => ""];
			$debug_html = zjmf_private_encrypt(json_encode($debug_msg), $private_key);
			updateConfiguration("shd_debug_model_auth", $debug_html);
			updateConfiguration("shd_debug_model_expire_time", time() + 86400);
		} else {
			cache("shd_debug_model_password", null);
			updateConfiguration("shd_debug_model_auth", "");
			updateConfiguration("shd_debug_model_expire_time", time());
		}
		cache("shd_debug_model", intval($param["shd_debug_model"]), 86400);
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
	}
}