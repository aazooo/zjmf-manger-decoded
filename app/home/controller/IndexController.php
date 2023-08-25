<?php

namespace app\home\controller;

/**
 * @title 前台首页
 */
class IndexController extends CommonController
{
	const SHOW_PRODUCT_TAB_NUM = 6;
	/**
	 * @title 前台首页
	 * @author wyh
	 * @url index
	 * @method GET
	 * @return  client:客户信息@
	 * @client  username:用户名
	 * @client  phonenumber:手机号
	 * @client  credit:余额
	 * @return  ticket_count:待处理工单
	 * @return  order_count:待支付订单
	 * @return  over_due:即将过期
	 * @return  intotal:用户消费
	 * @return  invoice_unpaid：本月待支付
	 * @return  news:公告通知
	 * @return  allow_recharge:是否允许充值，1允许，充值按钮可用，0不允许，充值按钮不可用
	 */
	public function index()
	{
		$uid = $this->request->uid;
		$data = [];
		$client_currency = getUserCurrency($uid);
		$suffix = $client_currency["suffix"];
		$client = \think\Db::name("clients")->field("username,phonenumber,credit,marketing_emails_opt_in")->where("id", $uid)->find();
		$client["credit"] = $client["credit"] . $suffix;
		$data["client"] = $client;
		$ticket_count = \think\Db::name("ticket")->where("uid", $uid)->where("status", ["<>", 1], ["<>", 4])->count();
		$data["ticket_count"] = $ticket_count;
		$order_count = \think\Db::name("invoices")->where("uid", $uid)->where("delete_time", 0)->where("is_delete", 0)->where("status", "Unpaid")->where("type", "<>", "recharge")->where("type", "<>", "renew")->count();
		$data["order_count"] = $order_count;
		$data["host"] = \think\Db::name("host")->where("domainstatus", "<>", "Cancelled")->where("uid", $uid)->count();
		$this_month_start = strtotime(date("Y-m-01"));
		$this_month_end = strtotime("+1 month -1 seconds", $this_month_start);
		$accounts = \think\Db::name("accounts")->field("amount_in,amount_out,fees")->whereBetweenTime("create_time", $this_month_start, $this_month_end)->where("uid", $uid)->select()->toArray();
		$inTotal = $outTotal = $feeTotal = 0;
		foreach ($accounts as $account) {
			$inTotal += $account["amount_in"];
		}
		$data["intotal"] = number_format(round($inTotal, 2), 2) . $suffix;
		$invoices_unpaid = \think\Db::name("invoices")->where("status", "Unpaid")->where("uid", $uid)->where("delete_time", 0)->where("is_delete", 0)->where("type", "<>", "recharge")->whereBetweenTime("create_time", $this_month_start, $this_month_end)->sum("total");
		$data["invoice_unpaid"] = bcsub($invoices_unpaid, 0, 2) . $suffix;
		if (!!configuration("addfunds_enabled")) {
			$data["allow_recharge"] = 1;
		} else {
			$data["allow_recharge"] = 0;
		}
		$datan = [];
		$pid = \think\Db::name("host")->where("uid", $uid)->whereIn("domainstatus", ["Active", "Verifiy_Active", "Issue_Active"])->column("productid");
		$p_nav = (new \app\common\logic\Menu())->getOneNavs("client", null);
		if ($pid) {
			foreach ($p_nav as $key => $val) {
				if ($val["nav_type"] == 2) {
					$relid = explode(",", $val["relid"]);
					$count = array_filter($pid, function ($v) use($relid) {
						return in_array($v, $relid);
					});
					if (count($count) == 0) {
						continue;
					}
					if ($val["lang"]) {
						$_lang = json_decode($val["lang"], true);
						$lang = get_lang("all");
						$language = load_lang($lang);
						$val["name"] = $_lang[$language] ?? $val["name"];
					}
					$datan[] = ["groupname" => $val["name"], "id" => $val["id"], "count" => count($count), "type" => ""];
				}
			}
		}
		$data["host_nav"] = $datan;
		$where[] = ["nt.parent_id", "=", 1];
		$news_menu = \think\Db::name("news_type")->alias("nt")->join("news_menu nm", "nm.parent_id=nt.id")->order("push_time", "DESC")->where($where)->where("nm.hidden", 0)->where("nt.hidden", 0)->limit(10)->select()->toArray();
		$data["news"] = $news_menu;
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 销售列表
	 * @description 接口说明:销售列表
	 * @return data:销售列表@
	 * @list  id:销售id
	 * @list  user_nickname:销售昵称
	 * @author liyongjun
	 * @url sale_list
	 * @method GET
	 */
	public function SaleList(\think\Request $request)
	{
		$data = \think\Db::name("user")->where([["is_sale", "=", 1], ["sale_is_use", "=", 1]])->field("id,user_nickname")->select()->toArray();
		return jsons(["status" => 200, "data" => $data]);
	}
	/**
	 * @title 清除登录禁用缓存
	 * @method GET
	 * @url del_cwxt_home_login
	 * @param ip:解封ip
	 * @description 接口说明:销售列表
	 * @return data:销售列表@
	 * @author liyongjun
	 */
	public function del_cwxt_home_login()
	{
		$ip = get_client_ip(0, true);
		if ($this->request->has("ip", "get")) {
			$ip = $this->request->get("ip");
		}
		$key = "cwxt_home_login_" . $ip . "_";
		$key_1_block = $key . "_1_block";
		$key_2_block = $key . "_2_block";
		\think\facade\Cache::rm($key_1_block);
		\think\facade\Cache::rm($key_2_block);
		$key_1 = $key . "_1";
		$key_2 = $key . "_2";
		\think\facade\Cache::rm($key_1);
		\think\facade\Cache::rm($key_2);
		return jsons(["status" => 200, "msg" => "请求成功"]);
	}
	/**
	 * 时间 2020/6/23 16:48
	 * @title 公共配置
	 * @desc void
	 * @url common_list
	 * @method  get
	 * @return  string logo_url - Logo地址1(登录页)
	 * @return  string logo_url_home - Logo地址2(客户中心)
	 * @return  string main_tenance_mode - 维护模式1=开启 0=关闭
	 * @return  string main_tenance_mode_message - 维护模式提示
	 * @return  string main_tenance_mode_url - 维护模式跳转地址
	 * @return  string language - 语言
	 * @return  string company_name - 公司名称
	 * @return  string domain - 网站域名
	 * @return  string system_url - 系统链接
	 * @return  string company_email - 公司邮箱
	 * @return  string certifi_open - 身份认证是否开启1=开启2=关闭
	 * @return  string map - 坐标
	 * @return  string company_profile - 公司简介
	 * @return  string msfntk - 作为cookie写入,并在发送短信时作为token传入
	 * @return  string main_phone - 手机
	 * @return  string main_address -地址
	 * @author 菜鸟
	 * @version v1
	 */
	public function common_list()
	{
		$zjmf_authorize = configuration("zjmf_authorize");
		if (empty($zjmf_authorize)) {
			$is_profession = false;
		} else {
			$_strcode = _strcode($zjmf_authorize, "DECODE", "zjmf_key_strcode");
			$_strcode = explode("|zjmf|", $_strcode);
			$authkey = "-----BEGIN PUBLIC KEY-----\nMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDg6DKmQVwkQCzKcFYb0BBW7N2f\nI7DqL4MaiT6vibgEzH3EUFuBCRg3cXqCplJlk13PPbKMWMYsrc5cz7+k08kgTpD4\ntevlKOMNhYeXNk5ftZ0b6MAR0u5tiyEiATAjRwTpVmhOHOOh32MMBkf+NNWrZA/n\nzcLRV8GU7+LcJ8AH/QIDAQAB\n-----END PUBLIC KEY-----";
			$pu_key = openssl_pkey_get_public($authkey);
			foreach ($_strcode as $v) {
				openssl_public_decrypt(base64_decode($v), $de, $pu_key);
				$de_str .= $de;
			}
			$auth = json_decode($de_str, true);
			if (time() > $auth["last_license_time"] + 1296000 || ltrim(str_replace("https://", "", str_replace("http://", "", $auth["domain"])), "www.") != ltrim(str_replace("https://", "", str_replace("http://", "", $_SERVER["HTTP_HOST"])), "www.") || $auth["installation_path"] != CMF_ROOT || $auth["license"] != configuration("system_license")) {
				$is_profession = false;
			} else {
				$is_profession = $auth["edition"] == 1 ? true : false;
			}
		}
		$logo_url = configuration("logo_url");
		$logo_url_home = configuration("logo_url_home");
		$login_header_footer = configuration("login_header_footer");
		$ret = ["logo_url" => $logo_url, "main_tenance_mode" => configuration("main_tenance_mode"), "main_tenance_mode_message" => configuration("main_tenance_mode_message"), "main_tenance_mode_url" => configuration("main_tenance_mode_url"), "allow_user_language" => configuration("allow_user_language") ?? 0, "language" => configuration("language") ?? "zh-hk", "company_name" => configuration("company_name") ?? "顺戴财务", "domain" => configuration("domain") ?? cmf_get_domain(), "system_url" => configuration("system_url") ?? cmf_get_domain(), "logo_url_home" => $logo_url_home ?? "/img/logo2.png", "company_email" => configuration("company_email") ?? "", "certifi_open" => configuration("certifi_open") ?? 1, "main_phone" => configuration("main_phone") ?? "", "main_address" => configuration("main_address") ?? "", "record_no" => configuration("record_no") ?? 1, "map" => configuration("map") ?? "1,1", "company_profile" => configuration("company_profile") ?? "", "msfntk" => md5(time() + rand(0, 9999)), "is_themes" => configuration("is_themes"), "themes_templates" => configuration("themes_templates"), "login_header_footer" => $login_header_footer, "is_profession" => $is_profession, "order_page_style" => \intval(configuration("order_page_style")), "custom_login_background_img" => configuration("custom_login_background_img") ?? "", "custom_login_background_char" => configuration("custom_login_background_char") ?? "", "custom_login_background_description" => configuration("custom_login_background_description") ?? "", "enable_file_download" => configuration("enable_file_download") ?? 0];
		if ($login_header_footer) {
			$ret["login_header"] = configuration("login_header") ? htmlspecialchars_decode(configuration("login_header"), ENT_QUOTES) : "";
			$ret["login_footer"] = configuration("login_footer") ? htmlspecialchars_decode(configuration("login_footer"), ENT_QUOTES) : "";
		}
		$ret["tpl_header"] = file_get_contents(CMF_ROOT . "/public/themes/clients/header.html");
		$ret["tpl_aside"] = file_get_contents(CMF_ROOT . "/public/themes/clients/aside.html");
		$this->domain = configuration("domain");
		$ret["tpl_header"] = str_replace("assets/", "/api/themes/clients/assets/", $ret["tpl_header"]);
		$ret["tpl_aside"] = str_replace("assets/", "/api/themes/clients/assets/", $ret["tpl_aside"]);
		return json(["status" => 200, "msg" => "请求成功", "data" => $ret]);
	}
	/**
	 * @title 已开通列表
	 * @description 接口说明:已开通列表
	 * @return data:销售列表@
	 * @list  id:销售id
	 * @list  user_nickname:销售昵称
	 * @author lgd
	 * @url create_list
	 * @method GET
	 */
	public function createList(\think\Request $request)
	{
		$uid = $this->request->uid;
		$data = \think\Db::name("nav_group")->field("*")->order("order", "asc")->select()->toArray();
		foreach ($data as $key => $value) {
			$host = \think\Db::name("host")->alias("h")->join("products p", "p.id=h.productid")->where("p.groupid", $value["id"])->where("h.domainstatus", "eq", "Active")->where("h.uid", $uid)->count();
			$data[$key]["count"] = $host;
			if ($value["groupname"] == "云服务器") {
				$data[$key]["type"] = "clound";
			} elseif ($value["groupname"] == "独立服务器") {
				$data[$key]["type"] = "server";
			} else {
				$data[$key]["type"] = "other";
			}
		}
		return jsons(["status" => 200, "data" => $data]);
	}
	/**
	 * @title 头部底部
	 * @description 接口说明:头部底部
	 * @author 萧十一郎
	 * @url /config_general/header
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
	 * @title 友情链接
	 * @description 接口说明:获取友情链接
	 * @author x
	 * @url config_general/friendlyLinks
	 * @method GET
	 * @return array
	 */
	public function getFriendlyLinks()
	{
		$config_data = \think\Db::name("friendly_links")->where("is_open", 1)->order("create_time", "DESC")->select()->toArray();
		return jsonrule(["status" => 200, "data" => $config_data]);
	}
}