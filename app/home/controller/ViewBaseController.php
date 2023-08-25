<?php

namespace app\home\controller;

class ViewBaseController extends CommonController
{
	public $data = [];
	public function __construct()
	{
		sessionInit();
		$sessionAdminId = session("ADMIN_ID");
		$param = request()->param();
		if ($param["forceLogout"]) {
			$this->data["ErrorMsg"] = "该帐号已停用/关闭，请联系管理员处理";
		}
		$this->ViewModel = new \app\home\model\ViewModel();
		if (configuration("main_tenance_mode") == 1 && !$sessionAdminId) {
			$main_tenance_mode_url = configuration("main_tenance_mode_url");
			if (!empty($main_tenance_mode_url)) {
				if (!get_headers($main_tenance_mode_url)) {
					throw new \Exception("维护模式重定向的url不是一个有效的url地址.");
				}
				header("location:" . configuration("main_tenance_mode_url"));
			} else {
				throw new \app\server\MaintainExctption([200, configuration("main_tenance_mode_message") ?: "维护中……"]);
			}
			exit;
		}
		if (VIEW_TEMPLATE_DIRECTORY === "clientarea" || VIEW_TEMPLATE_DIRECTORY === "cart") {
			if (request()->isPost() && !request()->isAjax()) {
				$param = md5(json_encode($param));
				if ($param == session("post")) {
					if ($_SERVER["HTTP_REFERER"]) {
						$refererUrl = $_SERVER["HTTP_REFERER"];
					} else {
						$refererUrl = $this->ViewModel->domain . "/clientarea";
					}
					header("location:" . $refererUrl);
					exit;
				}
				session("post", $param);
			}
			$uid = request()->uid;
			$action = request()->action();
			$controller = request()->controller();
			$clients = \think\Db::name("clients")->field("id")->where("id", $uid)->find();
			if (empty($clients["id"]) && $uid > 0) {
				$uid = 0;
				userUnsetCookie();
			}
			$nologin = ["page", "login", "logout", "register", "pwreset", "bind", "downloads", "news", "newslist", "newsview", "knowledgebase", "knowledgebaselist", "knowledgebaseview", "loginaccesstoken"];
			if (empty($uid) && !in_array($action, $nologin) && !in_array($controller, ["View", "ViewCart"])) {
				header("location:{$this->ViewModel->domain}/login");
				exit;
			}
			if (empty($uid) && in_array($action, ["login", "logout", "register", "pwreset", "bind"])) {
				session("bind_email_change", null);
				session("bind_phone_change", null);
			}
			$clientarea = ["login", "register", "pwreset", "bind"];
			if (!empty($uid) && in_array($action, $clientarea)) {
				header("location:{$this->ViewModel->domain}/clientarea");
				exit;
			}
		}
		if (VIEW_TEMPLATE_DIRECTORY === "clientarea") {
			$theme = \request()->param()["theme"];
			$path_arr = get_files(CMF_ROOT . "public/themes/clientarea");
			if ($theme && in_array($theme, $path_arr) || cookie("clientarea_theme") && in_array(cookie("clientarea_theme"), $path_arr)) {
				if ($theme) {
					cookie("clientarea_theme", $theme);
				}
				define("VIEW_TEMPLATE_DEFAULT", cookie("clientarea_theme") ?: $theme);
			} else {
				define("VIEW_TEMPLATE_SETTING_NAME", "clientarea_default_themes");
				define("VIEW_TEMPLATE_DEFAULT", "default");
			}
		}
		if (\request()->param()["aff"]) {
			$days = configuration("affiliate_cookie");
			setcookie("AffiliateID", \request()->param()["aff"], time() + 86400 * $days, "/");
		}
		if (\request()->param()["language"]) {
			cookie("lang", \request()->param()["language"]);
		}
		$uid = request()->uid;
		$client_status = \think\Db::name("clients")->where("id", $uid)->value("status");
		if ($uid && $client_status != 1 && !$sessionAdminId) {
			userUnsetCookie();
			if (request()->isAjax()) {
				return json(["status" => 400, "msg" => "该帐号已停用/关闭，请联系管理员处理"]);
			}
			$this->redirect("/login?forceLogout=1");
		}
	}
	protected function userlogout()
	{
		userUnsetCookie();
		header("location:/index.html");
		exit;
	}
	protected function viewOutData($view_tpl_file, $paramsData = [])
	{
		if (!session_id()) {
			session_start();
		}
		$_SESSION["view_tpl_tagdata"] = "";
		$_SESSION["view_tpl_debug"] = "";
		$_SESSION["view_tpl_debug_include"] = "";
		$view_html = file_get_contents($view_tpl_file);
		preg_match_all("/{\\s*include.*?file\\s*?=\\s*?[\"\\'](.*?)[\"\\'].*?\\/}/is", $view_html, $view_common);
		foreach ($view_common[1] as $v) {
			if (!empty($v)) {
				$common_view_html = file_get_contents(view_tpl_file($v));
				$tagdata = view_tpl_common($common_view_html, $v);
				if (!empty($tagdata)) {
					$view_html_arr[] = $tagdata;
				}
			}
		}
		$view_html_arr[] = view_tpl_common($view_html);
		$tagArray = [];
		$tagDataArray = [];
		foreach ($view_html_arr as $tag) {
			$tagArr = explode(",", str_replace(" ", "", $tag));
			$tagArray = array_merge($tagArray, $tagArr);
		}
		$tagArray = array_filter($tagArray);
		$tagArray = array_unique($tagArray);
		$paramsID = [];
		foreach ($tagArray as $v) {
			if (stripos($v, "firstGroups") !== false) {
				$result = $this->paramsReplace($v, "firstGroups");
				$v = "firstGroups";
			} elseif (stripos($v, "secondGroups") !== false) {
				$result = $this->paramsReplace($v, "secondGroups");
				$v = "secondGroups";
			} elseif (stripos($v, "product[") !== false) {
				$result = $this->paramsReplace($v, "product");
				$v = "product";
			} elseif (stripos($v, "productGroups[") !== false) {
				$result = $this->paramsReplace($v, "productGroups");
				$v = "productGroups";
			} elseif (stripos($v, "newsList[") !== false) {
				$result = $this->paramsReplace($v, "newsList");
				$v = "newsList";
			} elseif (stripos($v, "helpList[") !== false) {
				$result = $this->paramsReplace($v, "helpList");
				$v = "helpList";
			}
			if ($paramsData[trim($v)]) {
				if ($v == "firstGroups" || $v == "secondGroups" || $v == "product" || $v == "productGroups") {
					foreach ($result as $rk => $re) {
						$pid = $re;
					}
					$paramsID = array_merge($paramsID, $pid);
					$paramsID = array_filter($paramsID);
					$paramsID = array_unique($paramsID);
					$tagDataArray[trim($v)] = $paramsID;
				} else {
					if ($v == "newsList" || $v == "helpList") {
						$arr = [];
						foreach ($result as $list_key => $list_val) {
							foreach ($list_val as $kkey => $vval) {
								if ($list_key == "num" || $list_key == "order") {
									$arr[$list_key] = $vval;
								} else {
									$arr[$list_key][$kkey] = $vval;
								}
							}
						}
						$tagDataArray[trim($v)] = $arr;
					} else {
						$tagDataArray[trim($v)] = $paramsData[trim($v)];
					}
				}
			}
		}
		return $tagDataArray;
	}
	protected function paramsReplace($params, $replace)
	{
		$product = str_replace($replace, "", $params);
		$product = str_replace("[", "", $product);
		$product = str_replace("]", "", $product);
		$product = explode("|", $product);
		$product = array_filter($product);
		$product = array_unique($product);
		foreach ($product as $k => $p) {
			list($name, $id) = explode(":", $p);
			$arr[$name][$k] = $id;
		}
		return $arr;
	}
	protected function ajaxPages($showdata = [], $listRow = 10, $curpage = 1, $total = 0)
	{
		$url = "/" . request()->action();
		$p = \think\paginator\driver\Bootstrap::make($showdata, $listRow, $curpage, $total, false, ["var_page" => "page", "path" => $url, "fragment" => "", "query" => $_GET]);
		$pages = $p->render();
		$default_pages = "<li class=\"page-item disabled\"><a class=\"page-link\" href=\"#\">&laquo;</a></li>
	<li class=\"page-item active\"><a class=\"page-link\" href=\"#\">1</a></li>
	<li class=\"page-item disabled\"><a class=\"page-link\" href=\"#\">&raquo;</a></li>";
		$pages = !empty($pages) ? $pages : $default_pages;
		return $pages;
	}
	public function view($tplName, $data = [], $config = [])
	{
		$view_tpl_file = view_tpl_file($tplName);
		$tagDataArray = $this->viewOutData($view_tpl_file);
		$setting["Setting"] = $this->ViewModel->setting();
		$User = controller("User");
		$data["Userinfo"] = request()->uid ? $User->index(request()) : "";
		if (\request()->uid) {
			$sale_info = \think\Db::name("user")->field("user_nickname")->find($data["Userinfo"]["user"]["sale_id"]);
			$data["Userinfo"]["sale_name"] = $sale_info["user_nickname"] ?? "无";
		}
		if (count($data["Userinfo"]["second_verify_action_home"]) > 0) {
			$getSecondVerifyPage = $User->getSecondVerifyPage();
			$data["AllowType"] = $getSecondVerifyPage["data"]["allow_type"];
		} else {
			$data["AllowType"] = [];
		}
		$verify["Verify"] = $this->ViewModel->verify();
		$data["ShowBreadcrumb"] = $data["Breadcrumb"] ? true : false;
		$shop = new \app\common\logic\Shop(request()->uid);
		$data["CartShopData"] = $shop->getShoppingCart();
		$data["CustomDepot"] = $this->ViewModel->getDepot();
		$_LANG = [];
		$lang = get_lang("all");
		$language = load_lang($lang);
		include CMF_ROOT . "public/language/" . $language . ".php";
		$theme_name = configuration(VIEW_TEMPLATE_SETTING_NAME) ?: "default";
		$custom_lang = get_custom_lang("clientarea", $theme_name, "all");
		$lang = array_merge($lang, $custom_lang);
		if (!empty($custom_lang)) {
			$custom_language = customload_lang($custom_lang);
			if (file_exists(CMF_ROOT . "public/themes/clientarea/" . $theme_name . "/language/" . $custom_language . ".php")) {
				include CMF_ROOT . "public/themes/clientarea/" . $theme_name . "/language/" . $custom_language . ".php";
			}
		}
		$data["Lang"] = $_LANG;
		$data["LanguageCheck"] = $lang[$language];
		$data["Language"] = $lang;
		$data["Ver"] = md5(configuration("beta_version"));
		$data["TplName"] = $tplName;
		$data["RouteName"] = $tplName;
		$data["Date"] = date("Y-m-d H:i:s");
		$data["Token"] = md5(time());
		$data["Get"] = request()->get();
		if (request()->fid && request()->gid && request()->pid) {
			$data["Get"]["fid"] = request()->fid;
			$data["Get"]["gid"] = request()->gid;
			$data["Get"]["pid"] = request()->pid;
		}
		$post = request()->post();
		unset($post["token"]);
		$data["Post"] = $post;
		$data["Nav"] = (new \app\common\logic\Menu())->getNavs("client", $setting["Setting"]["web_url"], $language);
		$themes_templates = configuration(VIEW_TEMPLATE_SETTING_NAME);
		$themes_templates = !empty($themes_templates) ? $themes_templates : VIEW_TEMPLATE_DEFAULT;
		$yaml = view_tpl_yaml(CMF_ROOT . "public/themes/cart/" . $themes_templates . "/");
		$site = request()->get()["site"];
		if ($site) {
			$view_header_and_footer = $site;
		} else {
			if (request()->uid) {
				$view_header_and_footer = $yaml["loggedheader"];
			} else {
				$view_header_and_footer = $yaml["nologinheader"];
			}
		}
		if ($view_header_and_footer == "web") {
			$webtplname = configuration("themes_templates");
			if (empty($webtplname)) {
				$webtplname = "clientareaonly";
			}
			$data["userInfo"] = $this->ViewModel->userinfo();
			$data["setting"] = $setting["Setting"];
			$data["setting"]["web_view"] = $this->ViewModel->domain . "/themes/web/" . $webtplname;
			$custom_lang = get_custom_lang("web", $webtplname, "all");
			if (!empty($custom_lang)) {
				$custom_language = customload_lang($custom_lang);
				if (file_exists(CMF_ROOT . "public/themes/web/" . $webtplname . "/language/" . $custom_language . ".php")) {
					include CMF_ROOT . "public/themes/web/" . $webtplname . "/language/" . $custom_language . ".php";
					$data["Lang"] = array_merge($_LANG, $data["Lang"]);
				}
			}
			$setting["Setting"]["current_header"] = "web";
		} else {
			if (!empty($view_header_and_footer)) {
				$setting["Setting"]["current_header"] = "clientarea";
			}
		}
		$menu_model = new \app\common\logic\Menu();
		$data["www_top"] = $menu_model->getWebNav("www_top", "", $custom_language, false);
		$data["www_bottom"] = $menu_model->getWebNav("www_bottom", "", $custom_language, false);
		$data["f_links"] = \think\Db::name("friendly_links")->where("is_open", 1)->select()->toArray();
		$data = array_merge($verify, $data);
		$data = array_merge($setting, $data);
		$_SESSION["view_tpl_data"] = $data;
		$_SESSION["paramsData"] = [];
		$tplName = !empty($tplName) ? $tplName : request()->action();
		$view = new \think\View();
		$view->init("Think");
		$template = $view->fetch($tplName, $data, $config);
		if (!$this->zjmf_authorize() && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) != "xmlhttprequest") {
			if ($language != "chinese") {
				$powered = "IDCSMART";
			} else {
				$powered = "智简魔方";
			}
			$template = $template . "<a style=\"position: absolute;right: 10px;bottom: 20px;color:#555;z-index:9999;display: block!important;\" href=\"https://www.idcsmart.com\" target=\"_blank\"> Powered by &copy;" . $powered . "</a></body>";
		}
		return $template;
	}
	public function zjmf_authorize()
	{
		$zjmf_authorize = configuration("zjmf_authorize");
		if (empty($zjmf_authorize)) {
			return false;
		} else {
			$_strcode = _strcode($zjmf_authorize, "DECODE", "zjmf_key_strcode");
			$_strcode = explode("|zjmf|", $_strcode);
			$authkey = "-----BEGIN PUBLIC KEY-----\r\nMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDg6DKmQVwkQCzKcFYb0BBW7N2f\r\nI7DqL4MaiT6vibgEzH3EUFuBCRg3cXqCplJlk13PPbKMWMYsrc5cz7+k08kgTpD4\r\ntevlKOMNhYeXNk5ftZ0b6MAR0u5tiyEiATAjRwTpVmhOHOOh32MMBkf+NNWrZA/n\r\nzcLRV8GU7+LcJ8AH/QIDAQAB\r\n-----END PUBLIC KEY-----";
			$pu_key = openssl_pkey_get_public($authkey);
			foreach ($_strcode as $v) {
				openssl_public_decrypt(base64_decode($v), $de, $pu_key);
				$de_str .= $de;
			}
			$auth = json_decode($de_str, true);
			return intval($auth["edition"]);
		}
	}
	public function ticketUploadImage()
	{
		if (empty($_FILES["attachments"]["name"][0])) {
			return ["status" => 200];
		}
		$attachments = request()->file("attachments");
		foreach ($attachments as $image) {
			$str = explode(pathinfo($image->getInfo()["name"])["extension"], $image->getInfo()["name"])[0];
			if (preg_match("/[ ',:;*?~`!@#\$%^&+=)(<>{}]|\\]|\\[|\\/|\\\\|\"|\\|/", substr($str, 0, strlen($str) - 1))) {
				$resultCheck["status"] = 400;
				$resultCheck["msg"] = "文件名只允许数字，字母，还有汉字";
			}
		}
		if ($resultCheck["status"] == 400) {
			return $resultCheck;
		}
		$upload = new \app\common\logic\Upload();
		foreach ($attachments as $image) {
			$resultUpload = $upload->uploadHandle($image);
			if (!$resultUpload) {
				$result = ["status" => 400, "msg" => lang("ERROR MESSAGE")];
			}
			if ($resultUpload["status"] == 200) {
				$result["status"] = 200;
				$result["attachment"][] = $resultUpload["savename"];
			} else {
				$result = ["status" => 400, "msg" => $result["msg"]];
			}
		}
		return $result;
	}
	public function verifiedUploadImage()
	{
		if (empty($_FILES["attachments"]["name"][0])) {
			return ["status" => 200];
		}
		$attachments = request()->file("attachments");
		foreach ($attachments as $image) {
			$str = explode(pathinfo($image->getInfo()["name"])["extension"], $image->getInfo()["name"])[0];
			if (preg_match("/[ ',:;*?~`!@#\$%^&+=)(<>{}]|\\]|\\[|\\/|\\\\|\"|\\|/", substr($str, 0, strlen($str) - 1))) {
				$resultCheck["status"] = 400;
				$resultCheck["msg"] = "文件名只允许数字，字母，还有汉字";
			}
		}
		if ($resultCheck["status"] == 400) {
			return $resultCheck;
		}
		$upload = new \app\common\logic\Upload();
		foreach ($attachments as $image) {
			$resultUpload = $upload->uploadHandle($image);
			if (!$resultUpload) {
				$result = ["status" => 400, "msg" => $resultUpload["msg"] ?: lang("ERROR MESSAGE")];
			}
			if ($resultUpload["status"] == 200) {
				$result["status"] = 200;
				$result["idimage"][] = $resultUpload["savename"];
			} else {
				$result = ["status" => 400, "msg" => $result["msg"]];
			}
		}
		return $result;
	}
	public function pushParam(\think\Request $request)
	{
		$id = $request->uid;
		$data = \think\Db::name("affiliates")->alias("a")->join("clients c", "c.id=a.uid")->leftJoin("currencies cu", "cu.id = c.currency")->field("a.*,cu.suffix,cu.prefix")->where("uid", $id)->find();
		$aff = ["affStatus" => $data ? 1 : 0, "affNum" => $data ? $data["visitors"] : 0];
		$nextduedate3 = \think\Db::name("host")->where("domainstatus", "=", "Active")->whereBetweenTime("nextduedate", time(), time() + 259200)->where("uid", $id)->count();
		$nextduedate7 = \think\Db::name("host")->where("domainstatus", "=", "Active")->whereBetweenTime("nextduedate", time(), time() + 604800)->where("uid", $id)->count();
		$createduedate7 = \think\Db::name("host")->where("domainstatus", "=", "Active")->whereBetweenTime("create_time", time() - 604800, time())->where("uid", $id)->count();
		$createduedate30 = \think\Db::name("host")->where("domainstatus", "=", "Active")->whereBetweenTime("create_time", time() - 2592000, time())->where("uid", $id)->count();
		$certifi_person = \think\Db::name("certifi_person")->where("auth_user_id", $id)->column("status");
		$_user = \think\Db::name("clients")->where("id", $id)->find();
		$accounts7 = \think\Db::name("accounts")->field("amount_in,amount_out,fees")->whereBetweenTime("create_time", time() - 604800, time())->where("uid", $id)->sum("amount_in");
		$accounts30 = \think\Db::name("accounts")->field("amount_in,amount_out,fees")->whereBetweenTime("create_time", time() - 604800, time())->where("uid", $id)->sum("amount_in");
		$accounts = \think\Db::name("accounts")->field("amount_in,amount_out,fees")->where("uid", $id)->sum("amount_in");
		return ["aff" => $aff, "nextduedate3" => $nextduedate3, "nextduedate7" => $nextduedate7, "_user" => $_user, "user_certtfi" => $certifi_person ? $certifi_person[0] : 0, "accounts7" => number_format(round($accounts7, 2), 2), "accounts30" => number_format(round($accounts30, 2), 2), "accounts" => number_format(round($accounts, 2), 2), "createduedate7" => $createduedate7, "createduedate30" => $createduedate30];
	}
}