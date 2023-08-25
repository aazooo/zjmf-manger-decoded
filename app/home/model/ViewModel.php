<?php

namespace app\home\model;

/**
 * @title 新闻
 * @description 新闻
 */
class ViewModel extends \think\Model
{
	public function __construct()
	{
		$this->upload_url = CMF_ROOT . "/public/upload/news/";
		$this->domain = configuration("domain");
	}
	public function setting()
	{
		$directory = VIEW_TEMPLATE_DIRECTORY !== "VIEW_TEMPLATE_DIRECTORY" ? VIEW_TEMPLATE_DIRECTORY : "";
		$themes_templates = configuration(VIEW_TEMPLATE_SETTING_NAME);
		$themes_templates = !empty($themes_templates) ? $themes_templates : VIEW_TEMPLATE_DEFAULT;
		$view_path = CMF_ROOT . "public/themes/" . $directory . "/" . $themes_templates . "/";
		$yaml = view_tpl_yaml($view_path);
		if (count($yaml) > 0 && !empty($yaml["config-parent-theme"])) {
			$themes_templates = $yaml["config-parent-theme"];
		}
		$uid = !empty(request()->uid) ? request()->uid : "";
		$unread_count = [];
		$unread_count_num = 0;
		$system_message_type = [1 => "work_order_message", 2 => "product_news", 3 => "on_site_news", 4 => "event_news"];
		foreach ($system_message_type as $key => $type_item) {
			$temp_message["id"] = $key;
			$temp_message["name"] = $type_item;
			$temp_message["unread_num"] = \think\Db::name("system_message")->where("delete_time", 0)->where("read_time", 0)->where("type", $key)->where("uid", $uid)->count();
			$unread_count_num += $temp_message["unread_num"];
			$unread_count[] = $temp_message;
		}
		$setting["company_record"] = configuration("record_no");
		$setting["company_email"] = htmlspecialchars_decode(htmlspecialchars_decode(configuration("company_email")));
		$setting["company_profile"] = htmlspecialchars_decode(htmlspecialchars_decode(configuration("company_profile")));
		$setting["company_address"] = htmlspecialchars_decode(htmlspecialchars_decode(configuration("main_address")));
		$setting["company_phone"] = htmlspecialchars_decode(htmlspecialchars_decode(configuration("main_phone")));
		$setting["company_name"] = htmlspecialchars_decode(htmlspecialchars_decode(configuration("company_name")));
		$setting["company_qq"] = htmlspecialchars_decode(htmlspecialchars_decode(configuration("company_qq")));
		$setting["map"] = configuration("map");
		$setting["allow_user_language"] = configuration("allow_user_language");
		if (configuration("login_header_footer")) {
			$setting["login_header"] = htmlspecialchars_decode(htmlspecialchars_decode(configuration("login_header")));
			$setting["login_footer"] = htmlspecialchars_decode(htmlspecialchars_decode(configuration("login_footer")));
		} else {
			$setting["login_header"] = "";
			$setting["login_footer"] = "";
		}
		$setting["web_logo"] = configuration("logo_url");
		$setting["web_logo_home"] = configuration("logo_url_home");
		$setting["logo_url_home_mini"] = configuration("logo_url_home_mini");
		$setting["logo_url_bill"] = configuration("logo_url_bill");
		$setting["web_www_logo"] = configuration("www_logo");
		$setting["web_seo_keywords"] = configuration("seo_keywords");
		$setting["web_seo_desc"] = configuration("seo_desc");
		$setting["web_tos_url"] = configuration("server_clause_url");
		$setting["web_privacy_url"] = configuration("privacy_clause_url");
		$setting["cart_product_description"] = configuration("cart_product_description");
		$setting["web_close_mode_message"] = configuration("main_tenance_mode") ? configuration("main_tenance_mode_message") : "";
		$setting["web_url"] = $this->domain;
		$setting["system_url"] = $this->domain;
		$setting["web_jump_url"] = configuration("system_url");
		$setting["templates"] = $themes_templates;
		$setting["web_view"] = $this->domain . "/themes/" . $directory . "/" . $themes_templates;
		$setting["custom_login_background_img"] = configuration("custom_login_background_img") ?? "";
		$setting["custom_login_background_char"] = configuration("custom_login_background_char") ?? "";
		$setting["custom_login_background_description"] = configuration("custom_login_background_description") ?? "";
		$setting["msfntk"] = md5(time() + rand(0, 9999));
		$setting["unread_nav"] = $unread_count;
		$setting["unread_num"] = $unread_count_num;
		$setting["certifi_open"] = configuration("certifi_open");
		$setting = array_merge($setting, $this->businessUpload());
		return $setting;
	}
	protected function businessUpload()
	{
		$conf = configuration(["certifi_open", "certifi_is_upload", "certifi_business_open", "certifi_business_is_upload", "certifi_business_is_author", "certifi_business_author_path"]);
		$conf["certifi_is_upload"] = $conf["certifi_is_upload"] == 2 ? 0 : 1;
		$conf["certifi_open"] = $conf["certifi_open"] == 2 ? 0 : 1;
		if (!getEdition()) {
			$conf["certifi_business_open"] = 0;
		}
		if (!$conf["certifi_open"]) {
			$conf["certifi_business_open"] = 0;
		}
		$data = ["certifi_is_upload" => $conf["certifi_open"] ? $conf["certifi_is_upload"] : 0, "certifi_business_is_author" => $conf["certifi_business_open"] ? $conf["certifi_business_is_author"] : 0, "certifi_business_author_path" => $conf["certifi_business_author_path"] ? config("author_attachments_url") . $conf["certifi_business_author_path"] : ""];
		$data["certifi_business_is_upload"] = $conf["certifi_business_open"] ? $conf["certifi_business_is_upload"] : $data["certifi_is_upload"];
		return $data;
	}
	public function verify()
	{
		$verify["is_captcha"] = configuration("is_captcha");
		$verify["allow_register_email_captcha"] = configuration("allow_register_email_captcha");
		$verify["allow_register_phone_captcha"] = configuration("allow_register_phone_captcha");
		$verify["allow_login_phone_captcha"] = configuration("allow_login_phone_captcha");
		$verify["allow_login_email_captcha"] = configuration("allow_login_email_captcha");
		$verify["allow_login_code_captcha"] = configuration("allow_login_code_captcha");
		$verify["allow_login_id_captcha"] = configuration("allow_login_id_captcha");
		$verify["allow_phone_forgetpwd_captcha"] = configuration("allow_phone_forgetpwd_captcha");
		$verify["allow_email_forgetpwd_captcha"] = configuration("allow_email_forgetpwd_captcha");
		$verify["allow_resetpwd_captcha"] = configuration("allow_resetpwd_captcha");
		$verify["allow_phone_bind_captcha"] = configuration("allow_phone_bind_captcha");
		$verify["allow_email_bind_captcha"] = configuration("allow_email_bind_captcha");
		$verify["allow_cancel_captcha"] = configuration("allow_cancel_captcha");
		$verify["allow_login_admin_captcha"] = configuration("allow_login_admin_captcha");
		$verify["allow_cancel_sms_captcha"] = configuration("allow_cancel_sms_captcha");
		$verify["allow_cancel_email_captcha"] = configuration("allow_cancel_email_captcha");
		$verify["allow_setpwd_captcha"] = configuration("allow_setpwd_captcha");
		if (configuration("login_error_switch")) {
			$login_error_max_num = configuration("login_error_max_num");
			if ($login_error_max_num) {
				$verify["is_captcha"] = $login_error_max_num < intval(\think\facade\Cookie::get("login_error_log")) ? 1 : $verify["is_captcha"];
				$verify["allow_login_phone_captcha"] = $login_error_max_num < intval(\think\facade\Cookie::get("login_error_log")) ? 1 : $verify["allow_login_phone_captcha"];
				$verify["allow_login_email_captcha"] = $login_error_max_num < intval(\think\facade\Cookie::get("login_error_log")) ? 1 : $verify["allow_login_email_captcha"];
				$verify["allow_login_id_captcha"] = $login_error_max_num < intval(\think\facade\Cookie::get("login_error_log")) ? 1 : $verify["allow_login_id_captcha"];
				$verify["allow_login_code_captcha"] = $login_error_max_num < intval(\think\facade\Cookie::get("login_error_log")) ? 1 : $verify["allow_login_code_captcha"];
			}
		}
		return $verify;
	}
	public function userinfo($strjwt = "")
	{
		$jwt = is_string($strjwt) ? $strjwt : userGetCookie();
		$jwt = html_entity_decode($jwt);
		$uid = \think\facade\Cache::get("client_user_login_token_" . $jwt);
		if ($uid) {
			$key = config("jwtkey");
			$jwtAuth = json_encode(\Firebase\JWT\JWT::decode($jwt, $key, ["HS256"]));
			$authInfo = json_decode($jwtAuth, true);
			$checkJwtToken = ["status" => 1001, "msg" => "Token验证通过", "id" => $authInfo["userinfo"]["id"], "nbf" => $authInfo["nbf"], "ip" => $authInfo["ip"], "contactid" => $authInfo["userinfo"]["contactid"] ?? 0, "username" => $authInfo["userinfo"]["username"]];
			$pass = \think\facade\Cache::get("client_user_update_pass_" . $uid);
			if ($pass && $checkJwtToken["nbf"] < $pass) {
				$authInfo = false;
			}
			$ip_check = configuration("home_ip_check");
			if (get_client_ip() !== $checkJwtToken["ip"] && $ip_check == 1) {
				$authInfo = false;
			}
		} else {
			$authInfo = false;
		}
		if ($authInfo) {
			$userinfo = \think\Db::name("clients")->field("username,sex,avatar,companyname,email,country,province,city,address1,phonenumber,lastloginip,credit,language,status,id")->where("id", $uid)->find();
		} else {
			$userinfo = [];
		}
		if (is_string($strjwt)) {
			$userinfo["uid"] = $uid;
		}
		return $userinfo;
	}
	/**
	 * @title 新闻分类
	 */
	public function newsCate()
	{
		return $this->cateList(1);
	}
	/**
	 * @title 帮助分类
	 */
	public function helpCate()
	{
		return $this->cateList(2);
	}
	/**
	 * @title 分类
	 */
	public function cateList($parent_id)
	{
		$list = \think\Db::name("news_type")->where(["hidden" => 0, "parent_id" => $parent_id])->order("id", "DESC")->select()->toArray();
		$data = [];
		foreach ($list as $k => $v) {
			$data[$k] = $v;
			$data[$k]["count"] = \think\Db::name("news_menu")->where(["parent_id" => $v["id"], "hidden" => 0])->count();
		}
		return $data;
	}
	/**
	 * @title 新闻列表页
	 */
	public function newsList($param, $tagDataVal = [])
	{
		$param["tagDataVal"] = $tagDataVal;
		return $this->nList($param, 1);
	}
	/**
	 * @title 新闻分类列表页
	 */
	public function newsListCate($param)
	{
		$param["data"] = "ListCate";
		return $this->nList($param, 1);
	}
	/**
	 * @title 新闻搜索列表页
	 */
	public function newsSearch($param)
	{
		$param["data"] = "Search";
		return $this->nList($param, 1);
	}
	/**
	 * @title 帮助列表页
	 */
	public function helpList($param, $tagDataVal = [])
	{
		$param["tagDataVal"] = $tagDataVal;
		return $this->nList($param, 2);
	}
	/**
	 * @title 帮助分类列表页
	 */
	public function helpListCate($param)
	{
		$param["data"] = "ListCate";
		return $this->nList($param, 2);
	}
	/**
	 * @title 帮助搜索列表页
	 */
	public function helpSearch($param)
	{
		$param["data"] = "Search";
		return $this->nList($param, 2);
	}
	/**
	 * @title 列表页
	 */
	public function nList($param, $cate_id)
	{
		$returndata = [];
		$tagDataVal_num = intval($param["tagDataVal"]["num"]);
		$cid = $param["tagDataVal"]["cid"];
		if ($tagDataVal_num > 0) {
			$pagecount = $param["limit"] ?: $tagDataVal_num;
		} else {
			$pagecount = $param["limit"] ?: 10;
		}
		if ($param["tagDataVal"]["order"] == "desc" || $param["tagDataVal"]["order"] == "asc") {
			$order = $param["tagDataVal"]["order"];
		} else {
			$order = "DESC";
		}
		if ($param["data"] == "ListCate") {
			$parent_id = $param["html2"];
			$page = intval($param["html3"]) > 0 ? intval($param["html3"]) : 1;
		} elseif ($param["data"] == "Search") {
			$page = intval($param["page"]) > 0 ? intval($param["page"]) : 1;
		} else {
			$page = intval($param["html2"]) > 0 ? intval($param["html2"]) : 1;
		}
		$where = [["hidden", "=", 0]];
		if (isset($parent_id) && $parent_id > 0) {
			$where[] = ["parent_id", "=", $parent_id];
		} else {
			if (is_array($cid)) {
				$where[] = ["parent_id", "in", $cid];
			} else {
				$tmp_list = \think\Db::name("news_type")->where(["parent_id" => $cate_id, "hidden" => 0])->select()->toArray();
				$tmp_ids = array_column($tmp_list, "id");
				$where[] = ["parent_id", "in", $tmp_ids];
			}
		}
		if (isset($param["search"])) {
			$where[] = ["content|title", "LIKE", "%" . $param["search"] . "%", "OR"];
		}
		$news_menu = \think\Db::name("news_menu as m")->field("m.*")->leftJoin("news", "m.id=news.relid")->where($where)->withAttr("news.content", function ($value) {
			return mb_substr($value, 0, 50);
		})->order("push_time", $order)->page($page, $pagecount)->select()->toArray();
		$count = \think\Db::name("news_menu as m")->leftJoin("news", "m.id=news.relid")->where($where)->count();
		$url = $this->upload_url;
		$returndata["list"] = array_map(function ($v) use($url) {
			$v["head_img"] = isset($v["head_img"][0]) ? $url . $v["head_img"] : "";
			$v["create_date"] = date("Y-m-d", $v["create_time"]);
			$v["update_date"] = date("Y-m-d", $v["update_time"]);
			$v["push_date"] = date("Y-m-d", $v["push_time"]);
			return $v;
		}, $news_menu);
		$returndata["pagecount"] = $pagecount;
		$returndata["page"] = $page;
		$returndata["search"] = $param["search"];
		$returndata["total_page"] = ceil($count / $pagecount);
		$returndata["count"] = $count;
		if ($parent_id > 0) {
			$news_type = \think\Db::name("news_type")->where(["id" => $parent_id, "hidden" => 0])->find();
			if (empty($news_type["id"])) {
				return false;
			}
			$returndata["cateid"] = $parent_id;
			$returndata["catename"] = $news_type["title"];
		}
		if ($returndata["total_page"] > 1) {
			$num = 3;
			$returndata["previous"] = $page == 1 ? 0 : $page - 1;
			$returndata["next"] = $returndata["total_page"] <= $page ? 0 : $page + 1;
			$returndata["start"] = $page;
			$returndata["num"] = $page + $num > $returndata["total_page"] ? $returndata["total_page"] : $page + $num;
			if ($returndata["num"] - $returndata["start"] < $num) {
				$returndata["start"] = $returndata["num"] - $num > 0 ? $returndata["num"] - $num : 1;
			}
			$returndata["num"] = $returndata["num"] + 1;
		}
		return $returndata;
	}
	/**
	 * @title 新闻详细
	 */
	public function newsContent($param)
	{
		return $this->content($param["html2"], 1);
	}
	/**
	 * @title 帮助详细
	 */
	public function helpContent($param)
	{
		return $this->content($param["html2"], 2);
	}
	/**
	 * @title 详细页
	 */
	public function content($id, $cate_id)
	{
		$id = intval($id);
		if (empty($id)) {
			return "";
		}
		$new_content = \think\Db::name("news_menu")->field("m.*,n.content,u.user_nickname as author,nt.title as cate_name")->alias("m")->leftJoin("news n", "n.relid=m.id")->leftJoin("news_type nt", "nt.id=m.parent_id")->leftJoin("shd_user u", "u.id=m.admin_id")->where("m.id", $id)->find();
		if (empty($new_content)) {
			return false;
		}
		unset($new_content["admin_id"]);
		$new_content["head_img"] = isset($new_content["head_img"][0]) ? $this->upload_url . $new_content : "";
		$new_content["content"] = htmlspecialchars_decode(htmlspecialchars_decode($new_content["content"]));
		$returndata = $new_content;
		$where = [["hidden", "=", 0]];
		$parent = \think\Db::name("news_type")->field("id")->where("parent_id", $cate_id)->select()->toArray();
		$parent = implode(",", array_column($parent, "id"));
		\think\Db::name("news_menu")->where("id", $id)->update(["read" => $new_content["read"] + 1]);
		$returndata["next"] = \think\Db::name("news_menu")->alias("m")->field("m.id,m.title")->where(array_merge($where, [["id", "<", $id]]))->whereIn("parent_id", $parent)->order("id", "desc")->find();
		$returndata["prev"] = \think\Db::name("news_menu")->alias("m")->field("m.id,m.title")->where(array_merge($where, [["id", ">", $id]]))->whereIn("parent_id", $parent)->order("id", "asc")->find();
		return $returndata;
	}
	public function currencyPriority($currencyId = "", $uid = "")
	{
		if (!empty($currencyId)) {
			$currencyId = intval($currencyId);
			$currency = \think\Db::name("currencies")->where("id", $currencyId)->find();
		} else {
			$currency = \think\Db::name("clients")->field("currency")->where("id", $uid)->find();
			if (!empty($currency["currency"])) {
				$currency = \think\Db::name("currencies")->where("id", $currency["currency"])->find();
			} else {
				$currency = \think\Db::name("currencies")->where("default", 1)->find();
			}
		}
		$currency = array_map(function ($v) {
			return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
		}, $currency);
		unset($currency["format"]);
		unset($currency["rate"]);
		unset($currency["default"]);
		return $currency;
	}
	public function firstGroups($param, $tagDataVal)
	{
		if (count($tagDataVal) > 0 && is_array($tagDataVal)) {
			$in = " AND id IN (" . implode(",", $tagDataVal) . ")";
		}
		$productgroups = \think\Db::name("product_first_groups")->field("id,name,hidden,order")->where("hidden=0" . $in)->order("order", "asc")->select();
		foreach ($productgroups as $key => $productgroup) {
			$filterproductgroups[$key] = array_map(function ($v) {
				return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
			}, $productgroup);
		}
		return $filterproductgroups;
	}
	public function secondGroups($param, $tagDataVal)
	{
		if (count($tagDataVal) > 0 && is_array($tagDataVal)) {
			$in = " AND id IN (" . implode(",", $tagDataVal) . ")";
		}
		$productgroups = \think\Db::name("product_groups")->field("id,name,headline,tagline,order,gid,order_frm_tpl,tpl_type")->where("hidden=0" . $in)->where("order_frm_tpl", "<>", "uuid")->order("order", "asc")->select();
		$products = \think\Db::name("products")->field("count(`gid`) as count,gid")->group("gid")->select()->toArray();
		$products = array_column($products, "count", "gid");
		foreach ($productgroups as $key => $productgroup) {
			$secondGroups[$key] = array_map(function ($v) {
				return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
			}, $productgroup);
			$secondGroups[$key]["count"] = $products[$productgroup["id"]] ?? 0;
		}
		return $secondGroups;
	}
	public function productGroups($param, $tagDataVal)
	{
		if (count($tagDataVal) > 0 && is_array($tagDataVal)) {
			$in = " AND gid IN (" . implode(",", $tagDataVal) . ")";
			return $this->product($params, $in);
		}
	}
	public function product($params, $tagDataVal)
	{
		$currencies = get_currency();
		$currenciesfilter = [];
		foreach ($currencies as $kk => $currencie) {
			$currenciesfilter[$kk] = array_map(function ($v) {
				return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
			}, $currencie);
		}
		$currency = isset($params["currencyid"]) ? intval($params["currencyid"]) : "";
		$uid = !empty(request()->uid) ? request()->uid : "";
		$currency = $this->currencyPriority($currency, $uid);
		$currencyid = $currency["id"];
		if (!$currencyid) {
			return jsons(["status" => 400, "msg" => lang("NO_THIS_CURRENCY")]);
		}
		if (count($tagDataVal) > 0 && is_array($tagDataVal)) {
			$in = " AND id IN (" . implode(",", $tagDataVal) . ")";
		}
		if (empty($in)) {
			$in = $tagDataVal;
		}
		$products = \think\Db::name("products")->field("id,type,gid,name,description,pay_method,tax,order,pay_type,api_type,upstream_version,upstream_price_type,upstream_price_value,stock_control,qty")->where("hidden=0" . $in)->where("p_uid", 0)->where("retired", 0)->order("order", "asc")->select()->toArray();
		foreach ($products as $kkk => $product) {
			$filterproducts[$kkk] = array_map(function ($v) {
				return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
			}, $product);
		}
		foreach ($filterproducts as $key => $v) {
			if (!empty($v)) {
				$paytype = (array) json_decode($v["pay_type"]);
				$pricing = \think\Db::name("pricing")->where("type", "product")->where("relid", $v["id"])->where("currency", $currencyid)->find();
				if (!empty($paytype["pay_ontrial_status"])) {
					if ($pricing["ontrial"] >= 0) {
						$v["product_price"] = $pricing["ontrial"];
						$v["setup_fee"] = $pricing["ontrialfee"];
						$v["billingcycle"] = "ontrial";
						$v["billingcycle_zh"] = lang("ONTRIAL");
					} else {
						$v["product_price"] = 0;
						$v["setup_fee"] = 0;
						$v["billingcycle"] = "";
						$v["billingcycle_zh"] = lang("PRICE_NO_CONFIG");
					}
					$v["ontrial"] = 1;
					$v["ontrial_cycle"] = $paytype["pay_ontrial_cycle"];
					$v["ontrial_cycle_type"] = $paytype["pay_ontrial_cycle_type"] ?: "day";
					$v["ontrial_price"] = $pricing["ontrial"];
					$v["ontrial_setup_fee"] = $pricing["ontrialfee"];
				} else {
					$v["ontrial"] = 0;
				}
				if ($paytype["pay_type"] == "free") {
					$v["product_price"] = 0;
					$v["setup_fee"] = 0;
					$v["billingcycle"] = "free";
					$v["billingcycle_zh"] = lang("FREE");
				} elseif ($paytype["pay_type"] == "onetime") {
					if ($pricing["onetime"] >= 0) {
						$v["product_price"] = $pricing["onetime"];
						$v["setup_fee"] = $pricing["osetupfee"];
						$v["billingcycle"] = "onetime";
						$v["billingcycle_zh"] = lang("ONETIME");
					} else {
						$v["product_price"] = 0;
						$v["setup_fee"] = 0;
						$v["billingcycle"] = "";
						$v["billingcycle_zh"] = lang("PRICE_NO_CONFIG");
					}
				} else {
					if (!empty($pricing) && $paytype["pay_type"] == "recurring") {
						if ($pricing["hour"] >= 0) {
							$v["product_price"] = $pricing["hour"];
							$v["setup_fee"] = $pricing["hsetupfee"];
							$v["billingcycle"] = "hour";
							$v["billingcycle_zh"] = lang("HOUR");
						} elseif ($pricing["day"] >= 0) {
							$v["product_price"] = $pricing["day"];
							$v["setup_fee"] = $pricing["dsetupfee"];
							$v["billingcycle"] = "day";
							$v["billingcycle_zh"] = lang("DAY");
						} elseif ($pricing["monthly"] >= 0) {
							$v["product_price"] = $pricing["monthly"];
							$v["setup_fee"] = $pricing["msetupfee"];
							$v["billingcycle"] = "monthly";
							$v["billingcycle_zh"] = lang("MONTHLY");
						} elseif ($pricing["quarterly"] >= 0) {
							$v["product_price"] = $pricing["quarterly"];
							$v["setup_fee"] = $pricing["qsetupfee"];
							$v["billingcycle"] = "quarterly";
							$v["billingcycle_zh"] = lang("QUARTERLY");
						} elseif ($pricing["semiannually"] >= 0) {
							$v["product_price"] = $pricing["semiannually"];
							$v["setup_fee"] = $pricing["ssetupfee"];
							$v["billingcycle"] = "semiannually";
							$v["billingcycle_zh"] = lang("SEMIANNUALLY");
						} elseif ($pricing["annually"] >= 0) {
							$v["product_price"] = $pricing["annually"];
							$v["setup_fee"] = $pricing["asetupfee"];
							$v["billingcycle"] = "annually";
							$v["billingcycle_zh"] = lang("ANNUALLY");
						} elseif ($pricing["biennially"] >= 0) {
							$v["product_price"] = $pricing["biennially"];
							$v["setup_fee"] = $pricing["bsetupfee"];
							$v["billingcycle"] = "biennially";
							$v["billingcycle_zh"] = lang("BIENNIALLY");
						} elseif ($pricing["triennially"] >= 0) {
							$v["product_price"] = $pricing["triennially"];
							$v["setup_fee"] = $pricing["tsetupfee"];
							$v["billingcycle"] = "triennially";
							$v["billingcycle_zh"] = lang("TRIENNIALLY");
						} elseif ($pricing["fourly"] >= 0) {
							$v["product_price"] = $pricing["fourly"];
							$v["setup_fee"] = $pricing["foursetupfee"];
							$v["billingcycle"] = "fourly";
							$v["billingcycle_zh"] = lang("FOURLY");
						} elseif ($pricing["fively"] >= 0) {
							$v["product_price"] = $pricing["fively"];
							$v["setup_fee"] = $pricing["fivesetupfee"];
							$v["billingcycle"] = "fively";
							$v["billingcycle_zh"] = lang("FIVELY");
						} elseif ($pricing["sixly"] >= 0) {
							$v["product_price"] = $pricing["sixly"];
							$v["setup_fee"] = $pricing["sixsetupfee"];
							$v["billingcycle"] = "sixly";
							$v["billingcycle_zh"] = lang("SIXLY");
						} elseif ($pricing["sevenly"] >= 0) {
							$v["product_price"] = $pricing["sevenly"];
							$v["setup_fee"] = $pricing["sevensetupfee"];
							$v["billingcycle"] = "sevenly";
							$v["billingcycle_zh"] = lang("SEVENLY");
						} elseif ($pricing["eightly"] >= 0) {
							$v["product_price"] = $pricing["eightly"];
							$v["setup_fee"] = $pricing["eightsetupfee"];
							$v["billingcycle"] = "eightly";
							$v["billingcycle_zh"] = lang("EIGHTLY");
						} elseif ($pricing["ninely"] >= 0) {
							$v["product_price"] = $pricing["ninely"];
							$v["setup_fee"] = $pricing["ninesetupfee"];
							$v["billingcycle"] = "ninely";
							$v["billingcycle_zh"] = lang("NINELY");
						} elseif ($pricing["tenly"] >= 0) {
							$v["product_price"] = $pricing["tenly"];
							$v["setup_fee"] = $pricing["tensetupfee"];
							$v["billingcycle"] = "tenly";
							$v["billingcycle_zh"] = lang("TENLY");
						} else {
							$v["product_price"] = 0;
							$v["setup_fee"] = 0;
							$v["billingcycle"] = "";
							$v["billingcycle_zh"] = lang("PRICE_CONFIG_ERROR");
						}
					} else {
						$v["product_price"] = 0;
						$v["setup_fee"] = 0;
						$v["billingcycle"] = "";
						$v["billingcycle_zh"] = lang("PRICE_NO_CONFIG");
					}
				}
				if ($paytype["pay_type"] == "recurring" && in_array($v["type"], array_keys(config("developer_app_product_type")))) {
					if ($pricing["annually"] > 0) {
						$v["product_price"] = $pricing["annually"];
						$v["setup_fee"] = $pricing["asetupfee"];
						$v["billingcycle"] = "annually";
						$v["billingcycle_zh"] = lang("ANNUALLY");
					}
				}
				$v["product_price"] = bcadd($v["setup_fee"], $v["product_price"], 2);
				$cart_logic = new \app\common\logic\Cart();
				$rebate_total = 0;
				$config_total = $cart_logic->getProductDefaultConfigPrice($v["id"], $currencyid, $v["billingcycle"], $rebate_total);
				$rebate_total = bcadd($v["product_price"], $rebate_total, 2);
				$v["product_price"] = bcadd($v["product_price"], $config_total, 2);
				if ($v["api_type"] == "zjmf_api" && $v["upstream_version"] > 0 && $v["upstream_price_type"] == "percent") {
					$v["product_price"] = bcmul($v["product_price"], $v["upstream_price_value"], 2) / 100;
					$rebate_total = bcmul($rebate_total, $v["upstream_price_value"], 2) / 100;
				}
				$flag = getSaleProductUser($v["id"], $uid);
				$v["sale_price"] = $v["bates"] = 0;
				if ($flag) {
					if ($flag["type"] == 1) {
						$bates = bcdiv($flag["bates"], 100, 2);
						$rebate = bcmul($rebate_total, 1 - $bates, 2) < 0 ? 0 : bcmul($rebate_total, 1 - $bates, 2);
						$v["sale_price"] = bcsub($v["product_price"], $rebate, 2) < 0 ? 0 : bcsub($v["product_price"], $rebate, 2);
						$v["bates"] = bcmul($v["product_price"], 1 - $bates, 2);
					} elseif ($flag["type"] == 2) {
						$bates = $flag["bates"];
						$rebate = $rebate_total < $bates ? $rebate_total : $bates;
						$v["sale_price"] = bcsub($v["product_price"], $rebate, 2) < 0 ? 0 : bcsub($v["product_price"], $rebate, 2);
						$v["bates"] = $bates;
					}
				}
			}
			$v["pay_type"] = json_decode($v["pay_type"], true);
			$newfilterproducts[$key] = $v;
			if ($v["billingcycle"] == "") {
				unset($newfilterproducts[$key]);
			}
		}
		$newfilterproducts = array_values($newfilterproducts);
		$array = ["default_currency" => $currency, "products" => $newfilterproducts];
		return $array;
	}
	public function getDepot()
	{
		$model = \think\Db::name("customfields")->field("fieldname,id")->where("type", "depot")->order("id", "desc")->select()->toArray();
		if (!$model) {
			return [];
		}
		$ids = array_column($model, "id");
		$vals_data = \think\Db::name("customfieldsvalues")->whereIn("fieldid", $ids)->select()->toArray();
		$vals = array_column($vals_data, "value", "fieldid");
		foreach ($model as $key => $val) {
			$model[$key]["value"] = $vals[$val["id"]];
		}
		return array_column($model, "value", "fieldname");
	}
}