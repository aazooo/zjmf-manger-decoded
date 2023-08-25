<?php

require_once "zjmf.php";
if (file_exists(CMF_ROOT . "app/res/common.php")) {
	require_once "res/common.php";
}
function p($data, $status = false)
{
	if (!$status) {
		echo "<pre/>";
		dump($data);
		exit;
	} else {
		echo json_encode($data, JSON_THROW_ON_ERROR);
		exit;
	}
}
function localAPI($class, $data = [])
{
	$array = explode("_", $class);
	$module = $array[0];
	$action = $array[1];
	if (strtolower(\think\facade\Request::action()) == strtolower($action)) {
		return ["status" => 400, "msg" => "不能调用当前函数"];
	}
	$result = controller("openapi/" . $module);
	foreach ($data as $k => $v) {
		request()->{$k} = $v;
	}
	$result = $result->{$action}()->getData();
	return $result;
}
function adminAddress()
{
	return config("database.admin_application") ?: "admin";
}
function adminTheme()
{
	return configuration("admin_default_theme") ?: "default";
}
/**
 * jwt
 * @param $userId
 * @return mixed
 */
function createJwt($userinfo, $expire = 7200)
{
	$key = config("jwtkey");
	$time = time();
	$token = ["userinfo" => $userinfo, "iss" => "www.idcSmart.com", "aud" => "www.idcSmart.com", "ip" => get_client_ip(), "iat" => $time, "nbf" => $time, "exp" => $time + $expire];
	$jwt = \Firebase\JWT\JWT::encode($token, $key);
	$key = "client_user_login_token_" . $jwt;
	\think\facade\Cache::set($key, $userinfo["id"], $expire);
	return $jwt;
}
function userCreateCookie()
{
	$cookie_clientarea_nmae = configuration("cookie_clientarea_nmae");
	if ($cookie_clientarea_nmae) {
		return $cookie_clientarea_nmae;
	}
	$http_host = explode(".", $_SERVER["HTTP_HOST"]);
	$count = count($http_host);
	if ($count > 2) {
		$domain = $http_host[$count - 2] . "." . $http_host[$count - 1];
	} else {
		$domain = "domain";
	}
	$system_license = configuration("system_license");
	if (!$system_license) {
		$system_license = "system_license";
	}
	$cookieNmae = "ZJMF_" . strtoupper(substr(md5($system_license . $domain), 16));
	updateConfiguration("cookie_clientarea_nmae", $cookieNmae);
	return $cookieNmae;
}
function userSetCookie($jwt)
{
	$cookie = config("database.domain_cookie");
	if (!empty($cookie)) {
		setcookie(usercreatecookie(), $jwt, time() + 7200, "/", $cookie);
	} else {
		setcookie(usercreatecookie(), $jwt, time() + 7200, "/");
	}
}
function userGetCookie()
{
	$userCreateCookie = usercreatecookie();
	return $_COOKIE[$userCreateCookie] ?? "";
}
function userUnsetCookie()
{
	$cookie = config("database.domain_cookie");
	if (!empty($cookie)) {
		setcookie("OrfLcI2IqQItv0vS", "", -3600, "/", $cookie);
		setcookie(usercreatecookie(), "", -3600, "/", $cookie);
	} else {
		setcookie("OrfLcI2IqQItv0vS", "", -3600, "/");
		setcookie(usercreatecookie(), "", -3600, "/");
	}
}
function crossProductUpgrade($hostid, $new_productid)
{
	$pid = \think\Db::name("host")->where("id", $hostid)->value("productid");
	if ($pid == $new_productid) {
		return [];
	}
	$new_config = \think\Db::name("product_config_options_sub")->alias("o_sub")->field("o.hidden,o.option_name,o.qty_minimum,o.qty_maximum,o_sub.option_name as option_name_sub,o_sub.qty_minimum as qty_minimum_sub,o_sub.qty_maximum as qty_maximum_sub,o_sub.id,o_sub.config_id,o.option_type,o.is_discount,o.option_name,o.is_rebate")->leftJoin("product_config_options o", "o_sub.config_id=o.id")->leftJoin("product_config_links links", "o.gid=links.gid")->where("links.pid", $new_productid)->order("o_sub.sort_order ASC")->order("o_sub.id ASC")->select()->toArray();
	$old_config = \think\Db::name("host_config_options")->alias("h")->field("o.option_name,o.qty_minimum,o.qty_maximum,o_sub.option_name as option_name_sub,o_sub.qty_minimum as qty_minimum_sub,o_sub.qty_maximum as qty_maximum_sub,h.qty,o.option_type")->leftJoin("product_config_options o", "o.id=h.configid")->leftJoin("product_config_options_sub o_sub", "o_sub.id=h.optionid")->where("h.relid", $hostid)->select()->toArray();
	$old_con = [];
	$qty = [];
	foreach ($old_config as $v) {
		$config = md5($v["option_name"] . $v["qty_minimum"] . $v["qty_maximum"]);
		$option = md5($v["option_name_sub"] . $v["qty_minimum_sub"] . $v["qty_maximum_sub"]);
		$old_con[$config] = $option;
		$qty[$config] = $v["qty"];
	}
	$host_configoptions = [];
	$config_id = [];
	foreach ($new_config as $v) {
		$host = [];
		$config = md5($v["option_name"] . $v["qty_minimum"] . $v["qty_maximum"]);
		$option = md5($v["option_name_sub"] . $v["qty_minimum_sub"] . $v["qty_maximum_sub"]);
		if ($old_con[$config] == $option) {
			$host["id"] = $v["config_id"];
			$host["cid"] = $v["config_id"];
			$host["hidden"] = $v["hidden"];
			$host["sub_id"] = $v["id"];
			$host["option_name_sub"] = $v["option_name_sub"];
			$host["option_type"] = $v["option_type"];
			$host["is_discount"] = $v["is_discount"];
			$host["option_name"] = $v["option_name"];
			$host["is_rebate"] = $v["is_rebate"];
			$host["qty_minimum"] = $qty[$config];
			$config_id[] = $v["config_id"];
		} else {
			if (!in_array($v["config_id"], $config_id)) {
				$host["id"] = $v["config_id"];
				$host["cid"] = $v["config_id"];
				$host["hidden"] = $v["hidden"];
				$host["sub_id"] = $v["id"];
				$host["option_name_sub"] = $v["option_name_sub"];
				$host["option_type"] = $v["option_type"];
				$host["is_discount"] = $v["is_discount"];
				$host["option_name"] = $v["option_name"];
				$host["is_rebate"] = $v["is_rebate"];
				$host["qty_minimum"] = 0;
				$config_id[] = $v["config_id"];
			}
		}
		if (count($host) > 0) {
			$host_configoptions[$v["config_id"]] = $host;
		}
	}
	return $host_configoptions;
}
function ticketContent($content)
{
	$content = explode("\r\n", $content);
	foreach ($content as $val) {
		if (!empty($val)) {
			$_content .= "<p>" . $val . "</p>";
		}
	}
	return $_content;
}
function is_profession()
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
function de_authorize($zjmf_authorize)
{
	$_strcode = _strcode($zjmf_authorize, "DECODE", "zjmf_key_strcode");
	$_strcode = explode("|zjmf|", $_strcode);
	$authkey = "-----BEGIN PUBLIC KEY-----\r\nMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDg6DKmQVwkQCzKcFYb0BBW7N2f\r\nI7DqL4MaiT6vibgEzH3EUFuBCRg3cXqCplJlk13PPbKMWMYsrc5cz7+k08kgTpD4\r\ntevlKOMNhYeXNk5ftZ0b6MAR0u5tiyEiATAjRwTpVmhOHOOh32MMBkf+NNWrZA/n\r\nzcLRV8GU7+LcJ8AH/QIDAQAB\r\n-----END PUBLIC KEY-----";
	$pu_key = openssl_pkey_get_public($authkey);
	$de_str = "";
	foreach ($_strcode as $v) {
		openssl_public_decrypt(base64_decode($v), $de, $pu_key);
		$de_str .= $de;
	}
	$auth = json_decode($de_str, true);
	$auth2 = json_decode($de_str, true);
	$auth["facetoken"] = $auth2["facetoken"];
	return $auth;
}
function de_systemip($authsystemip)
{
	$_strcode = _strcode($authsystemip, "DECODE", "zjmf_key_strcode");
	$_strcode = explode("|zjmf|", $_strcode);
	$authkey = "-----BEGIN PUBLIC KEY-----\r\nMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDg6DKmQVwkQCzKcFYb0BBW7N2f\r\nI7DqL4MaiT6vibgEzH3EUFuBCRg3cXqCplJlk13PPbKMWMYsrc5cz7+k08kgTpD4\r\ntevlKOMNhYeXNk5ftZ0b6MAR0u5tiyEiATAjRwTpVmhOHOOh32MMBkf+NNWrZA/n\r\nzcLRV8GU7+LcJ8AH/QIDAQAB\r\n-----END PUBLIC KEY-----";
	$pu_key = openssl_pkey_get_public($authkey);
	$de_str = "";
	foreach ($_strcode as $v) {
		openssl_public_decrypt(base64_decode($v), $de, $pu_key);
		$de_str .= $de;
	}
	return $de_str;
}
/**
 * 获取管理员所在工单部门
 * @param $admin_id：管理员ID
 * @return bool|mixed|string
 */
function admin_dept($admin_id)
{
	if (!intval($admin_id)) {
		return false;
	}
	$depts = \think\Db::name("ticket_department")->alias("a")->field("a.name")->leftJoin("ticket_department_admin b", "a.id = b.dptid")->leftJoin("user c", "c.id = b.admin_id")->where("c.id", $admin_id)->select()->toArray();
	return array_map(function ($v) {
		return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
	}, array_column($depts, "name"));
}
/**
 * 输入文件夹名
 * 获取文件夹里文件名
 * 输出
 * EN01.jpg#EN02.jpg#EN03.jpg#EN04.jpg#EN05.jpg#EN06.jpg#EN07.jpg#EN08.jpg#EN09.jpg#EN10.jpg#EN11.jpg#EN12.jpg#EN13.jpg#EN14.jpg
 */
function get_file_list($path, $name)
{
	$arr = [];
	$handler = opendir($path);
	while (($filename = readdir($handler)) !== false) {
		if ($filename != "." && $filename != "..") {
			array_push($arr, $filename);
		}
	}
	closedir($handler);
	foreach ($arr as &$value) {
		$value = explode("^", $value)[1];
		if ($value == $name) {
			return false;
			break;
		}
	}
	return true;
}
function get_files($path)
{
	$arr = [];
	$handler = opendir($path);
	while (($filename = readdir($handler)) !== false) {
		if ($filename != "." && $filename != ".." && strpos($filename, ".") === false) {
			array_push($arr, $filename);
		}
	}
	closedir($handler);
	return $arr;
}
function get_lang($display = "", $returndate = false)
{
	$path = CMF_ROOT . "public/language";
	$arr = [];
	$handler = opendir($path);
	while (($filename = readdir($handler)) !== false) {
		if ($filename != "." && $filename != "..") {
			$_LANG = [];
			include $path . "/" . $filename;
			if ($display == "all") {
				$arr[str_replace(".php", "", $filename)]["display_name"] = $_LANG["display_name"];
				$arr[str_replace(".php", "", $filename)]["display_flag"] = $_LANG["display_flag"];
			} else {
				$arr[str_replace(".php", "", $filename)] = $_LANG["display_name"];
			}
			$lang_data_now["display_name"] = $_LANG["display_name"];
			$lang_data_now["display_flag"] = $_LANG["display_flag"];
			$lang_data_now["display_lang"] = str_replace(".php", "", $filename);
			$lang_data_now_all[] = $lang_data_now;
			unset($_LANG);
		}
	}
	closedir($handler);
	if ($returndate) {
		$arr["lang_file_config"] = $lang_data_now_all;
		return $arr;
	}
	return $arr;
}
function strSubstrOmit($name)
{
	if (preg_match("/^[0-9]*[A-Za-z]+\$/is", substr($name, 0, 1))) {
		$name = substr($name, 0, 1);
	} else {
		if (preg_match("/^[\x7f-\xff]*\$/", substr($name, 0, 3))) {
			$name = substr($name, 0, 3);
		} else {
			$name = substr($name, 0, 1);
		}
	}
	return $name . "***";
}
function get_custom_lang($_path = "web", $theme_name, $display = "")
{
	$path = CMF_ROOT . "public/themes/" . $_path . "/" . $theme_name . "/language";
	if (!is_dir($path)) {
		return [];
	}
	$arr = [];
	$handler = opendir($path);
	while (($filename = readdir($handler)) !== false) {
		if ($filename != "." && $filename != "..") {
			$_LANG = [];
			include $path . "/" . $filename;
			if ($display == "all") {
				$arr[str_replace(".php", "", $filename)]["display_name"] = $_LANG["display_name"];
				$arr[str_replace(".php", "", $filename)]["display_flag"] = $_LANG["display_flag"];
			} else {
				$arr[str_replace(".php", "", $filename)] = $_LANG["display_name"];
			}
			unset($_LANG);
		}
	}
	closedir($handler);
	return $arr;
}
function app_lang($name = "")
{
	$install = CMF_ROOT . "data/install.lock";
	$database = CMF_ROOT . "app/config/database.php";
	if (!file_exists($install) || !file_exists($database)) {
		return false;
	}
	$database = (include $database);
	if (empty($database["admin_application"])) {
		return false;
	}
	$url = $_SERVER["REQUEST_URI"];
	if ($url) {
		$arr = explode("/", $url);
		$arr = array_filter($arr);
	}
	$get_lang = get_lang("all");
	if ($arr[1] == $database["admin_application"] && count($arr) >= 2) {
		$languagesys = htmlspecialchars($_GET["languagesys"]);
		if ($languagesys == "US") {
			$lang = "english";
		} elseif ($languagesys == "HK") {
			$lang = "chinese_tw";
		} else {
			$lang = "chinese";
		}
	} else {
		\think\Db::init($database);
		$lang = load_lang($get_lang);
	}
	if (empty($lang) || empty($get_lang[$lang])) {
		$lang = "chinese";
	}
	include CMF_ROOT . "public/language/" . $lang . ".php";
	if (!empty($name)) {
		return $_LANG[$name];
	} else {
		return $_LANG;
	}
}
function load_css($fileName)
{
	$setting = (new \app\home\model\ViewModel())->setting();
	$theme_name = configuration(VIEW_TEMPLATE_SETTING_NAME) ?: VIEW_TEMPLATE_DEFAULT;
	$path = "themes/clientarea/" . $theme_name . "/" . $fileName;
	$pos_path = CMF_ROOT . "public/" . $path;
	return file_exists($pos_path) ? $setting["web_url"] . "/" . $path : false;
}
function get_title_lang($name)
{
	if (!defined("GET_TITLE_LANG_DEFAULT")) {
		$lang = get_lang("all");
		$language = load_lang($lang);
		define("GET_TITLE_LANG_DEFAULT", $language);
	}
	include CMF_ROOT . "public/language/" . GET_TITLE_LANG_DEFAULT . ".php";
	return $_LANG[$name] ?? $name;
}
function load_lang($lang)
{
	$cookieLanguage = cookie("lang");
	if (!empty($cookieLanguage) && !empty($lang[$cookieLanguage])) {
		$language = $cookieLanguage;
	} else {
		$configLanguage = configuration("language");
		if (empty($lang[$configLanguage])) {
			if ($configLanguage == "en-us") {
				$language = "english";
			} elseif ($configLanguage == "zh-cn") {
				$language = "chinese";
			} elseif ($configLanguage == "zh-xg") {
				$language = "chinese_tw";
			} else {
				$language = "chinese";
			}
			updateConfiguration("language", $language);
		} else {
			$language = $configLanguage;
		}
	}
	return $language;
}
function customload_lang($lang)
{
	$cookieLanguage = cookie("lang");
	if (!empty($cookieLanguage) && !empty($lang[$cookieLanguage])) {
		$language = $cookieLanguage;
	} else {
		$language = configuration("language");
	}
	return $language;
}
function view_redirect_url($module = "home")
{
	if (stripos($_SERVER["REQUEST_URI"], "?") !== false) {
		$redirect_url = substr($_SERVER["REQUEST_URI"], 0, strpos($_SERVER["REQUEST_URI"], "?"));
	} else {
		$redirect_url = $_SERVER["REQUEST_URI"];
	}
	if (stripos($redirect_url, ".html") !== false || stripos($redirect_url, ".htm") !== false) {
		$request = \think\facade\Request::instance();
		if (stripos($request->module(), $module) !== false) {
			return true;
		}
	}
	if (!str_replace("/", "", $redirect_url)) {
		return true;
	}
	return false;
}
function view_tpl_yaml($file)
{
	$theme = $file . "/theme.config";
	$themes = [];
	if (file_exists($theme)) {
		$theme = file_get_contents($theme);
		$theme = explode("\r\n", $theme);
		$theme = array_filter($theme);
		foreach ($theme as $v) {
			$theme_config = explode(":", $v);
			$themes[trim($theme_config[0])] = trim(trim(trim($theme_config[1], "'"), "\""));
		}
	}
	return $themes;
}
function view_tpl_file($tpl_file)
{
	$tpl = configuration(VIEW_TEMPLATE_SETTING_NAME);
	if (empty($tpl)) {
		$tpl = VIEW_TEMPLATE_DEFAULT;
	}
	$dir = str_replace("\\", "/", WEB_ROOT . "themes/web/" . $tpl);
	$file = $dir . "/{$tpl_file}." . VIEW_TEMPLATE_SUFFIX;
	if (!file_exists($file)) {
		$file = false;
	}
	return $file;
}
/**
 * 模板数据标签匹配
 * @param $content 模板内容
 * @param $include 是否是引用模板页，空不是，传递了值就是引用模板页
 * @return string 返回字符串
 */
function view_tpl_common($content, $include = "")
{
	preg_match_all("/{\\s*tagdata.*?name\\s*?=\\s*?[\"\\'](.*?)[\"\\'].*?}/is", $content, $view_html_tagdata);
	$view_html_tag = "";
	if (!empty($view_html_tagdata)) {
		foreach ($view_html_tagdata[1] as $tagdata) {
			$view_html_tag .= trim($tagdata, ",") . ",";
		}
		if ($view_html_tag) {
			$_SESSION["view_tpl_tagdata"] = $_SESSION["view_tpl_tagdata"] ? array_merge($_SESSION["view_tpl_tagdata"], $view_html_tagdata[0]) : $view_html_tagdata[0];
		}
	}
	if (VIEW_TEMPLATE_WEB === true) {
	} else {
		$debug = ["{debug}"];
		$_SESSION["view_tpl_debug"] = $debug;
	}
	preg_match_all("/{\\s*debug.*?}/is", $content, $debug);
	if ($debug[0]) {
		$_SESSION["view_tpl_debug"] = $_SESSION["view_tpl_debug"] ? array_merge($_SESSION["view_tpl_debug"], $debug[0]) : $debug[0];
		if (!empty($include)) {
			$_SESSION["view_tpl_debug_include"] = $include;
		}
	}
	return $view_html_tag;
}
function view_tpl_array_out($dataVal, $nbsp = "")
{
	if ($nbsp !== false) {
		$nbsp .= "&nbsp;&nbsp;";
	}
	foreach ($dataVal as $key => $val) {
		if (is_array($val)) {
			if ($nbsp && $nbsp != "") {
				$nbsp_br = $nbsp;
			}
			if (!$nbsp) {
				$nbsp = "";
			}
			$td .= "" . $nbsp_br . $key . "=><br>" . view_tpl_array_out($val, $nbsp);
		} else {
			$td .= $nbsp . $key . "=>\"" . $val . "\"<br>";
		}
	}
	return $td;
}
/**
 * 模板数据标签替换
 * @param $content 模板内容
 * @param $tag 替换标签
 * @param $templateName $tag是debug时的debug所在标签模板名称
 * @return string 返回替换后的字符串
 */
function view_tpl_replace($content, $tag, $templateName = "")
{
	$tagdata = $_SESSION["view_tpl_" . $tag];
	if ($tagdata) {
		$view_tpl_num = $_SESSION["view_tpl_num"] > 0 ? $_SESSION["view_tpl_num"] : 0;
		foreach ($tagdata as $k => $v) {
			if (stripos($content, $v) !== false) {
				if ($tag == "debug") {
					if ($_SESSION["view_tpl_debug_include"] == $templateName && !empty($_SESSION["view_tpl_debug_include"]) || empty($_SESSION["view_tpl_debug_include"])) {
						$sessionparamsData = $_SESSION["paramsData"];
						foreach ($_SESSION["view_tpl_data"] as $dataKey => $dataVal) {
							$td = "<td>\$" . $dataKey . "<br>" . $sessionparamsData[$dataKey] . "</td>";
							if (is_array($dataVal)) {
								$td .= "<td><b>Value</b><br>";
								$td .= view_tpl_array_out($dataVal, false);
								$td .= "</td>";
							} else {
								$td .= "<td><b>Value</b><br>\"" . $dataVal . "\"</td>";
							}
							$debugData .= "<tr>" . $td . "</tr>";
						}
						$paramsData = "<br>";
						foreach ($sessionparamsData as $paramsKey => $paramsVal) {
							$paramsData .= "" . $paramsKey . ":" . $paramsVal . "<br>";
						}
						$templateName = stripos($templateName, "." . VIEW_TEMPLATE_SUFFIX) !== false ? $_SESSION["view_tpl_data"]["TplName"] : $templateName;
						unset($_SESSION["paramsData"]);
						unset($_SESSION["view_tpl_data"]);
						unset($_SESSION["view_tpl_debug_include"]);
						$hn = "";
						if (VIEW_TEMPLATE_WEB === true) {
							$hn = "<h1>当前调试模板debug标签所在文件地址:\"" . configuration(VIEW_TEMPLATE_SETTING_NAME) . "/" . $templateName . "." . VIEW_TEMPLATE_SUFFIX . "\" </h1><h2 style=\"background:green;\">模板可以输出的数据:" . $paramsData . " </h2>";
						} else {
							$hn = "<h1>当前调试模板debug标签所在文件地址:\"" . configuration(VIEW_TEMPLATE_SETTING_NAME) . "/" . $templateName . "." . VIEW_TEMPLATE_SUFFIX . "\" </h1>";
						}
						$debugHtml = "<!DOCTYPE html>
<html lang=\"en\">
<head>
<meta charset=\"utf-8\" />
<title>当前模板输出的数据</title>
<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
<style type=\"text/css\">           
            body, h1, h2, h3, td, th, p {
                font-family: sans-serif;
                font-weight: normal;
                font-size: 0.9em;
                margin: 1px;
                padding: 0;
            }
            h1 {
                margin: 0;
                text-align: left;
                padding: 2px;
                background-color: green;
                color: #fff;
                font-weight: bold;
                font-size: 1.2em;
            }
            h2 {
                background-color: #333;
                color: white;
                text-align: left;
                font-weight: bold;
                padding: 2px;
                border-top: 1px solid black;
            }
            table {
                width: 100%;
            }
            tr, td {
                font-family: monospace;
                vertical-align: top;
                text-align: left;
            }
            td {
                color: green;			
				padding:15px 10px;
            }
            tr:nth-of-type(odd) {
                background-color: #eeeeee;
            }
            tr:nth-of-type(even) {
                background-color: #fafafa;
            }            
        </style>
</head>
<body>
" . $hn . "
<h2>当前模板输出的数据:<br> </h2>
<table>
<tbody>
" . $debugData . "
</tbody>
</table>
</body>
</html>
";
						$debugHtml = ltrim(rtrim(preg_replace(["/> *([^ ]*) *</", "//", "'/\\*[^*]*\\*/'", "/\r\n/", "/\n/", "/\t/", "/>[ ]+</"], [">\\1<", "", "", "", "", "", "><"], $debugHtml)));
						$streplace = "
						<script type=\"text/javascript\">
						idcsmart_debug_console = window.open(\"\", \"" . md5(time()) . "\", \"width=1024,height=600,left=50,top=50,resizable,scrollbars=yes\");
						idcsmart_debug_console.document.write(\"" . addslashes($debugHtml) . "\");
						</script>
						";
						$strreplace = "_smarty_console.document.close();";
						$content1 = substr($content, 0, stripos($content, $v) + strlen($v));
						$content1 = str_replace($v, $streplace, $content1);
						$content = $content1 . substr($content, stripos($content, $v));
						unset($_SESSION["view_tpl_debug"]);
					}
				}
				$content = str_replace($v, "", $content);
				$_SESSION["view_tpl_num"] = $view_tpl_num++;
				if ($tag == "tagdata") {
					if ($_SESSION["view_tpl_num"] == count($tagdata)) {
						unset($_SESSION["view_tpl_tagdata"]);
						unset($_SESSION["view_tpl_num"]);
					}
				}
			}
		}
	}
	return $content;
}
/**
 * 修改config的函数
 * @param $arr1 配置前缀
 * @param $arr2 数据变量
 * @return bool 返回状态
 */
function setconfig($pat, $rep, $admin)
{
	if (is_array($pat) && is_array($rep)) {
		for ($i = 0; $i < count($pat); $i++) {
			$pats[$i] = "/'" . $pat[$i] . "'(.*?),/";
			$reps[$i] = "'" . $pat[$i] . "'" . "=>" . "'" . $rep[$i] . "',";
		}
		$fileurl = APP_PATH . $admin . "/config/template.php";
		$string = file_get_contents($fileurl);
		$string = preg_replace($pats, $reps, $string);
		$re = file_put_contents($fileurl, $string);
		return true;
	} else {
		return false;
	}
}
function clear_cache()
{
	$values = "TEMP_PATH,CACHE_PATH";
	$values = explode(",", $values);
	foreach ($values as $item) {
		if ($item == "LOG_PATH") {
			$dirs = (array) glob(constant($item) . "*");
			foreach ($dirs as $dir) {
				array_map("unlink", glob($dir . "/*.log"));
			}
			array_map("rmdir", $dirs);
		} else {
			array_map("unlink", glob(constant($item) . "/*.*"));
		}
	}
	\think\facade\Cache::clear();
}
/**
 * 递归获取省/市/区
 * @param $data:区域数据
 * @param $pid:父ID
 * @return bool|mixed|string
 */
function getTree($data, $pId)
{
	$tree = [];
	foreach ($data as $k => $v) {
		if ($v["pid"] == $pId) {
			$v["son"] = getTree($data, $v["area_id"]);
			$tree[] = $v;
			unset($data[$k]);
		}
	}
	return $tree;
}
/**
 * 生成随机码
 * @param $len:随机码长度
 * @param $lowwer:是否只小写加数字
 * @return string
 */
function randStr($length = 8, $lowwer = false)
{
	$str = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
	$len = strlen($str) - 1;
	$randstr = "";
	for ($i = 0; $i < $len; $i++) {
		$num = mt_rand(0, $len);
		$randstr .= $str[$num];
	}
	if ($lowwer) {
		return strtolower(substr($randstr, 0, $length));
	}
	return substr($randstr, 0, $length);
}
/**
 * 获取产品类型字符串连接
 * @return string
 */
function getProductTypeString()
{
	return implode(",", array_keys(config("product_type")));
}
function getAppTypeString()
{
	return implode(",", array_keys(config("developer_app_product_type")));
}
function getAppModuleString()
{
	return implode(",", array_keys(config("developer_app_type")));
}
function getCountryCode()
{
	$lang = cookie("language") ?: "zh-cn";
	if ($lang == "zh-cn") {
		$sms = \think\Db::name("sms_country")->field("concat(\"+\",phone_code) as phone_code,concat(\"+\",phone_code,\"(\",name_zh,\")\") as link")->order("phone_code asc")->select()->toArray();
	} else {
		$sms = \think\Db::name("sms_country")->field("concat(\"+\",phone_code) as phone_code,concat(\"+\",phone_code,\"(\",name,\")\") as link")->order("phone_code asc")->select()->toArray();
	}
	return $sms;
}
/**
 * 获取产品组信息
 */
function get_product_groups()
{
	$productgroups = db("product_groups")->field("id,name,headline,tagline,order")->order("order", "asc")->select();
	foreach ($productgroups as $key => $productgroup) {
		$filterproductgroups[$key] = array_map(function ($v) {
			return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
		}, $productgroup);
	}
	return $filterproductgroups;
}
/**
 * 获取产品组信息
 */
function get_product_group()
{
	$productgroups = db("product_groups")->field("id,name")->select();
	foreach ($productgroups as $key => $productgroup) {
		$filterproductgroups[$key] = array_map(function ($v) {
			return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
		}, $productgroup);
	}
	return $filterproductgroups;
}
/**
 * get请求
 * @param $url
 * @return bool|mixed|string
 */
function get_data($url)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$dom = curl_exec($ch);
	curl_close($ch);
	$dom = json_decode($dom, true);
	return $dom;
}
function curl_download($url, $file_name)
{
	$ch = curl_init($url);
	$dir = CMF_ROOT . $file_name;
	$fp = fopen($dir, "wb");
	curl_setopt($ch, CURLOPT_FILE, $fp);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	$res = curl_exec($ch);
	curl_close($ch);
	fclose($fp);
	return $res;
}
function unzip($filepath, $path)
{
	$zip = new ZipArchive();
	$res = $zip->open($filepath);
	if ($res === true) {
		if (!file_exists($path)) {
			mkdir($path, 511, true);
		}
		$zip->extractTo($path);
		$zip->close();
		return ["status" => 200, "msg" => "成功"];
	} else {
		return ["status" => 400, "msg" => $res];
	}
}
/**
 * 返回json数据
 * @param string $data
 * @param int $status
 * @param string $msg
 * @return \think\response\Json
 */
function j($data = "", $status = 400, $msg = "")
{
	return jsonrule(["data" => $data, "status" => $status, "msg" => $msg]);
}
function getCurlRequest($url, $refer = "", $timeout = 15)
{
	$ssl = stripos($url, "https://") === 0 ? true : false;
	$curlObj = curl_init();
	$options = [CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_AUTOREFERER => 1, CURLOPT_USERAGENT => "Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)", CURLOPT_TIMEOUT => $timeout, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_0, CURLOPT_HTTPHEADER => ["Expect:"], CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4];
	if ($refer) {
		$options[CURLOPT_REFERER] = $refer;
	}
	if ($ssl) {
		$options[CURLOPT_SSL_VERIFYHOST] = false;
		$options[CURLOPT_SSL_VERIFYPEER] = false;
	}
	curl_setopt_array($curlObj, $options);
	$returnData = curl_exec($curlObj);
	curl_close($curlObj);
	return $returnData;
}
function checkCertify($uid)
{
	$personcertify = \think\Db::name("certifi_person")->where("auth_user_id", $uid)->where("status", 1)->find();
	$companycertify = \think\Db::name("certifi_company")->where("auth_user_id", $uid)->where("status", 1)->find();
	if (!empty($personcertify) || !empty($companycertify)) {
		return true;
	} else {
		return false;
	}
}
function getNextTime($type, $number = 0, $start = 0, $ontrial = "day")
{
	if (empty($ontrial)) {
		$ontrial = "day";
	}
	if ($start == 0) {
		$start = time();
	}
	if ($type == "hour") {
		$res = strtotime($number . " hour", $start);
	} elseif ($type == "day") {
		$res = strtotime($number . " day", $start);
	} elseif ($type == "ontrial") {
		if ($ontrial == "day") {
			$res = strtotime($number . " day", $start);
		} elseif ($ontrial == "hour") {
			$res = strtotime($number . " hour", $start);
		}
	} elseif ($type == "monthly") {
		$res = strtotime("1 month", $start);
	} elseif ($type == "quarterly") {
		$res = strtotime("3 month", $start);
	} elseif ($type == "semiannually") {
		$res = strtotime("6 month", $start);
	} elseif ($type == "annually") {
		$res = strtotime("1 year", $start);
	} elseif ($type == "biennially") {
		$res = strtotime("2 year", $start);
	} elseif ($type == "triennially") {
		$res = strtotime("3 year", $start);
	} elseif ($type == "fourly") {
		$res = strtotime("4 year", $start);
	} elseif ($type == "fively") {
		$res = strtotime("5 year", $start);
	} elseif ($type == "sixly") {
		$res = strtotime("6 year", $start);
	} elseif ($type == "sevenly") {
		$res = strtotime("7 year", $start);
	} elseif ($type == "eightly") {
		$res = strtotime("8 year", $start);
	} elseif ($type == "ninely") {
		$res = strtotime("9 year", $start);
	} elseif ($type == "tenly") {
		$res = strtotime("10 year", $start);
	} elseif ($type == "onetime") {
		$res = time();
	} else {
		$res = 0;
	}
	return $res;
}
function configuration($config, $default = [])
{
	if (is_array($config)) {
		$result = \think\Db::name("configuration")->field("setting,value")->whereIn("setting", $config)->select()->toArray();
		$re = [];
		foreach ($result as $v) {
			$re[$v["setting"]] = $v["value"];
		}
		return $re;
		$res = [];
		foreach ($config as $kk => $vv) {
			$res[$vv] = is_null($re[$vv]) ? "" : (!is_null($default[$kk]) ? $default[$kk] : "");
		}
		return $res;
	} else {
		$result = \think\Db::name("configuration")->field("value")->whereRaw("setting = :setting", ["setting" => $config])->find();
		$re = $result["value"] ?? null;
		$data["controller"] = request()->controller();
		$data["action"] = request()->action();
		$change_arr = ["is_captcha", "allow_login_phone_captcha", "allow_login_email_captcha", "allow_login_id_captcha", "allow_login_code_captcha"];
		if (implode("/", $data) == "ViewClients/login" && in_array($config, $change_arr)) {
			$login_error_max_num = configuration("login_error_max_num");
			if (configuration("login_error_switch") && $login_error_max_num) {
				return $login_error_max_num < intval(\think\facade\Cookie::get("login_error_log")) ? 1 : $re;
			}
		}
		return $re;
	}
}
function updateConfiguration($setting, $value)
{
	$data["value"] = $value ?? "";
	$result = \think\Db::name("configuration")->whereRaw("setting = :setting", ["setting" => $setting])->find();
	if (!empty($result)) {
		$data["update_time"] = time();
		\think\Db::name("configuration")->whereRaw("setting = :setting", ["setting" => $setting])->update($data);
	} else {
		$data["create_time"] = time();
		$data["setting"] = $setting;
		\think\Db::name("configuration")->insertGetId($data);
	}
	return true;
}
function getEmailTemplateByType($type)
{
	$allowedType = ["general", "product", "invoice", "support", "notification"];
	if (in_array($type, $allowedType)) {
		$template = \think\Db::name("email_templates")->field("id,name")->where("type", $type)->select();
		return $template;
	} else {
		return false;
	}
}
function postRequest($url, $data, $refer = "", $timeout = 10, $header = [])
{
	$curlObj = curl_init();
	$ssl = stripos($url, "https://") === 0 ? true : false;
	$options = [CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => 1, CURLOPT_POST => 1, CURLOPT_POSTFIELDS => $data, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_AUTOREFERER => 1, CURLOPT_USERAGENT => "Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)", CURLOPT_TIMEOUT => $timeout, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_0, CURLOPT_HTTPHEADER => ["Expect:"], CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4, CURLOPT_REFERER => $refer, CURLOPT_COOKIE => ""];
	if (!empty($header)) {
		$options[CURLOPT_HTTPHEADER] = $header;
		$options[CURLOPT_USERAGENT] = $header["CURLOPT_USERAGENT"] ?? $options[CURLOPT_USERAGENT];
		$options[CURLOPT_COOKIE] = $header["CURLOPT_COOKIE"] ?? $options[CURLOPT_COOKIE];
	}
	if ($refer) {
		$options[CURLOPT_REFERER] = $refer;
	}
	if ($ssl) {
		$options[CURLOPT_SSL_VERIFYHOST] = false;
		$options[CURLOPT_SSL_VERIFYPEER] = false;
	}
	curl_setopt_array($curlObj, $options);
	$returnData = curl_exec($curlObj);
	if (curl_errno($curlObj)) {
		$returnData = curl_error($curlObj);
	}
	curl_close($curlObj);
	return $returnData;
}
function getRequest($url, $data, $refer = "", $timeout = 10, $header = [])
{
	$curlObj = curl_init();
	$ssl = stripos($url, "https://") === 0 ? true : false;
	$options = [CURLOPT_URL => $url . "?" . http_build_query($data), CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_AUTOREFERER => 1, CURLOPT_USERAGENT => "Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)", CURLOPT_TIMEOUT => $timeout, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_0, CURLOPT_HTTPHEADER => ["Expect:"], CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4, CURLOPT_REFERER => $refer, CURLOPT_COOKIE => "", CURLOPT_CUSTOMREQUEST => "GET"];
	if (!empty($header)) {
		$options[CURLOPT_HTTPHEADER] = $header;
		$options[CURLOPT_USERAGENT] = $header["CURLOPT_USERAGENT"] ?? $options[CURLOPT_USERAGENT];
		$options[CURLOPT_COOKIE] = $header["CURLOPT_COOKIE"] ?? $options[CURLOPT_COOKIE];
	}
	if ($refer) {
		$options[CURLOPT_REFERER] = $refer;
	}
	if ($ssl) {
		$options[CURLOPT_SSL_VERIFYHOST] = false;
		$options[CURLOPT_SSL_VERIFYPEER] = false;
	}
	curl_setopt_array($curlObj, $options);
	$returnData = curl_exec($curlObj);
	if (curl_errno($curlObj)) {
		$returnData = curl_error($curlObj);
	}
	curl_close($curlObj);
	return $returnData;
}
function getConfig($setting)
{
	if (is_array($setting)) {
		$data = \think\Db::name("configuration")->field("setting,value")->whereIn("setting", $setting)->select();
		$res = [];
		foreach ($data as $v) {
			$res[$v["setting"]] = $v["value"];
		}
	} else {
		$data = \think\Db::name("configuration")->field("setting,value")->where("setting", $setting)->find();
		$res = $data["value"];
	}
	return $res;
}
/**
 * 判断网关是否开通并激活
 */
function get_gateway_status($name)
{
	$where = ["status" => 1, "module" => "gateways", "name" => $name];
	$id = db("plugin")->where($where)->value("id");
	return $id;
}
/**
 * 可用【支付】网关列表
 */
function gateway_list($module = "gateways", $status = 1)
{
	if ($status != 1) {
		$where = ["module" => "gateways"];
	} else {
		$where = ["status" => $status, "module" => "gateways"];
	}
	$rows = \think\Db::name("plugin")->field("id,name,title,status,module,url,author_url")->where($where)->withAttr("url", function ($value, $data) {
		return "upload/pay/" . $data["name"] . ".png";
	})->withAttr("author_url", function ($value, $data) {
		if (file_exists(CMF_ROOT . "modules/gateways/" . cmf_parse_name($data["name"], 0) . "/" . $data["name"] . ".png")) {
			return base64EncodeImage(CMF_ROOT . "modules/gateways/" . cmf_parse_name($data["name"], 0) . "/" . $data["name"] . ".png");
		} else {
			return base64EncodeImage(CMF_ROOT . "public/plugins/gateways/" . cmf_parse_name($data["name"], 0) . "/" . $data["name"] . ".png");
		}
	})->order("order", "asc")->order("id", "asc")->select()->toArray();
	return $rows;
}
/**
 * 可用【支付】网关列表
 */
function gateway_list1($module = "gateways", $status = 1)
{
	if ($status != 1) {
		$where = ["module" => "gateways"];
	} else {
		$where = ["status" => $status, "module" => "gateways"];
	}
	$rows = db("plugin")->field("id,name,title,status,module")->where($where)->order("order", "asc")->order("id", "asc")->select()->toArray();
	return $rows;
}
/**
 * 验证处理支付
 */
function check_pay($data)
{
	return (new \app\home\controller\OrderController())->orderPayHandle($data);
}
/**
 *验证account信息
 */
function check_account_id($id)
{
	if (!empty($id)) {
		return db("accounts")->where("trans_id", $id)->value("id");
	} else {
		return 0;
	}
}
/**
 *获取开通的host_id
 */
function get_pay_host_id($id)
{
	return db("invoice_items")->where("invoice_id", $id)->distinct(true)->column("rel_id");
}
/**
 * 获取产品开通条件
 */
function get_product_condition($id)
{
	return db("products")->where("id", $id)->value("auto_setup");
}
function get_product_allowcycle($pid)
{
	$currency = \think\Db::name("currencies")->where("default", 1)->find();
	$currency_id = $currency["id"];
	$pricing_data = \think\Db::name("pricing")->where("type", "product")->where("relid", $pid)->where("currency", $currency_id)->find();
	if (empty($pricing_data)) {
		return [];
	}
	$allowcycle = [];
	$product_data = \think\Db::name("products")->field("pay_type")->where("id", $pid)->find();
	$pay_type = $product_data["pay_type"];
	$pay_type = json_decode($pay_type, true);
	if ($pay_type["pay_type"] == "free") {
		$allowcycle[] = "free";
	} else {
		if ($pricing_data["onetime"] >= 0) {
			$allowcycle[] = "onetime";
		}
		if ($pricing_data["hour"] >= 0) {
			$allowcycle[] = "hour";
		}
		if ($pricing_data["day"] >= 0) {
			$allowcycle[] = "day";
		}
		if ($pricing_data["ontrial"] >= 0) {
			$allowcycle[] = "ontrial";
		}
		if ($pricing_data["monthly"] >= 0) {
			$allowcycle[] = "monthly";
		}
		if ($pricing_data["quarterly"] >= 0) {
			$allowcycle[] = "quarterly";
		}
		if ($pricing_data["semiannually"] >= 0) {
			$allowcycle[] = "semiannually";
		}
		if ($pricing_data["annually"] >= 0) {
			$allowcycle[] = "annually";
		}
		if ($pricing_data["biennially"] >= 0) {
			$allowcycle[] = "biennially";
		}
		if ($pricing_data["triennially"] >= 0) {
			$allowcycle[] = "triennially";
		}
		if ($pricing_data["fourly"] >= 0) {
			$allowcycle[] = "fourly";
		}
		if ($pricing_data["fively"] >= 0) {
			$allowcycle[] = "fively";
		}
		if ($pricing_data["sixly"] >= 0) {
			$allowcycle[] = "sixly";
		}
		if ($pricing_data["sevenly"] >= 0) {
			$allowcycle[] = "sevenly";
		}
		if ($pricing_data["eightly"] >= 0) {
			$allowcycle[] = "eightly";
		}
		if ($pricing_data["ninely"] >= 0) {
			$allowcycle[] = "ninely";
		}
		if ($pricing_data["tenly"] >= 0) {
			$allowcycle[] = "tenly";
		}
	}
	return $allowcycle ?: [];
}
/**
 * 获取网关信息
 * @param $type
 * @return mixed
 */
function getGatewayInfo($type)
{
	$lowerName = lower_str_type($type);
	$res["gateways_dir"] = "gateways\\" . $lowerName . "\\" . $type . "Plugin";
	$res["typename"] = $lowerName . "_handle";
	$res["typename"] = cmf_parse_name($res["typename"], 1);
	return $res;
}
function getGatewayName($name)
{
	return strtolower(preg_replace("/(?<=[a-z])([A-Z])/", "_\$1", $name));
}
/**
 * 将时间戳转换为日期时间
 * @param int    $time   时间戳
 * @param string $format 日期时间格式
 * @return string
 */
function datetime($time, $format = "Y-m-d H:i:s")
{
	$time = is_numeric($time) ? $time : strtotime($time);
	return date($format, $time);
}
function search_users($name)
{
	return db("clients")->field("id,username")->where("username|phonenumber|email", "like", "%{$name}%")->limit(50)->select();
}
/**
 * 获取用户货币数组
 * @param int  $uid  用户id
 * @return array 用户货币数组
 */
function getUserCurrency($uid)
{
	$user_data = \think\Db::name("clients")->field("id,currency")->find($uid);
	$currency_data = [];
	if (!empty($user_data["currency"])) {
		$currency_data = \think\Db::name("currencies")->field("id,code,prefix,suffix")->where("id", $user_data["currency"])->find();
	}
	if (empty($currency_data)) {
		$currency_data = \think\Db::name("currencies")->field("id,code,prefix,suffix")->where("default", 1)->find();
	}
	return $currency_data;
}
/**
 * 获取所有货币
 */
function get_currency()
{
	$currencies = \think\Db::name("currencies")->field("id,code,prefix,suffix")->select()->toArray();
	foreach ($currencies as $key => $currency) {
		$filtercurrencies[$key] = array_map(function ($v) {
			return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
		}, $currency);
	}
	return $filtercurrencies;
}
/**
 * 获取用户货币
 */
function get_user_currency($invoice_id)
{
	$currencyId = db("invoices")->alias("i")->join("clients c", "c.id=i.uid")->where("i.id", $invoice_id)->value("i.currency");
	$currency_data = [];
	if (!empty($currencyId)) {
		$currency_data = \think\Db::name("currencies")->where("id", $currencyId)->find();
	}
	if (empty($currency_data)) {
		$currency_data = \think\Db::name("currencies")->where("default", 1)->find();
	}
	return $currency_data;
}
function aesPasswordEncode($data)
{
	$key = md5("shundai");
	$v = substr($key, 0, 8);
	$result = openssl_encrypt($data, "DES-CBC", $key, OPENSSL_RAW_DATA, $v);
	return base64_encode($result);
}
function aesPasswordDecode($data)
{
	$data = base64_decode($data);
	$key = md5("shundai");
	$v = substr($key, 0, 8);
	$result = openssl_decrypt($data, "DES-CBC", $key, OPENSSL_RAW_DATA, $v);
	return $result;
}
/**
 * 驼峰转下划线模式
 * @param $str
 * @return string
 */
function lower_str_type($str)
{
	return strtolower(preg_replace("/(?<=[a-z])([A-Z])/", "_\$1", $str));
}
function promoCodeDesc($data)
{
	$desc = "优惠码:" . $data["code"];
	if ($data["type"] == "percent") {
		$desc .= "- " . $data["value"] . "% ";
	} elseif ($data["type"] == "fixed") {
		$desc .= "- " . $data["value"] . " ";
	} elseif ($data["type"] == "override") {
		$desc .= "- 替换金额:" . $data["value"] . " ";
	} elseif ($data["type"] == "free") {
		$desc .= "- 免费安装 ";
	}
	if (!empty($data["recurring"])) {
		$desc .= "循环 折扣";
	} else {
		$desc .= "一次性 折扣";
	}
	return $desc;
}
/**
 * 友好时间显示
 * @param $time
 * @return bool|string
 */
function friend_date($time)
{
	if (!$time) {
		return false;
	}
	$fdate = "";
	$d = time() - intval($time);
	$ld = $time - mktime(0, 0, 0, 0, 0, date("Y"));
	$md = $time - mktime(0, 0, 0, date("m"), 0, date("Y"));
	$byd = $time - mktime(0, 0, 0, date("m"), date("d") - 2, date("Y"));
	$yd = $time - mktime(0, 0, 0, date("m"), date("d") - 1, date("Y"));
	$dd = $time - mktime(0, 0, 0, date("m"), date("d"), date("Y"));
	$td = $time - mktime(0, 0, 0, date("m"), date("d") + 1, date("Y"));
	$atd = $time - mktime(0, 0, 0, date("m"), date("d") + 2, date("Y"));
	if ($d == 0) {
		$fdate = "刚刚";
	} else {
		switch ($d) {
			case $d < $atd:
				$fdate = date("Y年m月d日", $time);
				break;
			case $d < $td:
				$fdate = "后天" . date("H:i", $time);
				break;
			case $d < 0:
				$fdate = "明天" . date("H:i", $time);
				break;
			case $d < 60:
				$fdate = $d . "秒前";
				break;
			case $d < 3600:
				$fdate = floor($d / 60) . "分钟前";
				break;
			case $d < $dd:
				$fdate = floor($d / 3600) . "小时前";
				break;
			case $d < $yd:
				$fdate = "昨天" . date("H:i", $time);
				break;
			case $d < $byd:
				$fdate = "前天" . date("H:i", $time);
				break;
			case $d < $md:
				$fdate = date("m月d日 H:i", $time);
				break;
			case $d < $ld:
				$fdate = date("m月d日", $time);
				break;
			default:
				$fdate = date("Y年m月d日", $time);
				break;
		}
	}
	return $fdate;
}
/**
 * 检查实名认证身份证号是否被其他用户使用
 * @param $idcard:身份证号
 * @param $clientid:客户ID
 * @return array
 */
function checkOtherUsed($idcard, $clientid)
{
	$re = [];
	$personalUsed = \think\Db::name("certifi_person")->whereRaw("auth_user_id !=:userid", ["userid" => $clientid])->whereRaw("auth_card_number =:idcard", ["idcard" => $idcard])->find();
	$companyUsed = \think\Db::name("certifi_company")->whereRaw("auth_user_id !=:userid", ["userid" => $clientid])->whereRaw("auth_card_number =:idcard", ["idcard" => $idcard])->find();
	if (!empty($personalUsed) || !empty($companyUsed)) {
		$re["status"] = 1001;
		return $re;
	}
	$re["status"] = 1000;
	return $re;
}
function base64EncodeImage($image_file)
{
	$base64_image = null;
	$image_info = getimagesize($image_file);
	$image_data = fread(fopen($image_file, "r"), filesize($image_file));
	if (!isset($image_data[0])) {
		return "";
	}
	$base64_image = "data:" . $image_info["mime"] . ";base64," . chunk_split(base64_encode($image_data));
	return $base64_image;
}
function svgBase64EncodeImage($image_file)
{
	$svg = file_get_contents($image_file);
	if (empty($svg)) {
		return "";
	}
	$str = rawurlencode($svg);
	$ret = "";
	$len = strlen($str);
	for ($i = 0; $i < $len; $i++) {
		if ($str[$i] == "%" && $str[$i + 1] == "u") {
			$val = hexdec(substr($str, $i + 2, 4));
			if ($val < 127) {
				$ret .= chr($val);
			} elseif ($val < 2048) {
				$ret .= chr(192 | $val >> 6) . chr(128 | $val & 63);
			} else {
				$ret .= chr(224 | $val >> 12) . chr(128 | $val >> 6 & 63) . chr(128 | $val & 63);
			}
			$i += 5;
		} else {
			if ($str[$i] == "%") {
				$ret .= urldecode(substr($str, $i, 3));
				$i += 2;
			} else {
				$ret .= $str[$i];
			}
		}
	}
	$base64_image = "data:image/svg+xml;base64," . base64_encode($ret);
	return $base64_image;
}
function priorityCurrency($uid, $currency = "")
{
	$exist = \think\Db::name("currencies")->where("id", $currency)->find();
	if (!empty($exist)) {
		return $currency;
	} else {
		$currency_client = \think\Db::name("clients")->where("id", $uid)->value("currency");
		$currency_default = db("currencies")->field("id")->where("default", 1)->value("id");
		return $currency_client ?? $currency_default;
	}
}
/**
 * 获取所有货币信息
 */
function getCurrencies()
{
	$currencies = \think\Db::name("currencies")->field("id,code,prefix,suffix")->select();
	foreach ($currencies as $key => $currency) {
		$filtercurrencies[$key] = array_map(function ($v) {
			return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
		}, $currency);
	}
	return $filtercurrencies;
}
function base64DecodeImage($base64_image_content, $path)
{
	if (preg_match("/^(data:\\s*image\\/(\\w+);base64,)/", $base64_image_content, $result)) {
		$type = $result[2];
		$new_file = $path;
		if (!file_exists($new_file)) {
			mkdir($new_file, 448);
		}
		$image = md5(uniqid()) . time() . ".{$type}";
		$new_file = $new_file . $image;
		if (file_put_contents($new_file, base64_decode(str_replace($result[1], "", $base64_image_content)))) {
			return $image;
		} else {
			return false;
		}
	} else {
		return false;
	}
}
function executePrivate($class, $fun, ...$args)
{
	$ref_class = new ReflectionClass($class);
	$instance = $ref_class->newInstance();
	$method = $ref_class->getmethod($fun);
	$method->setAccessible(true);
	$result = $method->invoke($instance, ...$args);
	return $result;
}
function getSupportedCycles($pid, $currency, $mon_pack_year = 1)
{
	$product_data = \think\Db::name("products")->field("pay_type,pay_method")->where("id", $pid)->find();
	if (empty($product_data)) {
		return [];
	}
	$pay_type = $product_data["pay_type"];
	$pay_method = $product_data["pay_method"];
	$pay_type_data = json_decode($pay_type, true);
	$cycle_data = [];
	$cycle_condition = [];
	$price_data = \think\Db::name("pricing")->where("type", "product")->where("relid", $pid)->where("currency", $currency)->find();
	$is_shop_cart = "";
	if ($price_data["ontrial"] >= 0 && $is_shop_cart && $mon_pack_year) {
		$cycle_data[] = "ontrial";
		$cycle_condition["ontrial"]["cycle"] = $pay_type_data["pay_ontrial_cycle"];
		$cycle_condition["ontrial"]["condition"] = $pay_type_data["pay_ontrial_condition"];
	}
	if ($pay_type_data["pay_type"] == "free" && $mon_pack_year) {
		$cycle_data[] = "free";
	} elseif ($pay_type_data["pay_type"] == "onetime" && $mon_pack_year) {
		if ($price_data["onetime"] >= 0) {
			$cycle_data[] = "onetime";
		}
	} else {
		if ($pay_type_data["pay_type"] == "recurring") {
			if ($price_data["hour"] >= 0 && $mon_pack_year) {
				$cycle_data[] = "hour";
				$cycle_condition["hour"]["cycle"] = $pay_type_data["pay_hour_cycle"];
			}
			if ($price_data["day"] >= 0 && $mon_pack_year) {
				$cycle_data[] = "day";
				$cycle_condition["day"]["cycle"] = $pay_type_data["pay_day_cycle"];
			}
			if ($price_data["monthly"] >= 0) {
				$cycle_data[] = "monthly";
			}
			if ($price_data["quarterly"] >= 0) {
				$cycle_data[] = "quarterly";
			}
			if ($price_data["semiannually"] >= 0) {
				$cycle_data[] = "semiannually";
			}
			if ($price_data["annually"] >= 0) {
				$cycle_data[] = "annually";
			}
			if ($price_data["biennially"] >= 0) {
				$cycle_data[] = "biennially";
			}
			if ($price_data["triennially"] >= 0) {
				$cycle_data[] = "triennially";
			}
			if ($price_data["fourly"] >= 0) {
				$cycle_data[] = "fourly";
			}
			if ($price_data["fively"] >= 0) {
				$cycle_data[] = "fively";
			}
			if ($price_data["sixly"] >= 0) {
				$cycle_data[] = "sixly";
			}
			if ($price_data["sevenly"] >= 0) {
				$cycle_data[] = "sevenly";
			}
			if ($price_data["eightly"] >= 0) {
				$cycle_data[] = "eightly";
			}
			if ($price_data["ninely"] >= 0) {
				$cycle_data[] = "ninely";
			}
			if ($price_data["tenly"] >= 0) {
				$cycle_data[] = "tenly";
			}
		}
	}
	$coupon_cycle = config("coupon_cycle");
	$cycle_desc_data = [];
	foreach ($cycle_data as $key => $val) {
		$cycle_desc_data[$key]["name"] = $val;
		$cycle_desc_data[$key]["desc"] = $coupon_cycle[$val];
	}
	$returndata["cycle_desc_data"] = $cycle_desc_data;
	$returndata["cycle_data"] = $cycle_data;
	$returndata["cycle_condition"] = $cycle_condition;
	return $returndata;
}
/**
 * @title 获取语言列表
 */
function get_language_list()
{
	return config("language.list");
}
function now()
{
	return date("Y-m-d H:i:s");
}
function getCurrencyRateByUid($uid)
{
	$default = \think\Db::name("currencies")->where("default", 1)->value("rate");
	$client = \think\Db::name("clients")->alias("a")->field("b.rate")->leftJoin("currencies b", "a.currency = b.id")->where("a.id", $uid)->find();
	if ($client["rate"]) {
		$rate = bcdiv($client["rate"], $default, 5);
	} else {
		$rate = 1;
	}
	return $rate;
}
/**
 * 时间 2020/5/27 15:11
 * @title 获取所有销售
 * @return  array $ret 返回所有销售集合
 * @author 菜鸟
 */
function get_sale()
{
	$where = [["is_sale", "=", 1], ["user_status", "=", 1]];
	$field = "id,user_nickname";
	$tmp = \think\Db::name("user")->where($where)->field($field)->select();
	$ret = $tmp ? $tmp->toArray() : [];
	array_unshift($ret, ["id" => 0, "user_nickname" => lang("NULL")]);
	return $ret;
}
/**
 * 处理日志中的可替换id
 * @param log_list:日志数组
 * @param desc_file:处理的字段
 */
function handle_log($log_list = [], $desc_file = "description")
{
	$log_relation = config("log_relation");
	foreach ($log_list as $key => $val) {
		$new_desc = "";
		$description = $val[$desc_file];
		$desc_array = explode("-", $description);
		foreach ($desc_array as $value) {
			$relation = explode(":", $value)[0];
			$id = explode(":", $value)[1];
			if ($id) {
				$relation = trim($relation);
				$id = trim($id);
				if (!empty($log_relation[$relation])) {
					$new_desc .= " - " . sprintf($log_relation[$relation], $id, $value);
				} else {
					$new_desc .= " - " . trim($value);
				}
			} else {
				$new_desc .= trim($value);
			}
		}
		$log_list[$key]["new_desc"] = $new_desc;
		unset($log_list[$key]["description"]);
	}
	return $log_list;
}
/**
 * @title 获取系统语言配置
 */
function get_system_lang()
{
	$lang = "zh-cn";
	return config("language.list.{$lang}");
}
/**
 * @title 获取系统语言配置
 */
function get_system_langs()
{
	$lang = "zh-cn";
	return config("lang.{$lang}");
}
/**
 * @author x
 * @title 添加系统活动日志
 * @description 在所有系统中关键节点操作上添加日志（订单创建，账单创建，产品修改，产品配置修改，货币相关，自动任务，邮件发送，模块执行，）
 * @param [description]:描述信息格式eg: "创建续费账单 - Invoice ID: 1 - Host ID: 1 - 错误：余额不足", (动作描述 - 关键ID: 1 - [执行错误的结果])
 * @param [userid]:此记录属于的用户id【客户端用户，后台系统用户】
 * @param [type]:日志类型  [0：默认类型日志] [1：注册登录日志]  [2：host相关日志]  [4：config相关][5：cron任务][6：账单相关日志]
 * @param [type_data_id]: type类型对应数据id [type为2【type_data_id保存：表host下id】] [type为6【type_data_id保存：表invoices下id】]
 * @param [from_type]:操作类型 默认：[1：后台] | [2：客户端] | [1,2之外全部划分为系统任务] (区分userid来于场景)
 * TODO:由于公共方法里面会存在此方法使用，而此参数默认是系统管理员操作，当前台用户行为触发公共方法，就会导致系统识别管理员标识为空，会误认为此操作日志为系统自主执行（解决方案：在处理前,进行一次标识的检测判定）
 * @other 统一ID记录， 账单id: Invoice ID, 订单id: Order ID, 主机id：Host ID, 用户id: User ID,子账户id:Contacts ID, 工单id:Ticket ID,
 * @other 产品id:Product ID, ip地址：IP, 服务器id:Service ID, 交易流水id:Transaction ID, 管理员id:Admin ID,角色id:Role ID
 * @other (如有其他，请在后面添加，描述务必写成示例格式，日志中相应ID会被替换成可跳转)
 * @other 用户日志读取本日志
 */
function active_log_final($description, $userid = 0, $type = 0, $type_data_id = 0, $from_type = 1)
{
	$remote_ip = get_client_ip6();
	$remote_port = get_remote_port();
	$session_id = session_id();
	$jwt = usergetcookie();
	$user_id = \think\facade\Cache::get("client_user_login_token_" . $jwt);
	$admin_id = cmf_get_current_admin_id();
	if (empty($description) || $description == "") {
		$module = \think\facade\Request::module();
		$controller = \think\facade\Request::controller();
		$action = \think\facade\Request::action();
		$rule = "app\\" . $module . "\\controller\\" . $controller . "controller::" . $action;
		if (empty($module) || empty($controller) || empty($action)) {
			$rule = request()->url();
		}
		$description = $rule;
	}
	if (strpos($description, "password") !== false) {
		$description = preg_replace("/(password(?:hash)?`=')(.*)(',|' )/", "\${1}--REDACTED--\${3}", $description);
	}
	if (empty($admin_id) && empty($user_id) && $remote_ip == "0.0.0.0") {
		$from_type = 0;
	} else {
		if (!empty($admin_id) && empty($user_id)) {
			$from_type = 1;
		} else {
			if (empty($admin_id) && !empty($user_id)) {
				$from_type = 2;
			} else {
				$module = \think\facade\Request::module();
				$domain = config("database.admin_application") ?? "admin";
				if ($module == "home" || $module == "openapi") {
					$from_type = 2;
				} else {
					if ($module == $domain) {
						$from_type = 1;
					}
				}
			}
		}
	}
	switch ($from_type) {
		case 1:
			$userid = $userid ?: 0;
			if (empty($admin_id)) {
				$admin_id = $userid;
			}
			$username = \think\Db::name("user")->where("id", $admin_id)->value("user_login");
			$usertype = "Admin";
			$activeid = $admin_id;
			break;
		case 2:
			$userid = $userid ?: $user_id;
			if (empty($user_id)) {
				$user_id = $userid;
			}
			$username = \think\Db::name("clients")->where("id", $user_id)->value("username");
			$usertype = "Client";
			$activeid = $user_id;
			break;
	}
	if (empty($username) || $remote_ip == "0.0.0.0") {
		$username = "System";
		$usertype = "System";
		$description = "Cron_" . $description;
	}
	$idata = ["create_time" => time(), "description" => $description, "user" => $username ?? "", "usertype" => $usertype ?? "", "uid" => $userid ?? 0, "ipaddr" => $remote_ip, "type" => $type, "activeid" => $activeid ?? 0, "port" => $remote_port, "type_data_id" => $type_data_id ?? 0];
	\think\Db::name("activity_log")->insert($idata);
	if ($from_type == 1) {
		$exists_data = \think\Db::name("admin_log")->where("sessionid", $session_id)->find();
		if (!empty($exists_data) && empty($exists_data["logouttime"])) {
			\think\Db::name("admin_log")->where("sessionid", $session_id)->update(["lastvisit" => time()]);
		}
	}
	hook("log_activity", ["description" => $description, "user" => $username, "uid" => intval($userid), "ipaddress" => $remote_ip]);
}
/**
 * @title 添加系统活动日志
 * @description 在所有系统中关键节点操作上添加日志（订单创建，账单创建，产品修改，产品配置修改，货币相关，自动任务，邮件发送，模块执行，）
 * @param [description]:描述信息格式eg: "创建续费账单 - Invoice ID: 1 - Host ID: 1 - 错误：余额不足", (动作描述 - 关键ID: 1 - [执行错误的结果])
 * @param [type]:日志类型  1登录  2主机  空
 * @param [table]:1后台  2前台
 * @other 统一ID记录， 账单id: Invoice ID, 订单id: Order ID, 主机id：Host ID, 用户id: User ID,子账户id:Contacts ID, 工单id:Ticket ID,
 * @other 产品id:Product ID, ip地址：IP, 服务器id:Service ID, 交易流水id:Transaction ID, 管理员id:Admin ID,角色id:Role ID
 * @other (如有其他，请在后面添加，描述务必写成示例格式，日志中相应ID会被替换成可跳转)
 * @param [userid]: 后台管理员操作时或系统任务操作时,涉及到用户相关,需要传递用户id
 * @other 用户日志读取本日志
 * type  是代表登录日志  还是host日志   table代表前端日志还是后端日志  flag代表外部日志还是系统日志
 */
function active_log($description, $userid = 0, $type = "", $table = 1, $flag = 1, $admin = false)
{
	if ($table == 1) {
		active_log_final($description, $userid);
	}
	return true;
	$uid = request()->uid ?: $userid;
	$remote_ip = get_client_ip6();
	$session_id = session_id();
	$admin_id = cmf_get_current_admin_id();
	if ($uid) {
		$username = \think\Db::name("clients")->where("id", $uid)->value("username");
	}
	if (empty($uid) && !is_null($admin_id)) {
		if (empty($uid) && $uid != 0) {
			$uid = $admin_id;
		}
		$admin_name = \think\Db::name("user")->field("user_login,user_nickname")->where("id", $admin_id)->find();
		$username = $admin_name["user_nickname"];
		$username1 = $admin_name["user_login"];
	}
	if ($admin) {
		$admin_name = \think\Db::name("user")->field("user_login,user_nickname")->where("id", $admin_id)->find();
		$username = $admin_name["user_nickname"];
		$username1 = $admin_name["user_login"];
	}
	if (empty($username)) {
		if ($flag == 2) {
			$username = "Outside";
		} else {
			$username = "System";
		}
	}
	if (empty($description) || $description == "") {
		$module = \think\facade\Request::module();
		$controller = \think\facade\Request::controller();
		$action = \think\facade\Request::action();
		$rule = "app\\" . $module . "\\controller\\" . $controller . "controller::" . $action;
		if (empty($module) || empty($controller) || empty($action)) {
			$rule = request()->url();
		}
		$description = $rule;
	}
	if (strpos($description, "password") !== false) {
		$description = preg_replace("/(password(?:hash)?`=')(.*)(',|' )/", "\${1}--REDACTED--\${3}", $description);
	}
	if ($username == "System") {
		$description = "Cron_" . $description;
	}
	$idata = ["create_time" => time(), "description" => $description, "user" => $username ?? "", "usertype" => $username1 ?? "", "uid" => $uid ?? "", "ipaddr" => $remote_ip, "type" => $type, "activeid" => $uid ?? 0];
	if ($table == 1) {
		\think\Db::name("activity_log")->insert($idata);
	} else {
		\think\Db::name("activity_log_home")->insert($idata);
	}
	if (!is_null($admin_id)) {
		$exists_data = \think\Db::name("admin_log")->where("sessionid", $session_id)->find();
		if (!empty($exists_data) && empty($exists_data["logouttime"])) {
			\think\Db::name("admin_log")->where("sessionid", $session_id)->update(["lastvisit" => time()]);
		}
	}
	hook("log_activity", ["description" => $description, "user" => $username, "uid" => intval($uid), "ipaddress" => $remote_ip]);
}
/**
 * @title 添加前台活动日志
 * @description 在所有系统中关键节点操作上添加日志（订单创建，账单创建，产品修改，产品配置修改，货币相关，自动任务，邮件发送，模块执行，）
 * @param [description]:描述信息格式eg: "创建续费账单 - Invoice ID: 1 - Host ID: 1 - 错误：余额不足", (动作描述 - 关键ID: 1 - [执行错误的结果])
 * @param [type]:日志类型  1登录  2主机  空
 * @param [table]:1后台  2前台
 * @other 统一ID记录， 账单id: Invoice ID, 订单id: Order ID, 主机id：Host ID, 用户id: User ID,子账户id:Contacts ID, 工单id:Ticket ID,
 * @other 产品id:Product ID, ip地址：IP, 服务器id:Service ID, 交易流水id:Transaction ID, 管理员id:Admin ID,角色id:Role ID
 * @other (如有其他，请在后面添加，描述务必写成示例格式，日志中相应ID会被替换成可跳转)
 * @param [userid]: 后台管理员操作时或系统任务操作时,涉及到用户相关,需要传递用户id
 * @other 用户日志读取本日志
 */
function active_logs($description, $userid = 0, $type = "", $table = 1)
{
	if ($table == 1) {
		active_log_final($description, $userid, 0, 0, 2);
	}
	return true;
	$uid = request()->uid ?: $userid;
	$contact_id = null;
	$remote_ip = get_client_ip6();
	$session_id = session_id();
	if (!is_null($uid)) {
		$name = \think\Db::name("clients")->where("id", $uid)->value("username");
		$username = $name;
		$username1 = "Client";
	} else {
		$username = "System";
		$username1 = "System";
	}
	if (empty($description) || $description == "") {
		$module = \think\facade\Request::module();
		$controller = \think\facade\Request::controller();
		$action = \think\facade\Request::action();
		$rule = "app\\" . $module . "\\controller\\" . $controller . "controller::" . $action;
		return null;
	}
	if (strpos($description, "password") !== false) {
		$description = preg_replace("/(password(?:hash)?`=')(.*)(',|' )/", "\${1}--REDACTED--\${3}", $description);
	}
	if ($username == "System") {
		$description = "Cron_" . $description;
	}
	$idata = ["create_time" => time(), "description" => $description, "user" => $username ?? "", "usertype" => $username1 ?? "", "uid" => $uid ?? "", "ipaddr" => $remote_ip, "type" => $type, "activeid" => $uid ?? 0];
	if ($table == 1) {
		\think\Db::name("activity_log")->insert($idata);
	} else {
		\think\Db::name("activity_log_home")->insert($idata);
	}
	hook("log_activity", ["description" => $description, "user" => $username, "uid" => intval($uid), "ipaddress" => $remote_ip]);
}
/**
 * @title 添加系统活动日志【弃用】
 * @description 在所有系统中关键节点操作上添加日志（订单创建，账单创建，产品修改，产品配置修改，货币相关，自动任务，邮件发送，模块执行，）
 * @param [description]:描述信息格式eg: "创建续费账单 - Invoice ID: 1 - Host ID: 1 - 错误：余额不足", (动作描述 - 关键ID: 1 - [执行错误的结果])
 * @param [type]:日志类型  1登录  2主机  空
 * @param [table]:1后台  2前台
 * @other 统一ID记录， 账单id: Invoice ID, 订单id: Order ID, 主机id：Host ID, 用户id: User ID,子账户id:Contacts ID, 工单id:Ticket ID,
 * @other 产品id:Product ID, ip地址：IP, 服务器id:Service ID, 交易流水id:Transaction ID, 管理员id:Admin ID,角色id:Role ID
 * @other (如有其他，请在后面添加，描述务必写成示例格式，日志中相应ID会被替换成可跳转)
 * @param [userid]: 后台管理员操作时或系统任务操作时,涉及到用户相关,需要传递用户id
 * @other 用户日志读取本日志
 */
function active_loglogin($description, $userid = 0, $type = "", $table = 1)
{
	$uid = request()->uid ?: $userid;
	$remote_ip = get_client_ip6();
	$session_id = session_id();
	$admin_name = \think\Db::name("user")->field("user_login,user_nickname")->where("id", $userid)->find();
	$username = $admin_name["user_nickname"];
	$username1 = $admin_name["user_login"];
	if (empty($description) || $description == "") {
		$module = \think\facade\Request::module();
		$controller = \think\facade\Request::controller();
		$action = \think\facade\Request::action();
		$rule = "app\\" . $module . "\\controller\\" . $controller . "controller::" . $action;
		if (empty($module) || empty($controller) || empty($action)) {
			$rule = request()->url();
		}
		$description = $rule;
		return null;
	}
	if (strpos($description, "password") !== false) {
		$description = preg_replace("/(password(?:hash)?`=')(.*)(',|' )/", "\${1}--REDACTED--\${3}", $description);
	}
	if ($username == "System") {
		$description = "Cron_" . $description;
	}
	$idata = ["create_time" => time(), "description" => $description, "user" => $username ?? "", "usertype" => $username1 ?? "", "uid" => $uid ?? "", "ipaddr" => $remote_ip, "type" => $type, "activeid" => $uid ?? 0];
	if ($table == 1) {
		\think\Db::name("activity_log")->insert($idata);
	} else {
		\think\Db::name("activity_log_home")->insert($idata);
	}
	if (!is_null($userid)) {
		$exists_data = \think\Db::name("admin_log")->where("sessionid", $session_id)->find();
		if (!empty($exists_data) && empty($exists_data["logouttime"])) {
			\think\Db::name("admin_log")->where("sessionid", $session_id)->update(["lastvisit" => time()]);
		}
	}
	hook("log_activity", ["description" => $description, "user" => $username, "uid" => intval($uid), "ipaddress" => $remote_ip]);
}
function logLink($description, $uid = "", $admin = 0)
{
	$pattern = "/(?P<name>\\w+ ID): (?P<digit>\\d+)/";
	preg_match_all($pattern, $description, $matches);
	$name = $matches["name"];
	$digit = $matches["digit"];
	if (!empty($name)) {
		foreach ($name as $k => $v) {
			$relid = $digit[$k];
			$str = $v . ": " . $relid;
			if ($v == "Invoice ID") {
			} elseif ($v == "User ID") {
				$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/customer-view/abstract?id=" . $relid . "\"><span class=\"el-link--inner\">" . $str . "</span></a>";
				$description = str_replace($str, $url, $description);
			} elseif ($v == "Host ID") {
				$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/customer-view/product-innerpage?id=" . $uid . "\"><span class=\"el-link--inner\">" . $str . "</span></a>";
				$description = str_replace($str, $url, $description);
			} elseif ($v == "Order ID") {
			} elseif ($v == "Host ID") {
			} elseif ($v == "Admin ID") {
				$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/admin-management?id=" . $relid . "\"><span class=\"el-link--inner\">" . $str . "</span></a>";
				$description = str_replace($str, $url, $description);
			} elseif ($v == "Contacts ID") {
			} elseif ($v == "Ticket ID") {
			} elseif ($v == "Product ID") {
			} elseif ($v == "IP") {
			} elseif ($v == "Service ID") {
			} elseif ($v == "Transaction ID") {
			} elseif ($v == "Role ID") {
				$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/permissions-managment?id=" . $relid . "\"><span class=\"el-link--inner\">" . $str . "</span></a>";
				$description = str_replace($str, $url, $description);
			}
		}
		return $description;
	} else {
		return $description;
	}
}
/**
 * @title 管理员登录日志
 * @description 后台用户登录成功后设置session后调用一次，用户登出，注销session前执行一次
 */
function admin_log()
{
	$admin_id = cmf_get_current_admin_id();
	$session_id = session_id();
	$remote_ip = get_client_ip();
	$username = "";
	if (empty($admin_id) || empty($session_id)) {
		return null;
	}
	$admin_name = \think\Db::name("user")->where("id", $admin_id)->value("user_login");
	$username = $admin_name;
	$exists_data = \think\Db::name("admin_log")->where("sessionid", $session_id)->find();
	if (!empty($exists_data) && empty($exists_data["logouttime"])) {
		\think\Db::name("admin_log")->where("sessionid", $session_id)->update(["lastvisit" => time(), "logouttime" => 0]);
	} else {
		$idata = ["admin_username" => $username, "logintime" => time(), "ipaddress" => $remote_ip, "sessionid" => $session_id, "port" => get_remote_port()];
		\think\Db::name("admin_log")->insert($idata);
	}
}
/**
 * @title 管理员登出日志
 * @description 后台用户登录成功后设置session后调用一次，用户登出，注销session前执行一次
 */
function admin_log1()
{
	$admin_id = cmf_get_current_admin_id();
	$session_id = session_id();
	$remote_ip = get_client_ip();
	$username = "";
	if (empty($admin_id) || empty($session_id)) {
		return null;
	}
	$admin_name = \think\Db::name("user")->where("id", $admin_id)->value("user_login");
	$username = $admin_name;
	$exists_data = \think\Db::name("admin_log")->where("sessionid", $session_id)->find();
	if (!empty($exists_data)) {
		\think\Db::name("admin_log")->where("sessionid", $session_id)->update(["sessionid" => $session_id . "-" . time(), "lastvisit" => time(), "logouttime" => time()]);
	} else {
		$idata = ["admin_username" => $username ?? "", "logintime" => time(), "ipaddress" => $remote_ip ?? "", "sessionid" => $session_id ?? "", "port" => get_remote_port()];
		\think\Db::name("admin_log")->insert($idata);
	}
}
/**
 * @title 通知日志
 * @param .name:message type:string default: require:0 other: desc:信息内容
 * @param .name:to type:string default: require:0 other: desc:类型为sms时为手机号(带地区号eg: +86.1234567890),类型为email时为邮箱,类型为微信时，看着办
 * @param .name:type type:string default: require:0 other: desc:邮件(email), 短信(sms), 微信通知(wechat)
 * @param .name:subject type:string default: require:0 other: desc:主题(发送模板的名称)
 * @param .name:userid type:int default: require:0 other: desc:不传会获取gwt中的uid，默认0
 * @param .name:cc type:int default:email require:0 other: desc:抄送
 * @param .name:bcc type:int default:email require:0 other: desc:抄送
 */
function notify_log($message = "", $to = "", $type = "email", $subject = "", $userid = 0, $cc = "", $bcc = "")
{
	$uid = request()->uid ?: $userid;
	$idata = ["create_time" => time(), "to" => $to, "message" => $message, "type" => $type, "subject" => $subject, "uid" => $uid, "cc" => $cc, "bcc" => $bcc];
	\think\Db::name("notify_log")->insertGetId($idata);
}
/**
 * @title 网关日志/错误日志
 * @param .name:gateway type:string default: require:0 other: desc:网关名
 * @param .name:data type:string default: require:0 other: desc:需要记录的访问数据(json)
 * @param .result:gateway type:string default: require:0 other: desc:描述/错误信息
 */
function gateway_log($gateway = "", $data = "", $result = "")
{
	$idata = ["create_time" => time(), "gateway" => $gateway, "data" => $data, "result" => $result];
	\think\Db::name("gateway_log")->insertGetId($idata);
}
/**
 * @title 网关支付日志
 * @author 上官🔪
 * @param .name:invoice_id type:int require:1 default:0 other: desc:支付的账单id
 * @param .name:trans_id type:int require:1 default:0 other: desc:支付的账单id
 * @param .name:payment type:string require:1 default:0 other: desc:支付网关名
 * @param .name:amount type:float require:1 default:0 other: desc:金额
 * @param .name:status type:string require:1 default:0 other: desc:状态
 * @param .name:description type:string require:0 default:0 other: desc:描述
 */
function payment_log($id, $currency, $trans_id, $payment, $amount, $status, $description = "")
{
	$log = db("pay_log")->where("invoice_id", $id)->find();
	if (empty($log)) {
		$data = ["invoice_id" => $id ?? 0, "currency" => $currency ?? "", "trans_id" => $trans_id ?? 0, "create_time" => time(), "payment" => $payment ?? "", "amount" => $amount ?? 0, "status" => $status ?? "success", "description" => $description ?? ""];
		db("pay_log")->insert($data);
	}
}
/**
 * @title 余额变动日志
 * @author 上官🔪
 * @param .name:invoice_id type:int require:1 default:0 other: desc:支付的账单id
 * @param .name:payment type:string require:1 default:0 other: desc:支付网关名
 * @param .name:amount type:float require:1 default:0 other: desc:金额
 * @param .name:rel_id type:string require:1 default:0 other: desc:关联id
 */
function _credit_log($uid, $description, $amount, $rel_id = 0, $notes = "")
{
	$data = ["uid" => $uid, "description" => $description, "amount" => $amount, "create_time" => time(), "relid" => $rel_id, "notes" => $notes ?: ""];
	return \think\Db::name("credit")->insert($data);
}
/**
 * @title 余额变动(增加每次约变动之后，账户所剩余额)
 * @param $data
 * @return int|string
 */
function credit_log($data)
{
	$client_credit = \think\Db::name("clients")->where("id", $data["uid"])->value("credit");
	return \think\Db::name("credit")->insert(["uid" => $data["uid"], "description" => $data["desc"], "amount" => $data["amount"], "relid" => $data["relid"] ?? 0, "notes" => $data["notes"] ?? "", "balance" => $client_credit, "create_time" => time(), "aff_refund" => $data["aff_refund"] ?? 0]);
}
/**
 * 转换货币
 * @param $amount:金额
 * @param $from: 原的货币id
 * @param $to: 转化的用户货币id
 * @param string $base_currency_exchange_rate
 * @return float|string
 * @throws
 */
function convert_currency($amount, $from, $to, $base_rate = "")
{
	if (!$base_rate) {
		$base_rate = db("currencies")->where("id", $from)->value("rate") ?? 1;
	}
	$convertto_rate = db("currencies")->where("id", $to)->value("rate") ?? 1;
	$convertto_amount = round($amount / $base_rate * $convertto_rate, 2);
	return $convertto_amount;
}
/**
 * 客户关怀：注册用户超过XX天未登录
 * @param
 */
function registerLongTimeNoSee()
{
	$clientCares = \think\Db::name("client_care")->field("id,name,trigger,time,method,email_template_id,message_template_id,wechat_template_id,range_type")->where("trigger", "register_surpass")->where("status", 1)->order("id desc")->select();
	if (!empty($clientCares[0])) {
		foreach ($clientCares as $k => $clientCare) {
			$time = isset($clientCare["time"]) ? $clientCare["time"] : 0;
			$method = isset($clientCare["method"]) ? $clientCare["method"] : "";
			$clients = \think\Db::name("clients")->field("id,lastlogin")->select();
			$informClients = [];
			foreach ($clients as $client) {
				$lastLogin = $client["lastlogin"];
				$addTime = strtotime("+" . $time . " days", $lastLogin);
				if ($addTime <= time()) {
					array_push($informClients, $client["id"]);
				}
			}
			if ($method) {
				$methodArray = explode(",", $method);
				if (in_array("email", $methodArray)) {
					$email = new \app\common\logic\Email();
					$email->batchSendEmailBase($informClients, "general");
				}
				if (in_array("message", $methodArray)) {
				}
			}
		}
	}
	return true;
}
/**
 * 客户关怀：注册用户但未下单XX天
 * @param
 */
function registerNoOrder()
{
	$clientCares = \think\Db::name("client_care")->field("id,name,trigger,time,method,email_template_id,message_template_id,wechat_template_id,range_type")->where("trigger", "register_surpass")->where("status", 1)->order("id desc")->select();
	if (!empty($clientCares[0])) {
		foreach ($clientCares as $k => $clientCare) {
			$time = isset($clientCare["time"]) ?: 0;
			$method = isset($clientCare["method"]) ?: "";
			$clients = \think\Db::name("clients")->field("id,create_time")->select();
			$clientsArray = [];
			foreach ($clients as $client) {
				array_push($clientsArray, $client["id"]);
			}
			$hosts = \think\Db::name("host")->field("uid")->select();
			$hostArray = [];
			foreach ($hosts as $host) {
				array_push($hostArray, $host["uid"]);
			}
			$diff = array_diff($clientsArray, $hostArray);
			foreach ($diff as $client) {
				$createTime = $client["create_time"];
				$addTime = strtotime("+" . $time . " days", $createTime);
				if ($addTime <= time()) {
					array_push($informClients, $client);
				}
			}
			if ($method) {
				$methodArray = explode(",", $method);
				if (in_array("email", $methodArray)) {
					$email = new \app\common\logic\Email();
					$email->batchSendEmailBase($informClients, "general");
				}
				if (in_array("message", $methodArray)) {
				}
			}
		}
	}
	return true;
}
/**
 * 客户关怀：注册用户下单未支付XX天
 * @param
 */
function registerAndOrderNoPay()
{
	$clientCares = \think\Db::name("client_care")->field("id,name,trigger,time,method,email_template_id,message_template_id,wechat_template_id,range_type")->where("trigger", "register_surpass")->where("status", 1)->order("id desc")->select();
	if (!empty($clientCares[0])) {
		foreach ($clientCares as $k => $clientCare) {
			$method = isset($clientCare["method"]) ?: "";
			$clients = \think\Db::name("clients")->field("id")->select();
			$clientsArray = [];
			foreach ($clients as $client) {
				array_push($clientsArray, $client["id"]);
			}
			$hosts = \think\Db::name("host")->field("uid")->select();
			$hostArray = [];
			foreach ($hosts as $host) {
				array_push($hostArray, $host["uid"]);
			}
			$informClients = array_diff($clientsArray, $hostArray);
			if ($method) {
				$methodArray = explode(",", $method);
				if (in_array("email", $methodArray)) {
					$email = new \app\common\logic\Email();
					$email->batchSendEmailBase($informClients, "general");
				}
				if (in_array("message", $methodArray)) {
				}
			}
		}
	}
	return true;
}
function userErrorHandler($errno, $errmsg, $filename, $linenum, $vars)
{
	$dt = date("Y-m-d H:i:s (T)");
	$errortype = [1 => "Error", 2 => "Warning", 4 => "Parsing Error", 8 => "Notice", 16 => "Core Error", 32 => "Core Warning", 64 => "Compile Error", 128 => "Compile Warning", 256 => "User Error", 512 => "User Warning", 1024 => "User Notice", 2048 => "Runtime Notice", 4096 => "Catchable Fatal Error"];
	$user_errors = [256, 512, 1024];
	$err = "<errorentry>\n";
	$err .= "\t<datetime>" . $dt . "</datetime>\n";
	$err .= "\t<errornum>" . $errno . "</errornum>\n";
	$err .= "\t<errortype>" . $errortype[$errno] . "</errortype>\n";
	$err .= "\t<errormsg>" . $errmsg . "</errormsg>\n";
	$err .= "\t<scriptname>" . $filename . "</scriptname>\n";
	$err .= "\t<scriptlinenum>" . $linenum . "</scriptlinenum>\n";
	if (in_array($errno, $user_errors)) {
		$err .= "\t<vartrace>" . wddx_serialize_value($vars, "Variables") . "</vartrace>\n";
	}
	$err .= "</errorentry>\n\n";
	error_log($err, 3, "../data/user_log/error.log");
}
function promoShow($code, $currencyid = "")
{
	if (empty($currencyid)) {
		$currency = \think\Db::name("currencies")->field("prefix", "suffix")->where("default", 1)->find();
	} else {
		$currency = \think\Db::name("currencies")->field("prefix", "suffix")->where("id", $currencyid)->find();
	}
	$promo = \think\Db::name("promo_code")->where("code", $code)->find();
	if (!$promo) {
		return "";
	}
	$show = "";
	$promo_cy = $promo["recurring"] == 1 ? " 循环" : " 一次性";
	if ($promo["type"] == "percent") {
		$show .= $promo["code"] . "-" . (100 - $promo["value"]) . "%" . $promo_cy;
	} elseif ($promo["type"] == "fixed") {
		$show .= $promo["code"] . "-" . $currency["prefix"] . $promo["value"] . $promo["suffix"] . $promo_cy;
	} elseif ($promo["type"] == "override") {
		$show .= $promo["code"] . "-" . $currency["prefix"] . $promo["value"] . $promo["suffix"] . " 置换价格";
	} else {
		$show .= "免费安装";
	}
	return $show;
}
function getAllPromoData($expiration = false)
{
	$currency = \think\Db::name("currencies")->field("id,code,prefix,suffix")->where("default", 1)->find();
	$prefix = $currency["prefix"];
	$suffix = $currency["suffix"];
	$promo_data = \think\Db::name("promo_code")->field("id,code,type,recurring,value,recurring,expiration_time")->where(function (\think\db\Query $query) use($expiration) {
		$time = time();
		if (!$expiration) {
			$query->where("expiration_time > {$time} or expiration_time = 0");
		}
	})->select()->toArray();
	$promo_code_filter = [];
	foreach ($promo_data as $key => $value) {
		$promo_desc = $value["code"] . " - ";
		$promo_value = $value["value"];
		$promo_cy = $value["recurring"] == 1 ? " 循环" : " 一次性";
		switch ($value["type"]) {
			case "percent":
				$promo_desc .= $promo_value . "%" . $promo_cy;
				break;
			case "fixed":
				$promo_desc .= $prefix . $promo_value . $suffix . $promo_cy;
				break;
			case "override":
				$promo_desc .= $prefix . $promo_value . $suffix . " 置换价格";
				break;
			case "free":
				$promo_desc .= " 免费安装";
				break;
		}
		$value["promo_desc"] = $promo_desc;
		unset($value["type"]);
		unset($value["recurring"]);
		unset($value["value"]);
		if ($expiration) {
			if ($value["expiration_time"] > time() || $value["expiration_time"] == 0) {
				unset($value["expiration_time"]);
				$promo_code_filter["Active"][] = $value;
			} else {
				unset($value["expiration_time"]);
				$promo_code_filter["Expired"][] = $value;
			}
		} else {
			unset($value["expiration_time"]);
			$promo_code_filter[] = $value;
		}
	}
	return $promo_code_filter;
}
function values2keys($arr, $value = 1)
{
	$new = [];
	while (list($k, $v) = each($arr)) {
		$v = trim($v);
		if ($v != "") {
			$new[$v] = $value;
		}
	}
	return $new;
}
function getClientsList()
{
	$groups = \think\Db::name("client_groups")->field("id,group_name")->select()->toArray();
	foreach ($groups as $k => $v) {
		$groupid = $v["id"];
		$groups[$k]["client"] = db("clients")->field("id,username")->where("groupid", $groupid)->select()->toArray();
	}
	return $groups;
}
function getProductList($type = 0, $id = 0)
{
	$groups = get_product_groups();
	$params["type"] = $type;
	$params["id"] = $id;
	$fun = function (\think\db\Query $query) use($params) {
		if (isset($params["type"])) {
			$id = $params["id"];
			$query->where("id", "<>", $id);
		}
	};
	foreach ($groups as $k => $v) {
		$groupid = $v["id"];
		$groups[$k]["product"] = db("products")->field("id,name,type,api_type")->withAttr("type", function ($value) {
			return config("product_type")[$value];
		})->where($fun)->where("gid", $groupid)->where("retired", 0)->order("order", "asc")->select()->toArray();
	}
	return $groups;
}
function getProductLists($is_resource = false, $pid = 0)
{
	try {
		$groups = get_product_group();
		$pro = db("sale_products")->field("pid,gid")->select()->toArray();
		$arr = "";
		foreach ($pro as $k => $val) {
			if ($k = 0) {
				$arr .= $val["pid"];
			} else {
				$arr .= "," . $val["pid"];
			}
		}
		$where = function (\think\db\Query $query) use($is_resource, $pid) {
			if ($is_resource) {
				$query->where("resource_pid", "=", 0);
			}
			if ($pid) {
				$query->whereOr("id", $pid);
			}
		};
		foreach ($groups as $k => $v) {
			$groupid = $v["id"];
			$products = db("products")->field("id,gid as pid,name,type")->where("gid", $groupid)->where("id", "not in", $arr)->where($where)->select()->toArray();
			$groups[$k]["pid"] = 0;
			$groups[$k]["children"] = $products;
		}
		return $groups;
	} catch (Exception $e) {
		return "";
	}
}
function getuserProductLists()
{
	try {
		$groups = get_product_group();
		$pro = db("user_products")->field("pid,gid")->select()->toArray();
		$arr = "";
		foreach ($pro as $k => $val) {
			if ($k = 0) {
				$arr .= $val["pid"];
			} else {
				$arr .= "," . $val["pid"];
			}
		}
		foreach ($groups as $k => $v) {
			$groupid = $v["id"];
			$products = db("products")->field("id,gid as pid,name")->where("gid", $groupid)->where("id", "not in", $arr)->select()->toArray();
			$groups[$k]["pid"] = 0;
			$groups[$k]["children"] = $products;
		}
		return $groups;
	} catch (Exception $e) {
		return "";
	}
}
function getUserProductListss($id)
{
	try {
		$groups = get_product_group();
		$pro = db("user_products")->field("pid,gid")->where("gid", "neq", $id)->select()->toArray();
		$arr = "";
		foreach ($pro as $k => $val) {
			if ($k = 0) {
				$arr .= $val["pid"];
			} else {
				$arr .= "," . $val["pid"];
			}
		}
		foreach ($groups as $k => $v) {
			$groupid = $v["id"];
			$products = db("products")->field("id,gid as pid,name")->where("gid", $groupid)->where("id", "not in", $arr)->select()->toArray();
			$groups[$k]["pid"] = 0;
			$groups[$k]["children"] = $products;
		}
		return $groups;
	} catch (Exception $e) {
		return "";
	}
}
function getProductListss($id)
{
	try {
		$groups = get_product_group();
		$pro = db("sale_products")->field("pid,gid")->where("gid", "neq", $id)->select()->toArray();
		$arr = "";
		foreach ($pro as $k => $val) {
			if ($k = 0) {
				$arr .= $val["pid"];
			} else {
				$arr .= "," . $val["pid"];
			}
		}
		foreach ($groups as $k => $v) {
			$groupid = $v["id"];
			$products = db("products")->field("id,gid as pid,name")->where("gid", $groupid)->where("id", "not in", $arr)->select()->toArray();
			$groups[$k]["pid"] = 0;
			$groups[$k]["children"] = $products;
		}
		return $groups;
	} catch (Exception $e) {
		return "";
	}
}
function getProductListbypid($ids)
{
	try {
		$groups = db("product_groups")->alias("c")->field("c.id,c.name")->join("products p", "p.gid=c.id")->where(function (\think\db\Query $query) use($ids) {
			if (!empty($ids)) {
				$query->where("p.id", "in", $ids);
			}
		})->group("c.id")->select()->toArray();
		foreach ($groups as $k => $v) {
			$groupid = $v["id"];
			$groups[$k]["pid"] = 0;
			$groups[$k]["name"] = $v["name"];
			$groups[$k]["children"] = db("products")->alias("c")->field("c.id,c.name,c.gid")->join("product_groups p", "p.id=c.gid")->where("p.id", $v["id"])->where(function (\think\db\Query $query) use($ids) {
				if (!empty($ids)) {
					$query->where("c.id", "in", $ids);
				}
			})->select()->toArray();
		}
		return $groups;
	} catch (Exception $e) {
		return "";
	}
}
function ip_to_address($ip)
{
	$url = "http://ip-api.com/json/" . $ip . "?lang=zh-CN";
	$ret = get_data($url);
	if ($ret["status"] === "success") {
		return $ret["country"] . $ret["city"];
	}
	return "未知";
}
function login_sms_remind($data)
{
	$ip = get_client_ip(0, true);
	$param = [];
	$params["username"] = $data["username"];
	$params["account"] = $data["username"];
	$params["time"] = date("Y-m-d H:i:s");
	$params["address"] = $ip;
	$param["id"] = $data["id"];
	$resource = \think\Db::name("clients")->where("usertype", 3)->where("id", $data["id"])->find();
	if (!empty($resource)) {
		return false;
	}
	$arr_client = ["relid" => $data["id"], "name" => "登录提醒", "type" => "general", "sync" => true, "admin" => false, "ip" => $ip];
	if (!empty($data["email_remind"])) {
		$curl_multi_data[0] = ["url" => "async", "data" => $arr_client];
		asyncCurlMulti($curl_multi_data);
	}
	if ($data["phone_code"] > 0 && isset($data["phonenumber"][0]) && $data["is_login_sms_reminder"] == 1) {
		if ($data["phone_code"] == "+86" || $data["phone_code"] == "86") {
			$phone = $data["phonenumber"];
		} else {
			if (substr($data["phone_code"], 0, 1) == "+") {
				$phone = substr($data["phone_code"], 1) . "-" . $data["phonenumber"];
			} else {
				$phone = $data["phone_code"] . "-" . $data["phonenumber"];
			}
		}
		$message_template_type = array_column(config("message_template_type"), "id", "name");
		$sms = new \app\common\logic\Sms();
		$client = check_type_is_use($message_template_type[strtolower("login_sms_remind")], $data["id"], $sms);
		if ($client) {
			$tmp = $sms->sendSms($message_template_type[strtolower("login_sms_remind")], $phone, $params, false, $data["id"]);
			\think\facade\Log::record($tmp, "login_phone_remind");
		}
	}
}
/**
 * @param string $uid 接收人id
 * @param string $subject 主题
 * @param string $message 消息
 * @param string $to 接收人邮箱
 * @param string $cc
 * @param string $bcc
 * @param int $status 状态
 * @param string $fail_reason 失败理由
 * @param int $is_admin 是否是管理员
 * @param string $attachments 附件，以逗号分隔
 * @return int|string
 */
function email_log($uid = "", $subject = "", $message = "", $to = "", $cc = "", $bcc = "", $status = 1, $fail_reason = "", $is_admin = 0, $attachments = "", $ip = "")
{
	return \think\Db::name("email_log")->insert(["uid" => $uid, "subject" => $subject, "message" => $message, "create_time" => time(), "to" => $to, "cc" => $cc ?? "", "bcc" => $bcc ?? "", "status" => $status, "fail_reason" => empty($fail_reason) ? "" : $fail_reason, "is_admin" => $is_admin, "attachments" => $attachments, "ip" => !empty($ip) ? $ip : get_client_ip6(), "port" => get_remote_port()]);
}
function getDefaultCurrencyId()
{
	return \think\Db::name("currencies")->where("default", 1)->value("id");
}
function batch_curl_post($data, $timeout = 30)
{
	$queue = curl_multi_init();
	$map = [];
	foreach ($data as $k => $v) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $v["url"]);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($v["data"]));
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_NOSIGNAL, true);
		curl_multi_add_handle($queue, $ch);
		$map[$k] = $ch;
	}
	$active = null;
	do {
		$mrc = curl_multi_exec($queue, $active);
	} while ($mrc == CURLM_CALL_MULTI_PERFORM);
	while ($active > 0 && $mrc == CURLM_OK) {
		if (curl_multi_select($queue, 1) != -1) {
			do {
				$mrc = curl_multi_exec($queue, $active);
			} while ($mrc == CURLM_CALL_MULTI_PERFORM);
		}
	}
	$responses = [];
	foreach ($map as $k => $ch) {
		$res = curl_multi_getcontent($ch);
		$info = curl_getinfo($ch);
		$error = curl_error($ch);
		$curl_errno = curl_errno($ch);
		if (!empty($error)) {
			$output["status"] = 500;
			$output["msg"] = $error;
			$responses[$k] = $output;
		} else {
			$responses[$k] = ["status" => 200, "http_code" => $info["http_code"], "data" => json_decode($res, true)];
		}
		curl_multi_remove_handle($queue, $ch);
		curl_close($ch);
	}
	curl_multi_close($queue);
	return $responses;
}
function check_dcim_auth($hostid, $uid, $action)
{
	$host = \think\Db::name("host")->alias("a")->field("a.domainstatus,a.reinstall_info,a.uid,a.dcimid,b.api_type,b.zjmf_api_id,c.buy_times,c.reinstall_times,c.reinstall_price,c.auth")->leftJoin("products b", "a.productid=b.id")->leftJoin("dcim_servers c", "a.serverid=c.serverid")->where("a.uid", $uid)->where("a.id", $hostid)->where("b.type", "dcim")->find();
	if (empty($host)) {
		$result["status"] = 400;
		$result["msg"] = lang("ID_ERROR");
		return $result;
	}
	if ($host["domainstatus"] != "Active") {
		$result["status"] = 400;
		$result["msg"] = "没有权限";
		return $result;
	}
	if ($host["api_type"] == "zjmf_api") {
		$result["status"] = 200;
		return $result;
	}
	if ($host["api_type"] == "whmcs" && $host["domainstatus"] == "Active") {
		$auth = ["on" => "on", "off" => "on", "reboot" => "on", "bmc" => "on", "novnc" => "on", "reinstall" => "on", "crack_pass" => "on", "traffic" => "on"];
	} else {
		$auth = json_decode($host["auth"], true) ?? [];
	}
	if ($auth[$action] != "on") {
		$result["status"] = 403;
		$result["msg"] = "没有权限";
		return $result;
	}
	if ($action == "reinstall" && $host["reinstall_times"] > 0) {
		$reinstall_info = json_decode($host["reinstall_info"], true);
		$num = $reinstall_info["num"] ?? 0;
		if (empty($reinstall_info) || strtotime("this week Monday") > strtotime($reinstall_info["date"])) {
			$num = 0;
		}
		$id = 0;
		if ($host["buy_times"] == 1) {
			$buy_times = get_buy_reinstall_times($uid, $hostid);
		} else {
			$buy_times = 0;
		}
		if ($host["reinstall_times"] > 0 && $host["reinstall_times"] + $buy_times <= $num) {
			if ($host["buy_times"] > 0) {
				$result["status"] = 400;
				$result["msg"] = "可以购买重装次数";
				$result["price"] = $host["reinstall_price"];
			} else {
				$result["status"] = 400;
				$result["msg"] = "本周重装次数已达最大限额，请下周重试或联系技术支持";
			}
			return $result;
		}
	}
	$result["status"] = 200;
	return $result;
}
function checkPhoneLogin()
{
	return configuration("allow_login_phone");
}
function checkEmailLogin()
{
	return configuration("allow_login_email");
}
function checWechatLogin()
{
	return configuration("allow_login_wechat");
}
function checkPhoneRegister()
{
	return configuration("allow_register_phone");
}
function checkEmailRegister()
{
	return configuration("allow_register_email");
}
function checkWechatRegister()
{
	return configuration("allow_register_wechat");
}
/**
 * 时间 2020/6/22 16:42
 * @title 更新推荐关系
 * @desc  更新推荐关系
 * @param int $uid - 用户uid require
 * @author lgd
 * @version v1.03
 */
function update_aff($uid, $type = 0)
{
	$affi = cookie("AffiliateID");
	$is_open = configuration("affiliate_enabled");
	if ($is_open) {
		if (!empty($affi)) {
			$af = db("affiliates")->field("id")->where("url_identy", $affi)->find();
			$au = \think\Db::name("affiliates_user")->field("id")->where("uid", $uid)->find();
			if (empty($au)) {
				$data = ["affid" => $af["id"], "uid" => $uid, "create_time" => time()];
				\think\Db::name("affiliates_user")->insertGetId($data);
				if ($type == 1) {
					db("affiliates")->where("id", $af["id"])->setInc("registcount", 1);
					if (configuration("affiliate_invited")) {
						$affiliate_invited_money = configuration("affiliate_invited_money") ?: 0;
						\think\Db::name("clients")->where("id", $uid)->setInc("credit", $affiliate_invited_money);
						credit_log(["uid" => $uid, "desc" => "Promotion program activity reward", "amount" => $affiliate_invited_money]);
					}
				} elseif ($type == 2) {
					db("affiliates")->where("id", $af["id"])->setInc("payamount", 1);
				}
			} else {
				if ($type == 2) {
					db("affiliates")->where("id", $af["id"])->setInc("payamount", 1);
				}
			}
		} else {
			if ($type == 2) {
				$au = \think\Db::name("affiliates_user")->field("affid")->where("uid", $uid)->find();
				db("affiliates")->where("id", $au["affid"])->setInc("payamount", 1);
			}
		}
	}
}
/**
 * 时间 2020/6/22 16:42
 * @title 后台更新推荐关系
 * @desc  后台更新推荐关系
 * @param int $uid - 用户uid require
 * @author xue
 * @version v1.03
 */
function admin_update_aff($uid, $aff_id_uid)
{
	return \think\Db::transaction(function () use($uid, $aff_id_uid) {
		$aff_id = \think\Db::name("affiliates")->where("uid", $aff_id_uid)->value("id");
		$au = \think\Db::name("affiliates_user")->field("id,affid")->where("uid", $uid)->find();
		$affiliates_user_temp = \think\Db::name("affiliates_user_temp")->where("uid", $uid)->value("affid_uid");
		if ($aff_id_uid != 0) {
			if ($aff_id) {
				if (!empty($affiliates_user_temp)) {
					\think\Db::name("affiliates_user_temp")->where("uid", $uid)->delete();
				}
				if (empty($au)) {
					$data = ["affid" => $aff_id, "uid" => $uid, "create_time" => time()];
					\think\Db::name("affiliates_user")->insertGetId($data);
					db("affiliates")->where("id", $aff_id)->setInc("registcount", 1);
				} else {
					if (!empty($au["affid"])) {
						if ($au["affid"] != $aff_id) {
							\think\Db::name("affiliates_user")->where("uid", $uid)->update(["affid" => $aff_id]);
							\think\Db::name("affiliates")->where("id", $aff_id)->setInc("registcount", 1);
							\think\Db::name("affiliates")->where("id", $au["affid"])->setDec("registcount", 1);
						}
					}
				}
			} else {
				if (!empty($au["affid"])) {
					\think\Db::name("affiliates")->where("id", $au["affid"])->setDec("registcount", 1);
					\think\Db::name("affiliates_user")->where("uid", $uid)->delete();
				}
				if (empty($affiliates_user_temp)) {
					$data = ["affid_uid" => $aff_id_uid, "uid" => $uid];
					\think\Db::name("affiliates_user_temp")->insertGetId($data);
				} else {
					if ($aff_id_uid != $affiliates_user_temp) {
						\think\Db::name("affiliates_user_temp")->where("uid", $uid)->update(["affid_uid" => $aff_id_uid]);
					}
				}
			}
		} else {
			if (!empty($aff_id) && !empty($au)) {
				\think\Db::name("affiliates")->where("id", $au["affid"])->setDec("registcount", 1);
				\think\Db::name("affiliates_user")->where("uid", $uid)->delete();
			} else {
				\think\Db::name("affiliates_user_temp")->where("uid", $uid)->delete();
			}
		}
	});
}
/**
 * 时间 2020/5/15 17:42
 * @title certifi实名认证修改状态
 * @desc 实名认证修改状态
 * @param int $uid - 用户uid require
 * @param int $type - 类型 1=个人 2=企业 3=个人转企业 require
 * @param int $status - 状态1=未通过 2=通过
 * @return  string $token - 用户凭证
 * @author liyongjun
 * @version v1
 */
function update_certifi_log_status($uid, $type, $status)
{
	$log = \think\Db::name("certifi_log")->where([["uid", "=", $uid], ["type", "=", $type]])->order("id", "DESC")->find();
	if (isset($log["id"])) {
		\think\Db::name("certifi_log")->where("id", $log["id"])->data(["status" => $status])->find();
	}
}
/**
 * 时间 2020-05-26
 * @title 添加自定义订单
 * @desc 添加自定义订单
 * @author huanghao
 * @version v1
 * @param   int  $params['uid'] 用户ID
 * @param   float  $params['price'] 价格
 * @param   string  $params['type'] 账单类型(不传默认custom)
 * @param   int  $params['relid'] 子帐单关联ID
 * @param   string  $params['description'] 子帐单描述
 */
function add_custom_invoice($params = [])
{
	if (empty($params["uid"])) {
		$result["status"] = 400;
		$result["msg"] = "";
		return $result;
	}
	$payment = getGateway($params["uid"]);
	if ($params["price"] < 0.01) {
		$result["status"] = 400;
		$result["msg"] = "";
		return $result;
	}
	$type = $params["type"] ?? "custom";
	$invoices_data = ["uid" => $params["uid"], "create_time" => time(), "due_time" => time(), "paid_time" => 0, "last_capture_attempt" => 0, "subtotal" => $params["price"], "credit" => 0, "tax" => 0, "tax2" => 0, "total" => $params["price"], "taxrate" => 0, "taxrate2" => 0, "status" => "Unpaid", "payment" => $payment, "notes" => "", "type" => $type];
	$invoices_item = ["uid" => $params["uid"], "type" => $type, "rel_id" => $params["relid"], "description" => $params["description"] ?? "", "amount" => $params["price"], "notes" => "", "payment" => $payment];
	$order_data = ["uid" => $params["uid"], "ordernum" => cmf_get_order_sn(), "status" => "Pending", "create_time" => time(), "update_time" => 0, "amount" => $params["price"], "payment" => $payment];
	\think\Db::startTrans();
	try {
		$invoice_id = \think\Db::name("invoices")->insertGetId($invoices_data);
		$invoices_item["invoice_id"] = $invoice_id;
		$order_data["invoiceid"] = $invoice_id;
		\think\Db::name("invoice_items")->insertGetId($invoices_item);
		$orderid = \think\Db::name("orders")->insertGetId($order_data);
		\think\Db::commit();
		$result["status"] = 200;
		$result["invoiceid"] = $invoice_id;
		$result["orderid"] = $orderid;
	} catch (Exception $e) {
		\think\Db::rollback();
		$result["status"] = 400;
		$result["msg"] = $e->getMessage();
	}
	return $result;
}
function get_buy_reinstall_times($uid, $hostid)
{
	$start = strtotime("this week Monday", time());
	$end = strtotime(date("Y-m-d 23:59:59", strtotime("this week Sunday", time())));
	$count = \think\Db::name("dcim_buy_record")->where("uid", $uid)->where("hostid", $hostid)->where("type", "reinstall_times")->where("status", 1)->where("pay_time", ">=", $start)->where("pay_time", "<=", $end)->count();
	return $count;
}
function dcim_callback($invoiceid = 0)
{
	$record = \think\Db::name("dcim_buy_record")->where("invoiceid", $invoiceid)->where("status", 0)->find();
	if (empty($record)) {
		return false;
	}
	if ($record["type"] == "reinstall_times") {
		\think\Db::name("dcim_buy_record")->where("invoiceid", $invoiceid)->where("status", 0)->update(["status" => 1, "pay_time" => time()]);
		$host = \think\Db::name("host")->alias("a")->field("a.dcimid,a.domainstatus,a.nextduedate,a.suspendreason,a.serverid,b.bill_type,c.type product_type,c.api_type,c.zjmf_api_id")->leftJoin("dcim_servers b", "a.serverid=b.serverid")->leftJoin("products c", "a.productid=c.id")->where("a.id", $record["hostid"])->find();
		if ($host["api_type"] == "zjmf_api") {
			$post_data["id"] = $host["dcimid"];
			$res = zjmfCurl($host["zjmf_api_id"], "/dcim/buy_reinstall_times", $post_data);
			if ($res["status"] == 200) {
				$post_data = [];
				$post_data["invoiceid"] = $res["data"]["invoiceid"];
				$post_data["use_credit"] = 1;
				$res = zjmfCurl($host["zjmf_api_id"], "/apply_credit", $post_data);
				if ($res["status"] == 1001) {
					$description = sprintf("上游购买重装次数成功,上游账单ID:%d - Invoice ID:%d - Host ID:%d", $post_data["invoiceid"], $invoiceid, $record["hostid"]);
				} elseif ($res["status"] == 200) {
					$description = sprintf("上游购买重装次数失败,上游账单ID:%d - Invoice ID:%d - Host ID:%d - 原因:余额不足", $post_data["invoiceid"], $invoiceid, $record["hostid"]);
				} else {
					$description = sprintf("上游购买重装次数失败,上游账单ID:%d - Invoice ID:%d - Host ID:%d - 原因:%s", $post_data["invoiceid"], $invoiceid, $record["hostid"], $res["msg"]);
				}
			} else {
				$description = sprintf("上游购买重装次数失败 - Invoice ID: %d - Host ID:%d - 原因:%s", $invoiceid, $record["hostid"], $res["msg"]);
			}
			active_log($description);
		}
	} elseif ($record["type"] == "flow_packet") {
		\think\Db::name("dcim_buy_record")->where("invoiceid", $invoiceid)->where("status", 0)->update(["status" => 1, "pay_time" => time()]);
		$host = \think\Db::name("host")->alias("a")->field("a.dcimid,a.domainstatus,a.nextduedate,a.suspendreason,a.serverid,b.bill_type,c.type product_type,c.api_type,c.zjmf_api_id")->leftJoin("dcim_servers b", "a.serverid=b.serverid")->leftJoin("products c", "a.productid=c.id")->where("a.id", $record["hostid"])->find();
		if ($host["api_type"] == "zjmf_api") {
			$post_data["id"] = $host["dcimid"];
			$post_data["fid"] = $record["relid"];
			$res = zjmfCurl($host["zjmf_api_id"], "/dcim/buy_flow_packet", $post_data);
			if ($res["status"] == 200) {
				$post_data = [];
				$post_data["invoiceid"] = $res["data"]["invoiceid"];
				$post_data["use_credit"] = 1;
				$res = zjmfCurl($host["zjmf_api_id"], "/apply_credit", $post_data);
				if ($res["status"] == 1001) {
					$description = sprintf("上游购买流量包成功,上游账单ID:%d - Invoice ID:%d - Host ID:%d", $post_data["invoiceid"], $invoiceid, $record["hostid"]);
				} elseif ($res["status"] == 200) {
					$description = sprintf("上游购买流量包失败,上游账单ID:%d - Invoice ID:%d - Host ID:%d - 原因:余额不足", $post_data["invoiceid"], $invoiceid, $record["hostid"]);
				} else {
					$description = sprintf("上游购买流量包失败,上游账单ID:%d - Invoice ID:%d - Host ID:%d - 原因:%s", $post_data["invoiceid"], $invoiceid, $record["hostid"], $res["msg"]);
				}
			} else {
				$description = sprintf("上游购买流量包失败 - Invoice ID: %d - Host ID:%d - 原因:%s", $invoiceid, $record["hostid"], $res["msg"]);
			}
			active_log($description);
		} else {
			\think\Db::name("dcim_flow_packet")->where("id", $record["relid"])->setInc("sale_times");
			if ($host["product_type"] == "dcim") {
				$data = ["dcimid" => $host["dcimid"], "bill_type" => $host["bill_type"], "capacity" => $record["capacity"], "hostid" => $record["hostid"], "invoiceid" => $invoiceid, "nextduedate" => $host["nextduedate"]];
				$dcim = new \app\common\logic\Dcim();
				$dcim->setUrl($host["serverid"]);
				$dcim->buyFlowPacket($data);
			} elseif ($host["product_type"] == "dcimcloud") {
				$data = ["dcimid" => $host["dcimid"], "capacity" => $record["capacity"], "hostid" => $record["hostid"], "invoiceid" => $invoiceid, "nextduedate" => $host["nextduedate"], "domainstatus" => $host["domainstatus"], "suspendreason" => $host["suspendreason"]];
				$dcimcloud = new \app\common\logic\DcimCloud();
				$dcimcloud->setUrl($host["serverid"]);
				$dcimcloud->buyFlowPacket($data);
			} else {
				$provision = new \app\common\logic\Provision();
				$provision->afterFlowPacketPaid($record["hostid"], ["capacity" => $record["capacity"]]);
			}
		}
		return true;
	} else {
		return false;
	}
}
function jsonrule($data = [], $code = 200, $header = [], $options = [])
{
	if (VIEW_TEMPLATE_RETURN_ARRAY === true) {
		return $data;
	}
	$sessionAdminId = session("ADMIN_ID");
	$user = \think\Db::name("user")->field("is_sale,sale_is_use,only_mine,all_sale")->where("id", $sessionAdminId)->find();
	$data["identify"] = sub_strs(request()->url());
	$data["action"] = request()->action();
	$data["is_sale"] = $user["is_sale"];
	$data["per_page_limit"] = configuration("per_page_limit") ?? 50;
	return \think\Response::create($data, "json", $code, $header, $options);
}
function jsons($data = [], $code = 200, $header = [], $options = [])
{
	if (VIEW_TEMPLATE_RETURN_ARRAY === true) {
		return $data;
	}
	$data["is_aff"] = configuration("affiliate_enabled");
	return \think\Response::create($data, "json", $code, $header, $options);
}
function json($data = [], $code = 200, $header = [], $options = [])
{
	if (VIEW_TEMPLATE_RETURN_ARRAY === true) {
		return $data;
	}
	return \think\Response::create($data, "json", $code, $header, $options);
}
function jsonseparate($data = [], $code = 200, $header = [], $options = [])
{
	return \think\Response::create($data, "json", $code, $header, $options);
}
function sub_strs($strs)
{
	$str = str_replace("/admin/", "", $strs);
	if (strpos($str, "?") > 0) {
		$str = substr($str, 0, strpos($str, "?"));
	}
	if (strpos($str, "/") > 0) {
		$str = substr($str, 0, strpos($str, "/"));
	}
	return $str;
}
function get_dcim_traffic_usage_table($hostid, $uid, $bill_type, $bwusage, $bwlimit = 0)
{
	if ($bwlimit == 0) {
		return [];
	}
	if ($bill_type == "last_30days") {
		$serverid = 0;
		$host = \think\Db::name("host")->field("id,regdate")->where("id", $hostid)->find();
		$start_time = intval(strtotime(date("Y-m-d 00:00:00", $host["regdate"])));
		$now = time();
		$diff = floor(($now - $start_time) / 2592000);
		$start_time = $start_time + $diff * 30 * 24 * 3600;
		$start_date = date("Y-m-d 00:00:00", $start_time);
	} else {
		$start_date = date("Y-m-01 00:00:00");
	}
	$start_time = strtotime($start_date);
	$res = \think\Db::name("dcim_buy_record")->field("name,capacity,price,pay_time")->where("type", "flow_packet")->where("hostid", $hostid)->where("uid", $uid)->where("status", 1)->where("show_status", 0)->where("pay_time", ">", $start_time)->order("pay_time", "asc")->select()->toArray();
	if (empty($res)) {
		return [];
	}
	$data = [];
	$limit = $bwlimit;
	foreach ($res as $k => $v) {
		$limit -= $v["capacity"];
	}
	$over = $bwusage - $limit;
	foreach ($res as $k => $v) {
		$v["used"] = 0;
		if ($limit > 0 && $over >= 0) {
			$over = $over - $v["capacity"];
			if ($over >= 0) {
				$v["used"] = $v["capacity"];
			} else {
				$v["used"] = $over + $v["capacity"];
			}
		}
		$v["leave"] = round($v["capacity"] - $v["used"], 2);
		$data[] = $v;
	}
	return $data;
}
function getGateway($uid)
{
	$client_gateway = \think\Db::name("clients")->where("id", $uid)->value("defaultgateway");
	foreach (gateway_list() as $v) {
		if ($client_gateway == $v["id"]) {
			$client_gateway = $v["name"];
		}
	}
	if (empty($client_gateway)) {
		$client_gateway = gateway_list()[0]["name"];
	}
	return $client_gateway;
}
function getProductOs($pid)
{
	$groups_filter = [];
	$products = \think\Db::name("products")->where("id", $pid)->find();
	if (!empty($products) && $products["type"] == "dcim") {
		$os = \think\Db::name("server_groups")->alias("a")->leftJoin("servers b", "a.id = b.gid")->leftJoin("dcim_servers c", "b.id = c.serverid")->where("a.system_type", "dcim")->where("b.disabled", 0)->where("a.id", $products["server_group"])->value("c.os");
		if ($os) {
			$os = json_decode($os, true);
			$groups = $os["group"];
			$os_items = $os["os"];
			foreach ($groups as $v) {
				foreach ($os_items as $vv) {
					if ($vv["group_id"] == $v["id"]) {
						$groups_filter[$v["id"]]["id"] = $v["id"];
						$groups_filter[$v["id"]]["name"] = $v["name"];
						$groups_filter[$v["id"]]["svg"] = $v["svg"];
						$groups_filter[$v["id"]]["child"][] = $vv;
					}
				}
			}
		}
	}
	return $groups_filter;
}
function getGroupIdByOs($id)
{
}
function cartCheckOs($pid, $os)
{
	if (!$pid || empty($os)) {
		return false;
	}
	$products = \think\Db::name("products")->where("id", $pid)->find();
	if (!empty($products) && $products["type"] == "dcim") {
		$prodtuc_os = \think\Db::name("server_groups")->alias("a")->leftJoin("servers b", "a.id = b.gid")->leftJoin("dcim_servers c", "b.id = c.serverid")->where("a.system_type", "dcim")->where("b.disabled", 0)->where("a.id", $products["server_group"])->value("c.os");
		if ($prodtuc_os) {
			$prodtuc_os = json_decode($prodtuc_os, true);
			$os_items = $prodtuc_os["os"];
			foreach ($os_items as $v) {
				if (array_keys($os)[0] == $v["id"] && array_values($os)[0] == $v["name"]) {
					return true;
					break;
				}
			}
		} else {
			return false;
		}
	} else {
		return false;
	}
	return false;
}
function invoiceTypeToDescription($type, $invoiceid = null)
{
	if ($type == "product") {
		$description = "购买产品";
	} elseif ($type == "renew") {
		$description = "产品续费";
	} elseif ($type == "recharge") {
		$description = "用户充值";
	} elseif ($type == "custom") {
		$description = "自定义充值";
	} elseif ($type == "upgrade") {
		$description = "升降级";
	} elseif ($type == "combine") {
		$ids = \think\Db::name("invoice_items")->where("invoice_id", $invoiceid)->where("type", "combine")->column("rel_id");
		$description = "批量支付#" . implode(",#", $ids);
	} elseif ($type == "down") {
		$description = "降级";
	} else {
		$description = "";
	}
	return $description;
}
function get_order_num_by_date(int $days = 7, $status = "")
{
	$start = strtotime(date("Y-m-d", strtotime("-" . ($days - 1) . " days")));
	if (empty($status)) {
		$count = \think\Db::name("orders")->field("from_unixtime(create_time, \"%Y-%m-%d\") date,count(id) count,sum(amount) amount_total")->where("create_time", ">=", $start)->where("delete_time", 0)->group("date")->select()->toArray();
	} else {
		$count = \think\Db::name("orders")->alias("a")->field("from_unixtime(a.create_time, \"%Y-%m-%d\") date,count(a.id) count,sum(amount) amount_total")->leftJoin("invoices b", "a.invoiceid=b.id")->where("a.create_time", ">=", $start)->where("b.status", $status)->where("b.delete_time", 0)->where("a.delete_time", 0)->group("date")->select()->toArray();
	}
	return array_values($count);
}
function create_system_token()
{
	$ip = $_SERVER["SERVER_ADDR"];
	$token = md5($ip . time() . uniqid() . mt_rand(1000, 9999));
	$key = "system_token";
	$res = \think\Db::name("configuration")->where("setting", $key)->find();
	if (empty($res)) {
		\think\Db::name("configuration")->insert(["setting" => $key, "value" => $token, "create_time" => time()]);
	}
}
function get_dcim_svg($id, $os, $group)
{
	if (empty($id)) {
		return "";
	}
	$gid = 0;
	foreach ($os as $v) {
		if ($v["id"] == $id) {
			$gid = $v["group_id"];
			break;
		}
	}
	$svg = 1;
	foreach ($group as $v) {
		if ($v["id"] == $gid) {
			$svg = $v["svg"];
			break;
		}
	}
	return intval($svg);
}
function get_dcim_os_info($id, $os, $group)
{
	$data = ["svg" => ""];
	if (empty($id)) {
		return $data;
	}
	$gid = 0;
	foreach ($os as $v) {
		if ($v["id"] == $id) {
			$gid = $v["group_id"];
			$data["os_name"] = $v["os_name"];
			$data["ostype"] = $v["ostype"];
			$data["port"] = $v["port"];
			break;
		}
	}
	foreach ($group as $v) {
		if ($v["id"] == $gid) {
			$data["svg"] = $v["svg"];
			break;
		}
	}
	return $data;
}
function get_all_dcim_area()
{
	$area = \think\Db::name("dcim_servers")->field("area")->where("area", "<>", "")->select()->toArray();
	$area_temp = [];
	foreach ($area as $k => $v) {
		$a = json_decode($v["area"], true);
		foreach ($a as $kk => $vv) {
			if (empty($vv["name"])) {
				continue;
			}
			if (!isset($area_temp[$vv["area"]])) {
				$area_temp[$vv["area"]] = [];
			}
			if (!in_array($vv["name"], $area_temp[$vv["area"]])) {
				$area_temp[$vv["area"]][] = $vv["name"];
			}
		}
	}
	$data = [];
	foreach ($area_temp as $k => $v) {
		foreach ($v as $vv) {
			$data[] = ["code" => $k, "name" => $vv];
		}
	}
	return $data;
}
function compareLicense()
{
	if (!empty($_SERVER) && isset($_SERVER["SERVER_ADDR"]) && !empty($_SERVER["SERVER_ADDR"]) && isset($_SERVER["HTTP_HOST"]) && !empty($_SERVER["HTTP_HOST"])) {
		updateconfiguration("last_license_time", time());
	} else {
		return false;
	}
	$ip = $_SERVER["SERVER_ADDR"];
	$arr = parse_url($_SERVER["HTTP_HOST"]);
	$domain = $arr["host"] . ($arr["port"] ? ":" . $arr["port"] : "") ?: $arr["path"];
	$type = "finance";
	$system_license = configuration("system_license");
	$system_token = configuration("system_token");
	$install_version = configuration("update_last_version");
	$data = ["ip" => $ip, "domain" => $domain, "type" => $type, "license" => $system_license, "system_token" => $system_token, "install_version" => $install_version ?? "1.0.0", "token" => config("auth_token"), "installation_path" => CMF_ROOT, "request_time" => time()];
	if (!!configuration("zjmf_authorize")) {
		$data["auth_info"] = de_authorize(configuration("zjmf_authorize"));
		$data["auth_info"] = json_encode($data["auth_info"]);
	}
	$url = "https://license.soft13.idcsmart.com/app/api/auth_complete";
	$result = json_decode(postrequest($url, $data, "", 20), true);
	if (isset($result["status"])) {
		if (isset($result["ip"])) {
			updateconfiguration("authsystemip", $result["ip"]);
		}
		if ($result["status"] == 200) {
			if (!empty($result["data"])) {
				updateconfiguration("zjmf_authorize", $result["data"]);
			}
			return $result;
		} else {
			return $result;
		}
	} else {
		$result = json_decode(postrequest($url, $data, "", 20), true);
		if (isset($result["status"])) {
			if (isset($result["ip"])) {
				updateconfiguration("authsystemip", $result["ip"]);
			}
			if ($result["status"] == 200) {
				if (!empty($result["data"])) {
					updateconfiguration("zjmf_authorize", $result["data"]);
				}
				return $result;
			} else {
				return $result;
			}
		} else {
			return false;
		}
	}
}
function recurse_copy($src, $dst, $out = [])
{
	$dir = opendir($src);
	@mkdir($dst, 511, true);
	while (false !== ($file = readdir($dir))) {
		if ($file != "." && $file != "..") {
			if (is_dir($src . "/" . $file)) {
				if (empty($out) || !in_array($file, $out)) {
					recurse_copy($src . "/" . $file, $dst . "/" . $file);
				}
			} else {
				$res = copy($src . "/" . $file, $dst . "/" . $file);
				if (!$res) {
					return ["status" => 400, "data" => $file];
				}
			}
		}
	}
	closedir($dir);
	return ["status" => 200];
}
function recurse_chmod($src, $dirmod = 493, $filemod = 420)
{
	$dir = opendir($src);
	while (false !== ($file = readdir($dir))) {
		if ($file != "." && $file != "..") {
			if (is_dir($src . "/" . $file)) {
				chmod($src . "/" . $file, $dirmod);
				recurse_chmod($src . "/" . $file);
			} else {
				chmod($src . "/" . $file, $filemod);
			}
		}
	}
	closedir($dir);
	return "success";
}
function format_dcim_ipnum($ip_num = "")
{
	if (strpos($ip_num, ",") !== false) {
		$arr = explode(",", $ip_num);
		$res = [];
		foreach ($arr as $v) {
			$r = format_dcim_ipnum($v);
			if ($r === false) {
				return false;
			}
			foreach ($r as $kk => $vv) {
				$res[$kk] = $vv;
			}
		}
	} elseif (strpos($ip_num, "_") !== false) {
		$arr = explode("_", $ip_num);
		if (count($arr) != 2 || !is_numeric($arr[0]) || !is_numeric($arr[1])) {
			return false;
		}
		return [$arr[1] => $arr[0]];
	} else {
		$res = false;
	}
	return $res;
}
function getMonths()
{
	$j = date("t");
	$start_time = strtotime(date("Y-m-01"));
	$array = [];
	for ($i = 0; $i < $j; $i++) {
		$array[] = date("Y-m-d", $start_time + $i * 86400);
	}
	return $array;
}
function getLastMonths()
{
	$start_time = strtotime(date("Y-m-01", mktime(0, 0, 0, date("m") - 2, 1, date("Y"))));
	$j = date("t", $start_time);
	$array = [];
	for ($i = 0; $i < $j; $i++) {
		$array[] = date("Y-m-d", $start_time + $i * 86400);
	}
	$start_time = strtotime(date("Y-m-01", strtotime("-1 month")));
	$j = date("t", $start_time);
	for ($i = 0; $i < $j; $i++) {
		$array[] = date("Y-m-d", $start_time + $i * 86400);
	}
	$j = date("t");
	$start_time = strtotime(date("Y-m-01"));
	for ($i = 0; $i < $j; $i++) {
		$array[] = date("Y-m-d", $start_time + $i * 86400);
	}
	return $array;
}
function getStartMonths($start_time)
{
	$start_times = $start_time[0];
	$end_time = $start_time[1];
	$j = ($end_time - $start_times) / 86400;
	$array = [];
	for ($i = 0; $i < $j; $i++) {
		$array[] = date("Y-m-d", $start_times + $i * 86400);
	}
	return $array;
}
function getAllStartMonths($start_time)
{
	$time = \think\Db::name("invoices")->field("paid_time")->where("paid_time", ">", 0)->order("id", "asc")->find();
	$j = getTimeLine($time["paid_time"]);
	return $j;
}
function getMonthNum($date1, $date2)
{
	$date1_stamp = strtotime($date1);
	$date2_stamp = strtotime($date2);
	list($date_1["y"], $date_1["m"]) = explode("-", date("Y-m", $date1_stamp));
	list($date_2["y"], $date_2["m"]) = explode("-", date("Y-m", $date2_stamp));
	return abs(($date_2["y"] - $date_1["y"]) * 12 + $date_2["m"] - $date_1["m"]);
}
/**
 * 获取从开始到当前时间的月份列表
 * @param $startMonth 月份时间戳
 * @return array
 */
function getTimeLine($startMonth)
{
	$timeline = [];
	$StartMonth = date("Y-m-d", $startMonth);
	$EndMonth = date("Y-m-d", time());
	$ToStartMonth = strtotime($StartMonth);
	$ToEndMonth = strtotime($EndMonth);
	$i = false;
	while ($ToStartMonth <= $ToEndMonth) {
		$NewMonth = !$i ? date("Y-m", strtotime("+0 Month", $ToStartMonth)) : date("Y-m", strtotime("+1 Month", $ToStartMonth));
		$ToStartMonth = strtotime($NewMonth);
		$i = true;
		$timeline[] = $NewMonth;
	}
	array_pop($timeline);
	return $timeline;
}
function check_type_is_use($type, $uid, $sms)
{
	$client = \think\Db::name("clients")->where("id", $uid)->find();
	$rangeType = preg_match("/^((\\+86)|(86))?[1][3456789][0-9]{9}\$/", $client["phonenumber"]) ? 0 : 1;
	if (!($sms_operator = $sms->getSmsOperator($rangeType))) {
		return false;
	}
	$count = \think\Db::name("message_template_link")->where("type", $type)->where("range_type", $rangeType)->where("sms_operator", $sms_operator)->where("is_use", 1)->find();
	if (!empty($count)) {
		return $client;
	} else {
		return false;
	}
}
function getSurplus($id)
{
	$tmp = \think\Db::name("invoice_items")->alias("a")->leftJoin("invoices b", "a.invoice_id = b.id")->where("a.rel_id", $id)->where("b.status", "Paid")->where("a.type", "combine")->find();
	if (!empty($tmp)) {
		return number_format(0, 2);
	}
	$invoices = \think\Db::name("invoices")->alias("a")->field("a.id,b.username,a.create_time,a.due_time,a.paid_time,a.total,a.subtotal,a.status,a.credit,a.payment,a.last_capture_attempt")->leftJoin("clients b ", "a.uid = b.id")->where("a.id", $id)->where("a.delete_time", 0)->find();
	$paids = \think\Db::name("accounts")->field("amount_in,fees,amount_out")->where("invoice_id", $id)->where("delete_time", 0)->select()->toArray();
	$amount_in = $fees = $amount_out = $credit_total = 0;
	foreach ($paids as $paid) {
		$amount_out += $paid["amount_out"];
		$amount_in += $paid["amount_in"];
		$fees += $paid["fees"];
	}
	$total = $invoices["total"];
	$surplus = bcadd(bcsub($total, $amount_in, 2), $amount_out, 2);
	return $surplus;
}
function deleteDir($path, $out = [])
{
	if (is_dir($path)) {
		$dirs = scandir($path);
		foreach ($dirs as $dir) {
			if (!in_array($dir, $out)) {
				if ($dir != "." && $dir != "..") {
					$sonDir = $path . "/" . $dir;
					if (is_dir($sonDir)) {
						deleteDir($sonDir);
						@rmdir($sonDir);
					} else {
						@unlink($sonDir);
					}
				}
			}
		}
		@rmdir($path);
	}
}
/**
 * GOOGLE翻译 汉英
 */
function googleTran($text)
{
	return $text;
	exit;
	if (empty($text)) {
		return "";
	}
	$wf = file_get_contents("http://translate.google.cn/translate_t?sl=zh-CN&tl=en&text=" . urlencode($text) . "#");
	if (false === $wf || empty($wf)) {
		return false;
	}
	$return = "";
	$wf = mb_substr($wf, 14000, 28100, "utf-8");
	$wf = strip_tags($wf);
	$star = strpos($wf, "英语中文(简体)日语");
	if (false === $star) {
		return false;
	}
	$end = strpos($wf, "Alpha字典");
	if (false === $end) {
		return false;
	}
	$return = strip_tags(substr($wf, $star + 18, $end - $star - 18));
	return $return;
}
function getProductCommission($oid, $type, $uid, $create_time, $ladder, $invoiceid = 0)
{
	$sum = 0;
	$bates = [];
	$types = "";
	$typed = [];
	$hosts = \think\Db::name("host")->alias("a")->join("products b", "a.productid = b.id")->join("invoice_items in", "in.rel_id = a.id")->field("a.domainstatus,a.domain,b.id,a.id as hostid,a.uid,b.name,in.type,in.amount,in.invoice_id,in.id as inid,in.aff_sure_time,in.aff_commission,in.aff_commmission_bates,in.aff_commmission_bates_type,in.is_aff")->where("in.invoice_id", $invoiceid)->order("a.id", "asc")->select()->toArray();
	if ($type == "renew") {
		foreach ($hosts as $key => $val) {
			$affiliates = \think\Db::name("affiliates_products_setting")->where("pid", $val["id"])->find();
			if ($affiliates["affiliate_enabled"] == 1) {
				if ($affiliates["affiliate_is_renew"] == 1) {
					$bates[] = $affiliates["affiliate_renew"];
					$nums = \think\Db::name("invoice_items")->where("rel_id", $val["hostid"])->where("type", "in", "renew,promo,discount")->sum("amount");
					if ($affiliates["affiliate_renew_type"] == 1) {
						if ($val["type"] != "discount" && $val["type"] != "setup" && $val["type"] != "promo") {
							if (!empty($ladder["turnover"]["turnover"])) {
								$sum = bcadd(bcadd($affiliates["affiliate_renew"], bcmul($ladder["turnover"]["bates"] / 100, $nums, 4), 4), $sum, 4);
							} else {
								$sum = bcadd($affiliates["affiliate_renew"], $sum, 4);
							}
						}
						$typed[] = 1;
					} else {
						if (!empty($ladder["turnover"]["turnover"])) {
							$sum = bcadd(bcadd(bcmul($affiliates["affiliate_renew"] / 100, $nums, 4), bcmul($ladder["turnover"]["bates"] / 100, $nums, 4), 4), $sum, 4);
						} else {
							$sum = bcadd(bcmul($affiliates["affiliate_renew"] / 100, $nums, 4), $sum, 4);
						}
						$typed[] = 2;
					}
				} else {
					return false;
					break;
				}
			} elseif ($affiliates["affiliate_enabled"] == 2) {
			} else {
				if (configuration("affiliate_is_renew") == 1) {
					$bates[] = configuration("affiliate_renew");
					$nums = $val["amount"];
					if (configuration("affiliate_renew_type") == 1) {
						if ($val["type"] != "discount" && $val["type"] != "setup" && $val["type"] != "promo") {
							if (!empty($ladder["turnover"]["turnover"])) {
								$sum = bcadd(bcadd(configuration("affiliate_renew"), bcmul($ladder["turnover"]["bates"] / 100, $nums, 4), 4), $sum, 4);
							} else {
								$sum = bcadd(configuration("affiliate_renew"), $sum, 4);
							}
						}
						$typed[] = 1;
					} else {
						if (!empty($ladder["turnover"]["turnover"])) {
							$sum = bcadd(bcadd(bcmul(configuration("affiliate_renew") / 100, $nums, 4), bcmul($ladder["turnover"]["bates"] / 100, $nums, 4), 4), $sum, 4);
						} else {
							$sum = bcadd(bcmul(configuration("affiliate_renew") / 100, $nums, 4), $sum, 2);
						}
						$typed[] = 2;
					}
				} else {
					return false;
					break;
				}
			}
		}
	} elseif ($type == "product") {
		$ordercount = \think\Db::name("orders")->alias("o")->join("clients c", "o.uid=c.id")->join("invoices i", "i.id=o.invoiceid")->field("o.id")->where("i.status", "in", "Paid,Refunded")->where("o.create_time", "<", $create_time)->where("c.id", $uid)->count();
		if ($ordercount > 0) {
			$types = "product2";
			foreach ($hosts as $key => $val) {
				$affiliates = \think\Db::name("affiliates_products_setting")->where("pid", $val["id"])->find();
				if ($affiliates["affiliate_enabled"] == 2) {
				} else {
					if (configuration("affiliate_is_reorder") == 1) {
						$bates[] = configuration("affiliate_reorder");
						$nums = $val["amount"];
						if (configuration("affiliate_reorder_type") == 1) {
							if ($val["type"] != "discount" && $val["type"] != "setup" && $val["type"] != "promo") {
								if (!empty($ladder["turnover"]["turnover"])) {
									$sum = bcadd(bcadd(configuration("affiliate_reorder"), bcmul($ladder["turnover"]["bates"] / 100, $nums, 4), 4), $sum, 4);
								} else {
									$sum = bcadd(configuration("affiliate_reorder"), $sum, 4);
								}
							}
							$typed[] = 1;
						} else {
							if (!empty($ladder["turnover"]["turnover"])) {
								$sum = bcadd(bcadd(bcmul(configuration("affiliate_reorder") / 100, $nums, 4), bcmul($ladder["turnover"]["bates"] / 100, $nums, 4), 4), $sum, 4);
							} else {
								$sum = bcadd(bcmul(configuration("affiliate_reorder") / 100, $nums, 4), $sum, 4);
							}
							$typed[] = 2;
						}
					} else {
						return false;
						break;
					}
				}
			}
		} else {
			foreach ($hosts as $key => $val) {
				$affiliates = \think\Db::name("affiliates_products_setting")->where("pid", $val["id"])->find();
				if (!empty($affiliates) && $affiliates["affiliate_enabled"] == 1) {
					$bates[] = $affiliates["affiliate_bates"];
					$nums = $val["amount"];
					if ($affiliates["affiliate_type"] == 1) {
						if ($val["type"] != "discount" && $val["type"] != "setup" && $val["type"] != "promo") {
							if (!empty($ladder["turnover"]["turnover"])) {
								$sum = bcadd(bcadd($affiliates["affiliate_bates"], bcmul($ladder["turnover"]["bates"] / 100, $nums, 4), 4), $sum, 4);
							} else {
								$sum = bcadd($affiliates["affiliate_bates"], $sum, 4);
							}
						}
						$typed[] = 1;
					} else {
						if (!empty($ladder["turnover"]["turnover"])) {
							$sum = bcadd(bcadd(bcmul($affiliates["affiliate_bates"] / 100, $nums, 4), bcmul($ladder["turnover"]["bates"] / 100, $nums, 4), 4), $sum, 4);
						} else {
							$sum = bcadd(bcmul($affiliates["affiliate_bates"] / 100, $nums, 4), $sum, 4);
						}
						$typed[] = 2;
					}
				} else {
					if ($affiliates["affiliate_enabled"] == 2) {
					} else {
						$nums = $val["amount"];
						$bates[] = configuration("affiliate_bates");
						if (configuration("affiliate_type") == 1) {
							if ($val["type"] != "discount" && $val["type"] != "setup" && $val["type"] != "promo") {
								if (!empty($ladder["turnover"]["turnover"])) {
									$sum = bcadd(bcadd(configuration("affiliate_bates"), bcmul($ladder["turnover"]["bates"] / 100, $nums, 4), 4), $sum, 4);
								} else {
									$sum = bcadd(configuration("affiliate_bates"), $sum, 4);
								}
							}
							$typed[] = 1;
						} else {
							if (!empty($ladder["turnover"]["turnover"])) {
								$sum = bcadd(bcadd(bcmul(configuration("affiliate_bates") / 100, $nums, 4), bcmul($ladder["turnover"]["bates"] / 100, $nums, 4), 4), $sum, 4);
							} else {
								$sum = bcadd(bcmul(configuration("affiliate_bates") / 100, $nums, 4), $sum, 4);
							}
							$typed[] = 2;
						}
					}
				}
			}
		}
	} else {
		return false;
	}
	return ["sum" => $sum, "bates" => $bates[0], "type" => $types, "types" => $typed[0]];
}
function getProductCount($oid, $type, $uid, $create_time)
{
	$hosts = \think\Db::name("host")->alias("a")->join("products b", "a.productid = b.id")->field("b.id,a.id as hostid,a.uid")->where("a.orderid", $oid)->order("a.id", "desc")->select()->toArray();
	if ($type == "renew") {
		foreach ($hosts as $key => $val) {
			$affiliates = \think\Db::name("affiliates_products_setting")->where("pid", $val["id"])->find();
			if ($affiliates["affiliate_enabled"] == 1) {
				if ($affiliates("affiliate_is_renew") != 1) {
					return false;
					break;
				}
			}
			if ($affiliates["affiliate_enabled"] != 1 && $affiliates["affiliate_enabled"] != 2) {
				if (configuration("affiliate_is_renew") != 1) {
					return false;
					break;
				}
			}
		}
	} elseif ($type == "product") {
		$ordercount = \think\Db::name("orders")->alias("o")->join("clients c", "o.uid=c.id")->join("invoices i", "i.id=o.invoiceid")->field("o.id")->where("i.status", "in", "Paid,Refunded")->where("o.create_time", "<", $create_time)->where("c.id", $uid)->count();
		if ($ordercount > 0) {
			foreach ($hosts as $key => $val) {
				if (configuration("affiliate_is_reorder") != 1) {
					return false;
					break;
				}
			}
		} else {
			foreach ($hosts as $key => $val) {
				$affiliates = \think\Db::name("affiliates_products_setting")->where("pid", $val["id"])->find();
				if ($affiliates["affiliate_enabled"] != 1 && $affiliates["affiliate_enabled"] != 2) {
					if (configuration("affiliate_enabled") != 1) {
						return false;
						break;
					}
				}
			}
		}
	} else {
		return false;
	}
	return true;
}
function getAffBycount($total, $id)
{
	$affiliates = \think\Db::name("affiliates_user_setting")->where("uid", $id)->find();
	$affiliate_enabled = configuration("affiliate_enabled");
	foreach ($total as $k => $value) {
		if ($affiliate_enabled == 1) {
			if ($value["type"] == "renew") {
				unset($total[$k]);
				continue;
			} elseif ($value["type"] == "product") {
				if (!isAffInvoiceProduct($value["invoiceid"])) {
					unset($total[$k]);
					continue;
				}
				$ordercount = \think\Db::name("orders")->alias("o")->join("clients c", "o.uid=c.id")->join("invoices i", "i.id=o.invoiceid")->field("o.id")->where("i.status", "in", "Paid,Refunded")->where("o.create_time", "<", $value["create_time"])->where("c.id", $value["uid"])->count();
				if ($ordercount > 0) {
					if ($affiliates["affiliate_is_reorder"] != 1) {
						$commission = getCountByaff($value["id"], $value["type"], $value["uid"], $value["create_time"]);
						if ($commission === false) {
							unset($total[$k]);
							continue;
						}
					}
				}
			} else {
				unset($total[$k]);
				continue;
			}
		} else {
			unset($total[$k]);
			continue;
		}
	}
	return count($total);
}
/**
 * 账单中是否有商品关闭推介计划
 * @param $invoice_id
 * @return bool|float|int|string
 */
function isAffInvoiceProduct($invoice_id)
{
	$host_ids = \think\Db::name("invoice_items")->where("invoice_id", $invoice_id)->column("rel_id");
	if (!$host_ids) {
		return false;
	}
	$pro_ids = \think\Db::name("host")->whereIn("id", array_unique($host_ids))->column("productid");
	return \think\Db::name("affiliates_products_setting")->where("affiliate_enabled", "<>", 2)->whereIn("pid", $pro_ids)->count();
}
function getCountByaff($oid, $type, $uid, $create_time)
{
	$hosts = \think\Db::name("host")->alias("a")->join("products b", "a.productid = b.id")->field("b.id,a.id as hostid,a.uid")->where("a.orderid", $oid)->order("a.id", "desc")->select()->toArray();
	if ($type == "renew") {
		return false;
	} elseif ($type == "product") {
		$ordercount = \think\Db::name("orders")->alias("o")->join("clients c", "o.uid=c.id")->join("invoices i", "i.id=o.invoiceid")->field("o.id")->where("i.status", "in", "Paid,Refunded")->where("o.create_time", "<", $create_time)->where("c.id", $uid)->count();
		if ($ordercount > 0) {
			foreach ($hosts as $key => $val) {
				if (configuration("affiliate_is_reorder") != 1) {
					return false;
					break;
				}
			}
		}
	} else {
		return false;
	}
	return true;
}
/**
 * @title 获取当前销售员的阶级统计(所有)
 * @description 接口说明:
 * @author 刘国栋
 */
function getLadder($uid, $uids)
{
	$user = \think\Db::name("clients")->alias("c")->field("cu.suffix")->join("currencies cu", "cu.id = c.currency")->where("c.id", $uid)->find();
	$rows = \think\Db::name("invoices")->alias("i")->join("clients c", "i.uid=c.id")->join("currencies cu", "cu.id = c.currency")->field("i.id as invoiceid")->where("i.status", "=", "Paid")->whereTime("i.paid_time", "month")->where("c.id", "in", $uids)->select()->toArray();
	$rows1 = \think\Db::name("invoices")->alias("i")->join("invoice_items in", "in.invoice_id=i.id")->join("host h", "h.id=in.rel_id")->join("clients c", "i.uid=c.id")->field("i.id,in.amount")->where("i.status", "=", "Paid")->whereTime("i.paid_time", "month")->where("c.id", "in", $uids)->select()->toArray();
	$sum = 0;
	foreach ($rows1 as $key => $val) {
		$sum = bcadd($sum, $val["amount"], 2);
	}
	foreach ($rows as $key => $val) {
		$accounts = \think\Db::name("accounts")->field("id,amount_in,amount_out,refund")->where("invoice_id", $val["invoiceid"])->where("refund", ">", 0)->select()->toArray();
		foreach ($accounts as $key1 => $val1) {
			$sum = bcsub($sum, $val1["amount_out"], 2);
		}
	}
	$sale_ladder = \think\Db::name("affiliate_ladder")->field("id,turnover,bates")->where("turnover", "<=", $sum)->order("turnover", DESC)->find();
	$sale_ladder1 = \think\Db::name("affiliate_ladder")->field("id,turnover,bates")->where("turnover", ">", $sum)->order("turnover", ASC)->find();
	if (empty($sale_ladder)) {
		$sale_ladder = ["id" => null, "turnover" => null, "bates" => null];
	}
	if (empty($sale_ladder1)) {
		$sale_ladder1 = ["id" => null, "turnover" => null, "bates" => null];
	}
	$data = ["total" => floatval($sum), "turnover" => $sale_ladder, "last" => $sale_ladder1, "suffix" => $user["suffix"]];
	return $data;
}
function getids($uid)
{
	$data = \think\Db::name("affiliates")->where("uid", $uid)->find();
	$au = \think\Db::name("affiliates_user")->field("uid")->where("affid", $data["id"])->select()->toArray();
	$uids = "";
	foreach ($au as $key => $value) {
		if ($key == 0) {
			$uids .= $value["uid"];
		} else {
			$uids .= "," . $value["uid"];
		}
	}
	return $uids;
}
function getCommissioninvoice($oid, $type, $aff_type, $ladder, $bates)
{
	$hosts = \think\Db::name("host")->alias("a")->join("products b", "a.productid = b.id")->join("invoice_items in", "in.rel_id = a.id")->field("a.domainstatus,a.domain,b.id,a.id as hostid,a.uid,b.name,in.type,in.amount,in.invoice_id,in.id as inid,in.aff_sure_time,in.aff_commission,in.aff_commmission_bates,in.aff_commmission_bates_type,in.is_aff")->where("in.invoice_id", $oid)->where("in.type", "in", $type)->order("a.id", "asc")->select()->toArray();
	foreach ($hosts as $key => $val) {
		if ($aff_type == 1) {
			if (!empty($ladder["turnover"]["turnover"])) {
				if ($val["type"] != "discount" && $val["type"] != "setup" && $val["type"] != "promo") {
					$hosts[$key]["commission"] = round(bcadd($bates, bcmul($ladder["turnover"]["bates"] / 100, $val["amount"], 4), 4), 2);
				} else {
					$hosts[$key]["commission"] = 0;
				}
			} else {
				if ($val["type"] != "discount" && $val["type"] != "setup" && $val["type"] != "promo") {
					$hosts[$key]["commission"] = $bates;
				} else {
					$hosts[$key]["commission"] = 0;
				}
			}
			$hosts[$key]["commission_bates"] = $bates;
			$hosts[$key]["commission_bates_type"] = $aff_type;
		} else {
			if (!empty($ladder["turnover"]["turnover"])) {
				$hosts[$key]["commission"] = round(bcadd(bcmul($bates / 100, $val["amount"], 4), bcmul($ladder["turnover"]["bates"] / 100, $val["amount"], 4), 4), 2);
			} else {
				$hosts[$key]["commission"] = round(bcmul($bates / 100, $val["amount"], 4), 2);
			}
			$hosts[$key]["commission_bates"] = $bates;
			$hosts[$key]["commission_bates_type"] = $aff_type;
		}
		$str = $hosts[$key]["name"] . "(" . $hosts[$key]["domain"] . ")";
		$hosts[$key]["names"] = "<a class=\"el-link el-link--primary is-underline\" 
                href=\"#/customer-view/product-innerpage?hid=" . $val["hostid"] . "&id=" . $val["uid"] . "\">
                <span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
		switch ($val["type"]) {
			case "renew":
				$hosts[$key]["type"] = "续费";
				$hosts[$key]["names"] = "续费-" . $hosts[$key]["names"];
				break;
			case "host":
				$hosts[$key]["type"] = "产品";
				break;
			case "setup":
				$hosts[$key]["type"] = "初装";
				$hosts[$key]["names"] = "初装";
				break;
		}
		$do = config("domainstatus");
		$hosts[$key]["domainstatus"] = $do[$hosts[$key]["domainstatus"]];
		$hosts[$key]["commission"] = sprintf("%.2f", $hosts[$key]["commission"]);
	}
	return $hosts;
}
function getProductCommissionInvoice($oid, $type, $uid, $create_time, $ladder, $invoiceid = 0)
{
	$sum = 0;
	$bates = [];
	$types = "";
	$arr = [];
	$typed = [];
	if ($type == "renew") {
		$typp = "renew,promo,discount";
	} elseif ($type == "product") {
		$typp = "host,setup,promo,discount";
	}
	$hosts = \think\Db::name("host")->alias("a")->join("products b", "a.productid = b.id")->join("invoice_items in", "in.rel_id = a.id")->field("a.domainstatus,a.domain,b.id,a.id as hostid,a.uid,b.name,in.type,in.amount,in.invoice_id,in.id as inid,in.aff_sure_time,in.aff_commission,in.aff_commmission_bates,in.aff_commmission_bates_type,in.is_aff")->where("in.invoice_id", $invoiceid)->where("in.type", "in", $typp)->order("a.id", "asc")->select()->toArray();
	if ($type == "renew") {
		foreach ($hosts as $key => $val) {
			$affiliates = \think\Db::name("affiliates_products_setting")->where("pid", $val["id"])->find();
			if ($affiliates["affiliate_enabled"] == 1) {
				if ($affiliates["affiliate_is_renew"] == 1) {
					$bates[] = $affiliates["affiliate_renew"];
					$nums = $val["amount"];
					if ($affiliates["affiliate_renew_type"] == 1) {
						if ($val["type"] != "discount" && $val["type"] != "setup" && $val["type"] != "promo") {
							if (!empty($ladder["turnover"]["turnover"])) {
								$sum = bcadd(bcadd($affiliates["affiliate_renew"], bcmul($ladder["turnover"]["bates"] / 100, $nums, 4), 4), $sum, 4);
							} else {
								$sum = bcadd($affiliates["affiliate_renew"], $sum, 4);
							}
						}
						$typed[] = 1;
					} else {
						if (!empty($ladder["turnover"]["turnover"])) {
							$sum = bcadd(bcadd(bcmul($affiliates["affiliate_renew"] / 100, $nums, 4), bcmul($ladder["turnover"]["bates"] / 100, $nums, 4), 4), $sum, 4);
						} else {
							$sum = bcadd(bcmul($affiliates["affiliate_renew"] / 100, $nums, 4), $sum, 4);
						}
						$typed[] = 2;
					}
					$arrs = getCommissioninvoicebyhost($hosts[$key], $affiliates["affiliate_renew_type"], $ladder, $affiliates["affiliate_renew"]);
					$arr[] = $arrs;
				} else {
					return false;
					break;
				}
			} elseif ($affiliates["affiliate_enabled"] == 2) {
				$arrs = getCommissioninvoicebyhost($hosts[$key], 1, 1, $ladder, 0);
				$arr[] = $arrs;
			} else {
				if (configuration("affiliate_is_renew") == 1) {
					$bates[] = configuration("affiliate_renew");
					$nums = $val["amount"];
					if (configuration("affiliate_renew_type") == 1) {
						if ($val["type"] != "discount" && $val["type"] != "setup" && $val["type"] != "promo") {
							if (!empty($ladder["turnover"]["turnover"])) {
								$sum = bcadd(bcadd(configuration("affiliate_renew"), bcmul($ladder["turnover"]["bates"] / 100, $nums, 4), 4), $sum, 4);
							} else {
								$sum = bcadd(configuration("affiliate_renew"), $sum, 4);
							}
						}
						$typed[] = 1;
					} else {
						if (!empty($ladder["turnover"]["turnover"])) {
							$sum = bcadd(bcadd(bcmul(configuration("affiliate_renew") / 100, $nums, 4), bcmul($ladder["turnover"]["bates"] / 100, $nums, 4), 4), $sum, 4);
						} else {
							$sum = bcadd(bcmul(configuration("affiliate_renew") / 100, $nums, 4), $sum, 4);
						}
						$typed[] = 2;
					}
					$arrs = getCommissioninvoicebyhost($hosts[$key], configuration("affiliate_renew_type"), $ladder, configuration("affiliate_renew"));
					$arr[] = $arrs;
				} else {
					return false;
					break;
				}
			}
		}
	} elseif ($type == "product") {
		$ordercount = \think\Db::name("orders")->alias("o")->join("clients c", "o.uid=c.id")->join("invoices i", "i.id=o.invoiceid")->field("o.id")->where("i.status", "=", "Paid")->where("o.create_time", "<", $create_time)->where("c.id", $uid)->count();
		if ($ordercount > 0) {
			$types = "product2";
			foreach ($hosts as $key => $val) {
				$affiliates = \think\Db::name("affiliates_products_setting")->where("pid", $val["id"])->find();
				if (!empty($affiliates) && $affiliates["affiliate_enabled"] == 2) {
					$arrs = getCommissioninvoicebyhost($hosts[$key], 1, $ladder, 0);
					$arr[] = $arrs;
				} else {
					if (configuration("affiliate_is_reorder") == 1) {
						$bates[] = configuration("affiliate_reorder");
						$nums = $val["amount"];
						if (configuration("affiliate_reorder_type") == 1) {
							if ($val["type"] != "discount" && $val["type"] != "setup" && $val["type"] != "promo") {
								if (!empty($ladder["turnover"]["turnover"])) {
									$sum = bcadd(bcadd(configuration("affiliate_reorder"), bcmul($ladder["turnover"]["bates"] / 100, $nums, 4), 4), $sum, 4);
								} else {
									$sum = bcadd(configuration("affiliate_reorder"), $sum, 4);
								}
							}
							$typed[] = 1;
						} else {
							if (!empty($ladder["turnover"]["turnover"])) {
								$sum = bcadd(bcadd(bcmul(configuration("affiliate_reorder") / 100, $nums, 4), bcmul($ladder["turnover"]["bates"] / 100, $nums, 4), 4), $sum, 4);
							} else {
								$sum = bcadd(bcmul(configuration("affiliate_reorder") / 100, $nums, 4), $sum, 4);
							}
							$typed[] = 2;
						}
						$arrs = getCommissioninvoicebyhost($hosts[$key], configuration("affiliate_reorder_type"), $ladder, configuration("affiliate_reorder"));
						$arr[] = $arrs;
					} else {
						return false;
						break;
					}
				}
			}
		} else {
			foreach ($hosts as $key => $val) {
				$affiliates = \think\Db::name("affiliates_products_setting")->where("pid", $val["id"])->find();
				if ($affiliates["affiliate_enabled"] == 1) {
					$bates[] = $affiliates["affiliate_bates"];
					$nums = $val["amount"];
					if ($affiliates["affiliate_type"] == 1) {
						if ($val["type"] != "discount" && $val["type"] != "setup" && $val["type"] != "promo") {
							if (!empty($ladder["turnover"]["turnover"])) {
								$sum = bcadd(bcadd($affiliates["affiliate_bates"], bcmul($ladder["turnover"]["bates"] / 100, $nums, 4), 4), $sum, 4);
							} else {
								$sum = bcadd($affiliates["affiliate_bates"], $sum, 4);
							}
						}
						$typed[] = 1;
					} else {
						if (!empty($ladder["turnover"]["turnover"])) {
							$sum = bcadd(bcadd(bcmul($affiliates["affiliate_bates"] / 100, $nums, 4), bcmul($ladder["turnover"]["bates"] / 100, $nums, 4), 4), $sum, 4);
						} else {
							$sum = bcadd(bcmul($affiliates["affiliate_bates"] / 100, $nums, 4), $sum, 4);
						}
						$typed[] = 2;
					}
					$arrs = getCommissioninvoicebyhost($hosts[$key], $affiliates["affiliate_type"], $ladder, $affiliates["affiliate_bates"]);
					$arr[] = $arrs;
				} elseif ($affiliates["affiliate_enabled"] == 2) {
					$arrs = getCommissioninvoicebyhost($hosts[$key], 1, $ladder, 0);
					$arr[] = $arrs;
				} else {
					$nums = $val["amount"];
					$bates[] = configuration("affiliate_bates");
					if (configuration("affiliate_type") == 1) {
						if ($val["type"] != "discount" && $val["type"] != "setup" && $val["type"] != "promo") {
							if (!empty($ladder["turnover"]["turnover"])) {
								$sum = bcadd(bcadd(configuration("affiliate_bates"), bcmul($ladder["turnover"]["bates"] / 100, $nums, 4), 4), $sum, 4);
							} else {
								$sum = bcadd(configuration("affiliate_bates"), $sum, 4);
							}
						}
						$typed[] = 1;
					} else {
						if (!empty($ladder["turnover"]["turnover"])) {
							$sum = bcadd(bcadd(bcmul(configuration("affiliate_bates") / 100, $nums, 4), bcmul($ladder["turnover"]["bates"] / 100, $nums, 4), 4), $sum, 4);
						} else {
							$sum = bcadd(bcmul(configuration("affiliate_bates") / 100, $nums, 4), $sum, 4);
						}
						$typed[] = 2;
					}
					$arrs = getCommissioninvoicebyhost($hosts[$key], configuration("affiliate_type"), $ladder, configuration("affiliate_bates"));
					$arr[] = $arrs;
				}
			}
		}
	} else {
		return false;
	}
	return ["sum" => $sum, "bates" => $bates[0], "type" => $types, "arr" => $arr, "types" => $typed[0]];
}
function getCommissioninvoicebyhost($hosts, $aff_type, $ladder, $bates)
{
	if ($aff_type == 1) {
		if (!empty($ladder["turnover"]["turnover"])) {
			if ($hosts["type"] != "discount" && $hosts["type"] != "setup" && $hosts["type"] != "promo") {
				$hosts["commission"] = round(bcadd($bates, bcmul($ladder["turnover"]["bates"] / 100, $hosts["amount"], 4), 4), 2);
			} else {
				$hosts["commission"] == 0.0;
			}
		} else {
			if ($hosts["type"] != "discount" && $hosts["type"] != "setup" && $hosts["type"] != "promo") {
				$hosts["commission"] = $bates;
			} else {
				$hosts["commission"] == 0.0;
			}
		}
		$hosts["commission_bates"] = $bates;
		$hosts["commission_bates_type"] = $aff_type;
	} else {
		if (!empty($ladder["turnover"]["turnover"])) {
			$hosts["commission"] = round(bcadd(bcmul($bates / 100, $hosts["amount"], 4), bcmul($ladder["turnover"]["bates"] / 100, $hosts["amount"], 4), 4), 2);
		} else {
			$hosts["commission"] = round(bcmul($bates / 100, $hosts["amount"], 4), 2);
		}
		$hosts["commission_bates"] = $bates;
		$hosts["commission_bates_type"] = $aff_type;
	}
	$str = $hosts["name"] . "(" . $hosts["domain"] . ")";
	$hosts["names"] = "<a class=\"el-link el-link--primary is-underline\" 
            href=\"#/customer-view/product-innerpage?hid=" . $hosts["hostid"] . "&id=" . $hosts["uid"] . "\">
            <span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
	switch ($hosts["type"]) {
		case "renew":
			$hosts["type"] = "续费";
			$hosts["names"] = "续费-" . $hosts["names"];
			break;
		case "host":
			$hosts["type"] = "产品";
			break;
		case "setup":
			$hosts["type"] = "初装";
			$hosts["names"] = "初装";
			break;
		case "promo":
			$hosts["type"] = "优惠码";
			$hosts["names"] = "优惠码";
			break;
		case "discount":
			$hosts["type"] = "客户折扣";
			$hosts["names"] = "客户折扣";
			break;
	}
	$do = config("domainstatus");
	$hosts["domainstatus"] = $do[$hosts["domainstatus"]];
	$hosts["commission"] = sprintf("%.2f", $hosts["commission"]);
	return $hosts;
}
function dealCommissionaff($rows, $ladder, $uid)
{
	$affiliates = \think\Db::name("affiliates_user_setting")->where("uid", $uid)->find();
	foreach ($rows as $k => $value) {
		if (configuration("affiliate_enabled") == 1) {
			if ($value["type"] == "renew") {
				if ($affiliates["affiliate_is_renew"] == 1) {
					if ($affiliates["affiliate_renew_type"] == 1) {
						if (!empty($ladder["turnover"]["turnover"])) {
							$rows[$k]["commission"] = bcadd($affiliates["affiliate_renew"], bcmul($ladder["turnover"]["bates"] / 100, $value["subtotal"], 4), 4);
						} else {
							$rows[$k]["commission"] = $affiliates["affiliate_renew"];
						}
						$rows[$k]["commission_bates"] = $affiliates["affiliate_renew"];
						$rows[$k]["commission_bates_type"] = 1;
					} else {
						if (!empty($ladder["turnover"]["turnover"])) {
							$rows[$k]["commission"] = bcadd(bcmul($affiliates["affiliate_renew"] / 100, $value["subtotal"], 2), bcmul($ladder["turnover"]["bates"] / 100, $value["subtotal"], 4), 4);
						} else {
							$rows[$k]["commission"] = bcmul($affiliates["affiliate_renew"] / 100, $value["subtotal"], 4);
						}
						$rows[$k]["commission_bates"] = $affiliates["affiliate_renew"];
						$rows[$k]["commission_bates_type"] = 2;
					}
				} else {
					$commission = getproductcommission($value["id"], $value["type"], $value["uid"], $value["create_time"], $ladder, $value["invoiceid"]);
					if ($commission === false) {
						$rows[$k]["commission"] = 0.0;
						$rows[$k]["commission_bates"] = 0.0;
						$rows[$k]["child"] = "";
						continue;
					}
					$rows[$k]["commission"] = $commission["sum"];
					$rows[$k]["commission_bates"] = $commission["bates"];
					$rows[$k]["commission_bates_type"] = $commission["types"];
				}
			} elseif ($value["type"] == "product") {
				$ordercount = \think\Db::name("orders")->alias("o")->join("clients c", "o.uid=c.id")->join("invoices i", "i.id=o.invoiceid")->field("o.id")->where("i.status", "in", "Paid,Refunded")->where("o.create_time", "<", $value["create_time"])->where("c.id", $value["uid"])->count();
				if ($ordercount > 0) {
					$rows[$k]["type"] = "product2";
					if ($affiliates["affiliate_is_reorder"] == 1) {
						if ($affiliates["affiliate_reorder_type"] == 1) {
							if (!empty($ladder["turnover"]["turnover"])) {
								$rows[$k]["commission"] = bcadd($affiliates["affiliate_reorder"], bcmul($ladder["turnover"]["bates"] / 100, $value["subtotal"], 4), 4);
							} else {
								$rows[$k]["commission"] = $affiliates["affiliate_reorder"];
							}
							$rows[$k]["commission_bates"] = $affiliates["affiliate_reorder"];
							$rows[$k]["commission_bates_type"] = 1;
						} else {
							if (!empty($ladder["turnover"]["turnover"])) {
								$rows[$k]["commission"] = bcadd(bcmul($affiliates["affiliate_reorder"] / 100, $value["subtotal"], 4), bcmul($ladder["turnover"]["bates"] / 100, $value["subtotal"], 4), 4);
							} else {
								$rows[$k]["commission"] = bcmul($affiliates["affiliate_reorder"] / 100, $value["subtotal"], 4);
							}
							$rows[$k]["commission_bates"] = $affiliates["affiliate_reorder"];
							$rows[$k]["commission_bates_type"] = 2;
						}
					} else {
						$commission = getproductcommission($value["id"], $value["type"], $value["uid"], $value["create_time"], $ladder, $value["invoiceid"]);
						if ($commission === false) {
							$rows[$k]["commission"] = 0.0;
							$rows[$k]["commission_bates"] = 0.0;
							$rows[$k]["child"] = "";
							continue;
						}
						$rows[$k]["commission"] = $commission["sum"];
						$rows[$k]["commission_bates"] = $commission["bates"];
						$rows[$k]["commission_bates_type"] = $commission["types"];
					}
				} else {
					if ($affiliates["affiliate_enabled"] == 1) {
						if ($affiliates["affiliate_type"] == 1) {
							if (!empty($ladder["turnover"]["turnover"])) {
								$rows[$k]["commission"] = bcadd($affiliates["affiliate_bates"], bcmul($ladder["turnover"]["bates"] / 100, $value["subtotal"], 4), 4);
							} else {
								$rows[$k]["commission"] = $affiliates["affiliate_bates"];
							}
							$rows[$k]["commission_bates"] = $affiliates["affiliate_bates"];
							$rows[$k]["commission_bates_type"] = 1;
						} else {
							if (!empty($ladder["turnover"]["turnover"])) {
								$rows[$k]["commission"] = bcadd(bcmul($affiliates["affiliate_bates"] / 100, $value["subtotal"], 4), bcmul($ladder["turnover"]["bates"] / 100, $value["subtotal"], 4), 4);
							} else {
								$rows[$k]["commission"] = bcmul($affiliates["affiliate_bates"] / 100, $value["subtotal"], 4);
							}
							$rows[$k]["commission_bates"] = $affiliates["affiliate_bates"];
							$rows[$k]["commission_bates_type"] = 2;
						}
					} else {
						$commission = getproductcommission($value["id"], $value["type"], $value["uid"], $value["create_time"], $ladder, $value["invoiceid"]);
						if ($commission === false) {
							$rows[$k]["commission"] = 0.0;
							$rows[$k]["commission_bates"] = 0.0;
							$rows[$k]["child"] = "";
							continue;
						}
						$rows[$k]["commission"] = $commission["sum"];
						$rows[$k]["commission_bates"] = $commission["bates"];
						$rows[$k]["commission_bates_type"] = $commission["types"];
					}
				}
			}
			if ($rows[$k]["is_aff"] == 1) {
				$amount_outs = \think\Db::name("accounts")->field("id,amount_out")->where("invoice_id", $value["invoiceid"])->where("refund", ">", 0)->where("aff_refund", "=", 0)->select()->toArray();
				$credit = \think\Db::name("credit")->field("id,amount")->where("relid", $value["invoiceid"])->where("description", "like", "%Removed%")->where("aff_refund", "=", 0)->select()->toArray();
				$refund2 = $refund1 = $refund = $amount_out = $amount_out1 = 0;
				if (!empty($amount_outs[0]) || !empty($credit[0])) {
					foreach ($amount_outs as $k => $v) {
						$amount_out = bcadd($v["amount_out"], $amount_out, 2);
						if ($rows[$k]["aff_commmission_bates_type"] == 1) {
							$re = sprintf("%.2f", $v["amount_out"]);
						} else {
							$re = sprintf("%.2f", bcmul($rows[$k]["aff_commmission_bates"] / 100, $v["amount_out"], 2));
						}
						$refund1 = bcadd($re, $refund1, 2);
						if ($rows[$k]["is_aff"] == 1) {
							\think\Db::name("accounts")->where("id", $v["id"])->update(["aff_refund" => 1]);
						}
					}
					foreach ($credit as $k => $v) {
						$amount_out1 = bcadd($v["amount"], $amount_out1, 2);
						if ($rows[$k]["aff_commmission_bates_type"] == 1) {
							$re = sprintf("%.2f", $v["amount"]);
						} else {
							$re = sprintf("%.2f", bcmul($rows[$k]["aff_commmission_bates"] / 100, $v["amount"], 2));
						}
						$refund2 = bcadd($re, $refund2, 2);
						if ($rows[$k]["is_aff"] == 1) {
							\think\Db::name("credit")->where("id", $v["id"])->update(["aff_refund" => 1]);
						}
					}
				}
				$refo = bcadd($refund1, -$refund2, 2);
				if ($refo > 0) {
					if ($rows[$k]["is_aff"] == 1) {
						$affi = \think\Db::name("affiliates")->where("uid", $uid)->find();
						$affcomm = bcsub($rows[$k]["aff_commission"], $refo, 2) > 0 ? bcsub($rows[$k]["aff_commission"], $refo, 2) : 0.0;
						if ($rows[$k]["aff_commission"] < $refo) {
							$refo = $rows[$k]["aff_commission"];
						}
						$affbala = bcsub($affi["balance"], $refo, 2) > 0 ? bcsub($affi["balance"], $refo, 2) : 0.0;
						\think\Db::name("invoices")->where("id", $value["invoiceid"])->update(["aff_commission" => $affcomm]);
						$res = \think\Db::name("affiliates")->where("id", $affi["id"])->update(["balance" => $affbala, "updated_time" => time()]);
					}
				}
			}
			$amount_out3 = \think\Db::name("credit")->field("id,amount")->where("relid", $value["invoiceid"])->where("description", "like", "%Removed%")->sum("amount");
			$amount_out2 = \think\Db::name("accounts")->field("id,amount_out")->where("invoice_id", $value["invoiceid"])->where("refund", ">", 0)->sum("amount_out");
			$amount_outs = bcadd(-$amount_out3, $amount_out2, 2);
			if ($rows[$k]["is_aff"] == 1) {
				if ($rows[$k]["aff_commmission_bates_type"] == 1) {
					$refund = $amount_outs;
				} else {
					$refund = bcmul($rows[$k]["aff_commmission_bates"] / 100, $amount_outs, 2);
				}
				if ($rows[$k]["commission"] < $refund) {
					$refund = $rows[$k]["commission"];
				}
			} else {
				if ($rows[$k]["commission_bates_type"] == 1) {
					$refund = $amount_outs;
				} else {
					$refund = bcmul($rows[$k]["commission_bates"] / 100, $amount_outs, 2);
				}
				if ($rows[$k]["commission"] < $refund) {
					$refund = $rows[$k]["commission"];
				}
			}
			$sum = round(bcsub($rows[$k]["commission"], $refund, 2), 2);
			if ($refund > 0) {
				$rows[$k]["is_refound"] = 1;
				$rows[$k]["refound"] = "金额-" . sprintf("%.2f", $amount_outs) . ",佣金-" . $refund;
				$rows[$k]["refounds"] = $refund;
			}
			if ($sum < 0) {
				$sum = 0;
			}
			$rows[$k]["commission"] = $sum;
		} else {
			$rows[$k]["commission"] = 0.0;
			$rows[$k]["commission_bates"] = 0.0;
		}
	}
	$rows = array_values($rows);
	return $rows;
}
function dealCommissionaffs($rows, $ladder, $id)
{
	$affiliates = \think\Db::name("affiliates_user_setting")->where("uid", $id)->find();
	$affiliate_enabled = configuration("affiliate_enabled");
	foreach ($rows as $k => $value) {
		if ($affiliate_enabled == 1) {
			if ($value["type"] == "renew") {
				if ($affiliates["affiliate_is_renew"] == 1) {
					if ($affiliates["affiliate_renew_type"] == 1) {
						if (!empty($ladder["turnover"]["turnover"])) {
							$rows[$k]["commission"] = bcadd($affiliates["affiliate_renew"], bcmul($ladder["turnover"]["bates"] / 100, $value["subtotal"], 4), 4);
						} else {
							$rows[$k]["commission"] = $affiliates["affiliate_renew"];
						}
						$rows[$k]["commission_bates"] = $affiliates["affiliate_renew"];
						$rows[$k]["commission_bates_type"] = 1;
					} else {
						if (!empty($ladder["turnover"]["turnover"])) {
							$rows[$k]["commission"] = bcadd(bcmul($affiliates["affiliate_renew"] / 100, $value["subtotal"], 4), bcmul($ladder["turnover"]["bates"] / 100, $value["subtotal"], 4), 4);
						} else {
							$rows[$k]["commission"] = bcmul($affiliates["affiliate_renew"] / 100, $value["subtotal"], 4);
						}
						$rows[$k]["commission_bates"] = $affiliates["affiliate_renew"];
						$rows[$k]["commission_bates_type"] = 2;
					}
					$rows[$k]["child"] = getcommissioninvoice($value["invoiceid"], "renew,promo,discount", $affiliates["affiliate_renew_type"], $ladder, $affiliates["affiliate_renew"], $value["invoiceid"]);
				} else {
					$commission = getproductcommissioninvoice($value["id"], $value["type"], $value["uid"], $value["create_time"], $ladder, $value["invoiceid"]);
					if ($commission === false) {
						$rows[$k]["commission"] = 0.0;
						$rows[$k]["commission_bates"] = 0.0;
						$rows[$k]["child"] = "";
						continue;
					}
					$rows[$k]["commission"] = $commission["sum"];
					$rows[$k]["commission_bates"] = $commission["bates"];
					$rows[$k]["commission_bates_type"] = $commission["types"];
					$rows[$k]["child"] = $commission["arr"];
				}
			} elseif ($value["type"] == "product") {
				$ordercount = \think\Db::name("orders")->alias("o")->join("clients c", "o.uid=c.id")->join("invoices i", "i.id=o.invoiceid")->field("o.id")->where("i.status", "in", "Paid,Refunded")->where("o.create_time", "<", $value["create_time"])->where("c.id", $value["uid"])->count();
				if ($ordercount > 0) {
					$rows[$k]["type"] = "product2";
					if ($affiliates["affiliate_is_reorder"] == 1) {
						if ($affiliates["affiliate_reorder_type"] == 1) {
							if (!empty($ladder["turnover"]["turnover"])) {
								$rows[$k]["commission"] = bcadd($affiliates["affiliate_reorder"], bcmul($ladder["turnover"]["bates"] / 100, $value["subtotal"], 4), 4);
							} else {
								$rows[$k]["commission"] = $affiliates["affiliate_reorder"];
							}
							$rows[$k]["commission_bates"] = $affiliates["affiliate_reorder"];
							$rows[$k]["commission_bates_type"] = 1;
						} else {
							if (!empty($ladder["turnover"]["turnover"])) {
								$rows[$k]["commission"] = bcadd(bcmul($affiliates["affiliate_reorder"] / 100, $value["subtotal"], 4), bcmul($ladder["turnover"]["bates"] / 100, $value["subtotal"], 4), 4);
							} else {
								$rows[$k]["commission"] = bcmul($affiliates["affiliate_reorder"] / 100, $value["subtotal"], 4);
							}
							$rows[$k]["commission_bates"] = $affiliates["affiliate_reorder"];
							$rows[$k]["commission_bates_type"] = 2;
						}
						$rows[$k]["child"] = getcommissioninvoice($value["invoiceid"], "host,setup,promo,discount", $affiliates["affiliate_reorder_type"], $ladder, $affiliates["affiliate_reorder"]);
					} else {
						$commission = getproductcommissioninvoice($value["id"], $value["type"], $value["uid"], $value["create_time"], $ladder, $value["invoiceid"]);
						if ($commission === false) {
							$rows[$k]["commission"] = 0.0;
							$rows[$k]["commission_bates"] = 0.0;
							$rows[$k]["child"] = "";
							continue;
						}
						$rows[$k]["commission"] = $commission["sum"];
						$rows[$k]["commission_bates"] = $commission["bates"];
						$rows[$k]["commission_bates_type"] = $commission["types"];
						$rows[$k]["child"] = $commission["arr"];
					}
				} else {
					if ($affiliates["affiliate_enabled"] == 1) {
						if ($affiliates["affiliate_type"] == 1) {
							if (!empty($ladder["turnover"]["turnover"])) {
								$rows[$k]["commission"] = bcadd($affiliates["affiliate_bates"], bcmul($ladder["turnover"]["bates"] / 100, $value["subtotal"], 4), 4);
							} else {
								$rows[$k]["commission"] = $affiliates["affiliate_bates"];
							}
							$rows[$k]["commission_bates"] = $affiliates["affiliate_bates"];
							$rows[$k]["commission_bates_type"] = 1;
						} else {
							if (!empty($ladder["turnover"]["turnover"])) {
								$rows[$k]["commission"] = bcadd(bcmul($affiliates["affiliate_bates"] / 100, $value["subtotal"], 4), bcmul($ladder["turnover"]["bates"] / 100, $value["subtotal"], 4), 4);
							} else {
								$rows[$k]["commission"] = bcmul($affiliates["affiliate_bates"] / 100, $value["subtotal"], 4);
							}
							$rows[$k]["commission_bates"] = $affiliates["affiliate_bates"];
							$rows[$k]["commission_bates_type"] = 2;
						}
						$rows[$k]["child"] = getcommissioninvoice($value["invoiceid"], "host,setup,promo,discount", $affiliates["affiliate_renew_type"], $ladder, $affiliates["affiliate_renew"]);
					} else {
						$commission = getproductcommissioninvoice($value["id"], $value["type"], $value["uid"], $value["create_time"], $ladder, $value["invoiceid"]);
						if ($commission === false) {
							$rows[$k]["commission"] = 0.0;
							$rows[$k]["commission_bates"] = 0.0;
							$rows[$k]["child"] = "";
							continue;
						}
						$rows[$k]["commission"] = $commission["sum"];
						$rows[$k]["commission_bates"] = $commission["bates"];
						$rows[$k]["commission_bates_type"] = $commission["types"];
						$rows[$k]["child"] = $commission["arr"];
					}
				}
			}
			if ($rows[$k]["is_aff"] == 1) {
				$amount_outs = \think\Db::name("accounts")->field("id,amount_out")->where("invoice_id", $value["invoiceid"])->where("refund", ">", 0)->where("aff_refund", "=", 0)->select()->toArray();
				$credit = \think\Db::name("credit")->field("id,amount")->where("relid", $value["invoiceid"])->where("description", "like", "%Removed%")->where("aff_refund", "=", 0)->select()->toArray();
				$refund2 = $refund1 = $refund = $amount_out = $amount_out1 = 0;
				if (!empty($amount_outs[0]) || !empty($credit[0])) {
					foreach ($amount_outs as $k => $v) {
						$amount_out = bcadd($v["amount_out"], $amount_out, 2);
						if ($rows[$k]["aff_commmission_bates_type"] == 1) {
							$re = sprintf("%.2f", $v["amount_out"]);
						} else {
							$re = sprintf("%.2f", bcmul($rows[$k]["aff_commmission_bates"] / 100, $v["amount_out"], 2));
						}
						$refund1 = bcadd($re, $refund1, 2);
						if ($rows[$k]["is_aff"] == 1) {
							\think\Db::name("accounts")->where("id", $v["id"])->update(["aff_refund" => 1]);
						}
					}
					foreach ($credit as $k => $v) {
						$amount_out1 = bcadd($v["amount"], $amount_out1, 2);
						if ($rows[$k]["aff_commmission_bates_type"] == 1) {
							$re = sprintf("%.2f", $v["amount"]);
						} else {
							$re = sprintf("%.2f", bcmul($rows[$k]["aff_commmission_bates"] / 100, $v["amount"], 2));
						}
						$refund2 = bcadd($re, $refund2, 2);
						if ($rows[$k]["is_aff"] == 1) {
							\think\Db::name("credit")->where("id", $v["id"])->update(["aff_refund" => 1]);
						}
					}
				}
				$refo = bcadd($refund1, -$refund2, 2);
				if ($refo > 0) {
					if ($rows[$k]["is_aff"] == 1) {
						$affi = \think\Db::name("affiliates")->where("uid", $value["uid"])->find();
						$affcomm = bcsub($rows[$k]["aff_commission"], $refo, 2) > 0 ? bcsub($rows[$k]["aff_commission"], $refo, 2) : 0.0;
						if ($rows[$k]["aff_commission"] < $refo) {
							$refo = $rows[$k]["aff_commission"];
						}
						$affbala = bcsub($affi["balance"], $refo, 2) > 0 ? bcsub($affi["balance"], $refo, 2) : 0.0;
						\think\Db::name("invoices")->where("id", $value["invoiceid"])->update(["aff_commission" => $affcomm]);
						$res = \think\Db::name("affiliates")->where("id", $affi["id"])->update(["balance" => $affbala, "updated_time" => time()]);
					}
				}
			}
			$amount_out3 = \think\Db::name("credit")->field("id,amount")->where("relid", $value["invoiceid"])->where("description", "like", "%Removed%")->sum("amount");
			$amount_out2 = \think\Db::name("accounts")->field("id,amount_out")->where("invoice_id", $value["invoiceid"])->where("refund", ">", 0)->sum("amount_out");
			$amount_outs = bcadd(-$amount_out3, $amount_out2, 2);
			if (!empty($rows[$k]["aff_commmission_bates_type"])) {
				if ($rows[$k]["aff_commmission_bates_type"] == 1) {
					$refund = $amount_outs;
				} else {
					$refund = bcmul($rows[$k]["aff_commmission_bates"] / 100, $amount_outs, 2);
				}
				if ($rows[$k]["commission"] < $refund) {
					$refund = $rows[$k]["commission"];
				}
			} else {
				if ($rows[$k]["commission_bates_type"] == 1) {
					$refund = $amount_outs;
				} else {
					$refund = bcmul($rows[$k]["commission_bates"] / 100, $amount_outs, 2);
				}
				if ($rows[$k]["commission"] < $refund) {
					$refund = $rows[$k]["commission"];
				}
			}
			$sum = round(bcsub($rows[$k]["commission"], $refund, 2), 2);
			if ($refund > 0) {
				$rows[$k]["is_refound"] = 1;
				$rows[$k]["refound"] = "金额-" . sprintf("%.2f", $amount_outs) . ",佣金-" . $refund;
				$rows[$k]["refounds"] = $refund;
			}
			if ($sum < 0) {
				$sum = 0;
			}
			$rows[$k]["commission"] = $sum;
		} else {
			$rows[$k]["commission"] = 0.0;
			$rows[$k]["commission_bates"] = 0.0;
			$rows[$k]["commission_bates_type"] = 0;
			$rows[$k]["child"] = getcommissioninvoice($value["invoiceid"], "renew,host,setup,promo,discount", $affiliates["affiliate_renew_type"], $ladder, $affiliates["affiliate_renew"]);
		}
	}
	$rows = array_values($rows);
	return $rows;
}
function get_zip_originalsize($filename)
{
	$size = 0;
	$resource = zip_open($filename);
	while ($dir_resource = zip_read($resource)) {
		$size += zip_entry_filesize($dir_resource);
	}
	zip_close($resource);
	return $size;
}
function shd_new_is_writeable($file)
{
	if (is_dir($file)) {
		$dir = $file;
		if ($fp = @fopen("{$dir}/test.txt", "w")) {
			@fclose($fp);
			@unlink("{$dir}/test.txt");
			$writeable = true;
		} else {
			$writeable = false;
		}
	} else {
		if ($fp = @fopen($file, "a+")) {
			@fclose($fp);
			$writeable = true;
		} else {
			$writeable = false;
		}
	}
	return $writeable;
}
function judgeYesNo($option_type)
{
	if ($option_type == 3) {
		return true;
	}
	return false;
}
function judgeOs($option_type)
{
	if ($option_type == 5) {
		return true;
	}
	return false;
}
function judgeQuantity($option_type)
{
	if ($option_type == 4 || $option_type == 7 || $option_type == 9 || $option_type == 11 || $option_type == 14 || $option_type == 15 || $option_type == 16 || $option_type == 17 || $option_type == 18 || $option_type == 19) {
		return true;
	}
	return false;
}
function judgeQuantityStage($option_type)
{
	if ($option_type == 15 || $option_type == 16 || $option_type == 17 || $option_type == 18 || $option_type == 19) {
		return true;
	}
	return false;
}
function judgeNoc($option_type)
{
	if ($option_type == 12) {
		return true;
	}
	return false;
}
function judgeBw($option_type)
{
	if ($option_type == 10 || $option_type == 11) {
		return true;
	}
	return false;
}
function configoptionsUnit($option_type)
{
	if (judgebw($option_type)) {
		return " Mbps ";
	} else {
		if ($option_type == 4) {
			return " x 1";
		} else {
			return "";
		}
	}
}
function getRandch($num)
{
	$str = "";
	for ($i = 0; $i < $num; $i++) {
		$str .= chr(rand(65, 90));
	}
	$aff1 = \think\Db::name("affiliates")->where("url_identy", $str)->count();
	if ($aff1 > 0) {
		getRandch($num);
	}
	return $str;
}
function generateHostName($prefix, $rule = [], $host_show = 0)
{
	if (!is_array($rule) || empty($rule)) {
		return "S" . randstr(12);
	}
	if ($host_show == 0) {
		$prefix = "ser";
	}
	$prefix = $prefix ?? "cloud";
	$str_upper = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$str_lower = strtolower($str_upper);
	$str_num = "0123456789";
	$randstr = $str = "";
	if ($rule["upper"]) {
		$str .= $str_upper;
	}
	if ($rule["lower"]) {
		$str .= $str_lower;
	}
	if ($rule["num"]) {
		$str .= $str_num;
	}
	if (empty($str)) {
		$str = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
	}
	$len = strlen($str) - 1;
	if (empty($rule["len_num"])) {
		$length = 12;
	} else {
		$length = $rule["len_num"];
	}
	if ($length > 20) {
		$length = 20;
	}
	if ($length < 4) {
		$length = 12;
	}
	if ($len < $length) {
		$n = ceil($length / $len);
		for ($j = 0; $j < $n; $j++) {
			$str .= $str;
		}
		$len = strlen($str) - 1;
	}
	for ($i = 0; $i < $len; $i++) {
		$num = mt_rand(0, $len);
		$randstr .= $str[$num];
	}
	return $prefix . substr($randstr, 0, $length);
}
function generateHostPassword($rule = [], $type = "")
{
	if (!is_array($rule) || empty($rule)) {
		return "";
	}
	if (empty($rule["rule"]["len_num"])) {
		$length = 12;
	} else {
		$length = $rule["rule"]["len_num"];
	}
	if ($length > 20) {
		$length = 20;
	}
	if ($length < 6) {
		$length = 6;
	}
	$upper = $rule["rule"]["upper"] ?? 1;
	$lower = $rule["rule"]["lower"] ?? 1;
	$num = $rule["rule"]["num"] ?? 1;
	$special = $rule["rule"]["special"] ?? 0;
	$upper_default = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$lower_default = "abcdefghijklmnopqrstuvwxyz";
	$num_default = "0123456789";
	$special_default = "~@#\$&*(){}[]|";
	$arr = [];
	if ($upper) {
		array_push($arr, $upper_default);
	}
	if ($lower) {
		array_push($arr, $lower_default);
	}
	if ($num) {
		array_push($arr, $num_default);
	}
	if ($type != "dcimcloud" && $special) {
		array_push($arr, $special_default);
	}
	$randstr = "";
	$count = count($arr);
	for ($i = 0; $i < $count; $i++) {
		$randstr .= $arr[$i][mt_rand(0, strlen($arr[$i]) - 1)];
	}
	$str = implode("", $arr);
	$len = strlen($str) - 1;
	$randstr2 = "";
	for ($j = 0; $j < $len; $j++) {
		$randstr2 .= $str[mt_rand(0, $len)];
	}
	$randstr2 = substr($randstr2, 0, $length - $count);
	$randstr .= $randstr2;
	return str_shuffle($randstr);
}
function verifyHostname($pid, $hostname)
{
	$flag = true;
	if (empty($pid)) {
		$flag = false;
	}
	$msg = "主机名";
	$product = \think\Db::name("products")->field("host")->where("id", $pid)->find();
	$host = json_decode($product["host"], true);
	$prefix = $host["prefix"];
	$msg .= "前缀必须是{$prefix};";
	if (strpos($hostname, $prefix) !== 0) {
		$flag = false;
	}
	$rule = $host["rule"];
	if ($rule["upper"]) {
		$msg .= "含大写字母;";
		if (!preg_match("/[A-Z]/", $hostname)) {
			$flag = false;
		}
	}
	if ($rule["lower"]) {
		$msg .= "含小写字母;";
		if (!preg_match("/[a-z]/", $hostname)) {
			$flag = false;
		}
	}
	if ($rule["num"] && !preg_match("/[0-9]/", $hostname)) {
		$msg .= "含数字;";
		if (!preg_match("/[0-9]/", $hostname)) {
			$flag = false;
		}
	}
	$len = intval($rule["len_num"]) + strlen($prefix);
	$msg .= "长度为{$len};";
	if ($rule["len_num"] != strlen(substr($hostname, strlen($prefix)))) {
		$flag = false;
	}
	if ($flag) {
		return ["status" => 200];
	} else {
		return ["status" => 400, "msg" => $msg];
	}
}
/**
 * 生成随机码
 * wyh
 * @param $length:随机码长度:$length>=6
 * @param $type:类型：0大小写字母+数字,1大小写字母+数字+特殊字符
 * @return string
 */
function randStrToPass($length = 8, $type = 0)
{
	if ($length < 6) {
		$length = mt_rand(6, 32);
	}
	if ($type == 0) {
		$arr = ["abcdefghijklmnopqrstuvwxyz", "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "0123456789"];
	} else {
		$arr = ["abcdefghijklmnopqrstuvwxyz", "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "0123456789", "~@#\$%^*(){}[]|"];
	}
	$randstr = "";
	$count = count($arr);
	for ($i = 0; $i < $count; $i++) {
		$randstr .= $arr[$i][mt_rand(0, strlen($arr[$i]) - 1)];
	}
	$str = implode("", $arr);
	if (strlen($str) < $length) {
		$n = floor($length / strlen($str));
		$str = str_repeat($str, $n);
		$new_len = strlen($str);
		if ($new_len < $length) {
			$str .= str_repeat("a", $length - $new_len);
		}
	}
	$len = strlen($str) - 1;
	$randstr2 = "";
	for ($j = 0; $j < $len; $j++) {
		$randstr2 .= $str[mt_rand(0, $len)];
	}
	$randstr2 = substr($randstr2, 0, $length - $count);
	$randstr .= $randstr2;
	return str_shuffle($randstr);
}
function getSaleProductUser($pid, $uid)
{
	$tmp = \think\Db::name("user_product_bates")->alias("a")->field("a.type,a.bates")->leftJoin("user_products b", "a.products = b.gid")->leftJoin("clients c", "a.user = c.groupid")->where("c.id", $uid)->where("b.pid", $pid)->find();
	return $tmp ?: false;
}
/**
 * 引用获取省/市/区(性能高,占用内存空间小)
 * @param $items:区域数据
 * @return bool|mixed|string
 */
function getStructuredTree($list)
{
	$tree = [];
	foreach ($list as &$item) {
		$parent_id = $item["pid"];
		$item["son"] = [];
		if (isset($list[$parent_id - 2]) && !empty($list[$parent_id - 2])) {
			$list[$parent_id - 2]["son"][] =& $item;
		} else {
			$tree[] =& $item;
		}
	}
	return $tree;
}
function getDiscount($hostid, $amount, $type = 1)
{
	$host_data = \think\Db::name("host")->field("id,uid,productid,flag")->where("id", $hostid)->find();
	$uid = $host_data["uid"];
	$pid = $host_data["productid"];
	if ($type == 3) {
		if ($host_data["flag"] == 0) {
			$flag = getsaleproductuser($pid, $uid);
			if ($flag) {
				if ($flag["type"] == 1) {
					$v["saleproducts"] = bcmul($amount, 1 - $flag["bates"] / 100);
					$v["amount"] = round(bcsub($amount, bcmul($amount, 1 - $flag["bates"] / 100)), 2);
				} elseif ($flag["type"] == 2) {
					$v["saleproducts"] = $flag["bates"];
					$v["amount"] = round(bcsub($amount, $flag["bates"]), 2) > 0 ? round(bcsub($amount, $flag["bates"]), 2) : 0;
				} else {
					$v["saleproducts"] = $flag["bates"];
					$v["amount"] = round(bcsub($amount, $flag["bates"]), 2) > 0 ? round(bcsub($amount, $flag["bates"]), 2) : 0;
				}
			}
		}
	} else {
		$flag = getsaleproductuser($pid, $uid);
		if ($flag) {
			if ($flag["type"] == 1) {
				$bates = 1 - $flag["bates"] / 100;
				if ($type == 1) {
					$v["saleproducts"] = sprintf("%.2f", bcsub($amount / (1 - $bates), $amount));
					$v["amount"] = $amount;
				} else {
					$v["amount"] = $amount;
					$v["saleproducts"] = sprintf("%.2f", bcsub($amount / (1 - $bates), $amount));
				}
			} elseif ($flag["type"] == 2) {
				$bates = $flag["bates"];
				if ($type == 1) {
					$v["saleproducts"] = $bates;
					$v["amount"] = $amount;
				} else {
					$v["saleproducts"] = $bates;
					$v["amount"] = $amount;
				}
			} else {
				$bates = $flag["bates"];
				if ($type == 1) {
					$v["saleproducts"] = $bates;
					$v["amount"] = $amount;
				} else {
					$v["saleproducts"] = $bates;
					$v["amount"] = $amount;
				}
			}
		}
	}
	return $v;
}
function getDiscounts($pid, $uid, $amount)
{
	$flag = getsaleproductuser($pid, $uid);
	if ($flag) {
		if ($flag["type"] == 1) {
			$bates = 1 - $flag["bates"] / 100;
			$v["saleproducts"] = sprintf("%.2f", bcmul($bates, $amount));
			$v["amount"] = bcsub($amount, $v["saleproducts"], 4) > 0 ? sprintf("%.2f", bcsub($amount, $v["saleproducts"], 4)) : 0.0;
		} elseif ($flag["type"] == 2) {
			$bates = $flag["bates"];
			$v["saleproducts"] = $bates;
			$v["amount"] = bcsub($amount, $bates, 4) > 0 ? sprintf("%.2f", bcsub($amount, $bates, 4)) : 0.0;
		} else {
			$bates = $flag["bates"];
			$v["saleproducts"] = $bates;
			$v["amount"] = bcsub($amount, $bates, 4) > 0 ? sprintf("%.2f", bcsub($amount, $bates, 4)) : 0.0;
		}
	}
	return $v;
}
function judgeOntrialNum($pid, $uid, $qty, $admin = false, $cart = false, $edit = -1)
{
	if (!$pid) {
		return false;
	}
	$pro = \think\Db::name("products")->field("pay_type,api_type,upstream_pid,zjmf_api_id")->where("id", $pid)->find();
	$pay_type = $pro["pay_type"];
	$pay_type = json_decode($pay_type, true);
	$pay_ontrial_num = $pay_type["pay_ontrial_num"] ?? 1;
	$pay_ontrial_num_rule = !getEdition() ? 0 : $pay_type["pay_ontrial_num_rule"] ?? 0;
	$whereMap = [];
	if ($pay_ontrial_num_rule) {
		$whereMap["a.domainstatus"] = "Active";
	}
	$host_ontrial_count = \think\Db::name("host")->alias("a")->where("a.uid", $uid)->where("a.productid", $pid)->where("a.billingcycle", "ontrial")->where($whereMap)->count();
	$host_ontrial_count = $host_ontrial_count ?? 0;
	if (!$admin) {
		$cart_data = \think\Db::name("cart_session")->where("uid", $uid)->value("cart_data");
		$cart_data = json_decode($cart_data, true);
		$products = $cart_data["products"] ?? [];
		if ($edit >= 0) {
			$products[intval($edit)]["qty"] = 0;
		}
		foreach ($products as $product) {
			if ($product["pid"] == $pid && $product["billingcycle"] == "ontrial") {
				$host_ontrial_count += $product["qty"] ?? 1;
			}
		}
	}
	if (!$cart) {
		$host_ontrial_count += intval($qty);
	}
	$api = \think\Db::name("api_user_product")->field("ontrial,qty")->where("uid", $uid)->where("pid", $pid)->find();
	if (!empty($api)) {
		$pay_ontrial_num = intval($api["ontrial"]);
	}
	if ($pro["api_type"] == "zjmf_api") {
		$res = zjmfCurl($pro["zjmf_api_id"], "cart/ontrialmax", ["pid" => $pro["upstream_pid"]], 5, "GET");
		if (!empty($res["data"][0]) && $res["data"]["product"]["api_type"] != "resource") {
			$pay_ontrial_num = intval($res["data"]["product"]["ontrial"]);
		}
	}
	if ($host_ontrial_count <= $pay_ontrial_num) {
		return true;
	} else {
		return false;
	}
}
function getUnitByOptionType($option_type)
{
	if (!$option_type) {
		return "";
	}
	$unit = config("configurable_option_type_name")[$option_type - 1]["unit"];
	return $unit;
}
function batch_curl($data, $request = "POST", $timeout = 30)
{
	$request = strtoupper($request);
	$queue = curl_multi_init();
	$map = [];
	foreach ($data as $k => $v) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $v["url"]);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_NOSIGNAL, true);
		if ($request == "GET") {
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPGET, 1);
		}
		if ($request == "POST") {
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			if (is_array($v["data"])) {
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($v["data"]));
			} else {
				curl_setopt($ch, CURLOPT_POSTFIELDS, $v["data"]);
			}
		}
		if ($request == "PUT" || $request == "DELETE") {
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request);
			if (is_array($v["data"])) {
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($v["data"]));
			} else {
				curl_setopt($ch, CURLOPT_POSTFIELDS, $v["data"]);
			}
		}
		if (!empty($v["header"])) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $v["header"]);
		}
		curl_multi_add_handle($queue, $ch);
		$map[$k] = $ch;
	}
	$active = null;
	do {
		$mrc = curl_multi_exec($queue, $active);
	} while ($mrc == CURLM_CALL_MULTI_PERFORM);
	while ($active > 0 && $mrc == CURLM_OK) {
		if (curl_multi_select($queue, 1) != -1) {
			do {
				$mrc = curl_multi_exec($queue, $active);
			} while ($mrc == CURLM_CALL_MULTI_PERFORM);
		}
	}
	$responses = [];
	foreach ($map as $k => $ch) {
		$res = curl_multi_getcontent($ch);
		$info = curl_getinfo($ch);
		$error = curl_error($ch);
		$curl_errno = curl_errno($ch);
		if (!empty($error)) {
			$output["status"] = 500;
			$output["http_code"] = $info["http_code"];
			$output["msg"] = $error;
			$responses[$k] = $output;
		} else {
			$responses[$k] = ["status" => 200, "http_code" => $info["http_code"], "data" => json_decode($res, true) ?: []];
		}
		curl_multi_remove_handle($queue, $ch);
		curl_close($ch);
	}
	curl_multi_close($queue);
	return $responses;
}
function checkip($ip)
{
	if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
		return true;
	} else {
		return false;
	}
}
function unsuspendAfterCertify($uid)
{
	if (checkcertify($uid)) {
		$hosts = \think\Db::name("host")->field("id,suspendreason,nextduedate,billingcycle")->where("domainstatus", "Suspended")->where("uid", $uid)->select()->toArray();
		$host_logic = new \app\common\logic\Host();
		foreach ($hosts as $host) {
			if (explode("-", $host["suspendreason"])[1] && (explode("-", $host["suspendreason"])[0] == "uncertifi" || explode("-", $host["suspendreason"])[0] == "其他")) {
				if ($host["billingcycle"] == "free" || $host["billingcycle"] == "onetime") {
					$res = $host_logic->unsuspend($host["id"], true);
					if ($res["status"] == 200) {
						active_log("解除暂停ID为{$host["id"]}的产品成功", $uid, 0);
					} else {
						active_log("解除暂停ID为{$host["id"]}的产品失败,失败原因:" . $res["msg"], $uid, 0);
					}
					$logic_run_map = new \app\common\logic\RunMap();
					$model_host = new \app\common\model\HostModel();
					$data_i = [];
					$data_i["host_id"] = $host["id"];
					$data_i["active_type_param"] = [$host["id"], 1, "", 0];
					$is_zjmf = $model_host->isZjmfApi($data_i["host_id"]);
					if ($res["status"] == 200) {
						$data_i["description"] = " 实名认证完成 - 解除暂停 Host ID:{$data_i["host_id"]}的产品成功";
						if ($is_zjmf) {
							$logic_run_map->saveMap($data_i, 1, 400, 3);
						}
						if (!$is_zjmf) {
							$logic_run_map->saveMap($data_i, 1, 300, 3);
						}
					} else {
						$data_i["description"] = " 实名认证完成 - 解除暂停 Host ID:{$data_i["host_id"]}的产品失败：{$res["msg"]}";
						if ($is_zjmf) {
							$logic_run_map->saveMap($data_i, 0, 400, 3);
						}
						if (!$is_zjmf) {
							$logic_run_map->saveMap($data_i, 0, 300, 3);
						}
					}
				} else {
					if ($host["nextduedate"] > time()) {
						$res = $host_logic->unsuspend($host["id"], true);
						if ($res["status"] == 200) {
							active_log("解除暂停ID为{$host["id"]}的产品成功", $uid, 0);
						} else {
							active_log("解除暂停ID为{$host["id"]}的产品失败,失败原因:" . $res["msg"], $uid, 0);
						}
						$logic_run_map = new \app\common\logic\RunMap();
						$model_host = new \app\common\model\HostModel();
						$data_i = [];
						$data_i["host_id"] = $host["id"];
						$data_i["active_type_param"] = [$host["id"], 1, "", 0];
						$is_zjmf = $model_host->isZjmfApi($data_i["host_id"]);
						if ($res["status"] == 200) {
							$data_i["description"] = " 实名认证完成 - 解除暂停 Host ID:{$data_i["host_id"]}的产品成功";
							if ($is_zjmf) {
								$logic_run_map->saveMap($data_i, 1, 400, 3);
							}
							if (!$is_zjmf) {
								$logic_run_map->saveMap($data_i, 1, 300, 3);
							}
						} else {
							$data_i["description"] = " 实名认证完成 - 解除暂停 Host ID:{$data_i["host_id"]}的产品失败：{$res["msg"]}";
							if ($is_zjmf) {
								$logic_run_map->saveMap($data_i, 0, 400, 3);
							}
							if (!$is_zjmf) {
								$logic_run_map->saveMap($data_i, 0, 300, 3);
							}
						}
					}
				}
			}
		}
	}
	return null;
}
function httpstothree($appcode, $querys, $num)
{
	$host = "https://api11.aliyun.venuscn.com";
	$path = "/cert/bank-card/" . $num;
	$method = "GET";
	$appcode = $appcode;
	$headers = [];
	array_push($headers, "Authorization:APPCODE " . $appcode);
	$bodys = "";
	$url = $host . $path . "?" . $querys;
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($curl, CURLOPT_FAILONERROR, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HEADER, false);
	if (1 == strpos("\$" . $host, "https://")) {
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	}
	$result = curl_exec($curl);
	curl_close($curl);
	return json_decode($result, true);
}
function objtoarr($obj)
{
	$ret = [];
	foreach ($obj as $key => $value) {
		if (gettype($value) == "array" || gettype($value) == "object") {
			$ret[$key] = objtoarr($value);
		} else {
			$ret[$key] = $value;
		}
	}
	return $ret;
}
function createLinkstrings($para)
{
	$arg = "";
	foreach ($para as $key => $val) {
		$arg .= $key . "=" . $val . "&";
	}
	$arg = trim($arg, "&");
	if (function_exists("get_magic_quotes_gpc") && get_magic_quotes_gpc()) {
		$arg = stripslashes($arg);
	}
	return $arg;
}
function checkLoginToken()
{
	$admin_id = session("ADMIN_ID");
	$exist = \think\Db::name("user_token")->where("user_id", $admin_id)->where("device_type", "web")->find();
	if (!empty($exist) && isset($exist["token"])) {
		if ($exist["token"] != session("token")) {
			session("AUTH_IDS_" . session("ADMIN_ID"), null);
			session("AUTH_ROLE_IDS_" . session("ADMIN_ID"), null);
			session("ADMIN_ID", null);
			session("name", null);
			session("token", null);
			return 1;
		}
	}
	return 0;
}
/**
 * 转换IPv6地址为bin
 * @param string $ip 返回类型 0 数字 1 返回False
 * @return mixed
 */
function ip2bin($ip)
{
	$ipbin = "";
	if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
		return base_convert(ip2long($ip), 10, 2);
	}
	if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
		return false;
	}
	if (($ip_n = inet_pton($ip)) === false) {
		return false;
	}
	for ($bits = 15; $bits >= 0; $bits--) {
		$bin = sprintf("%08b", ord($ip_n[$bits]));
		$ipbin = $bin . $ipbin;
	}
	return $ipbin;
}
/**
 * 转换bin地址为IPv6 或IPv4
 * @param long $bin 返回类型 0 IPv4 IPv6地址
 * @return mixed
 */
function bin2ip($bin)
{
	$ipv6 = "";
	if (strlen($bin) <= 32) {
		return long2ip(base_convert($bin, 2, 10));
	}
	if (strlen($bin) != 128) {
		return false;
	}
	$pad = 128 - strlen($bin);
	for ($i = 1; $i <= $pad; $i++) {
		$bin = "0" . $bin;
	}
	for ($bits = 0; $bits <= 7; $bits++) {
		$bin_part = substr($bin, $bits * 16, 16);
		$ipv6 .= dechex(bindec($bin_part)) . ":";
	}
	return inet_ntop(inet_pton(substr($ipv6, 0, -1)));
}
/**
 * 获取客户端IP地址
 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @return mixed
 */
function get_client_ip6($type = 0)
{
	$type = $type ? 1 : 0;
	static $ip;
	if ($ip !== null) {
		return $ip[$type];
	}
	if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
		$arr = explode(",", $_SERVER["HTTP_X_FORWARDED_FOR"]);
		$pos = array_search("unknown", $arr);
		if (false !== $pos) {
			unset($arr[$pos]);
		}
		$ip = trim($arr[0]);
	} elseif (isset($_SERVER["HTTP_CLIENT_IP"])) {
		$ip = $_SERVER["HTTP_CLIENT_IP"];
	} elseif (isset($_SERVER["REMOTE_ADDR"])) {
		$ip = $_SERVER["REMOTE_ADDR"];
	}
	$long = sprintf("%u", ip2bin($ip));
	$ip = $long ? [$ip, $long] : ["0.0.0.0", 0];
	return $ip[$type];
}
function get_remote_port()
{
	$port = \think\facade\Request::remotePort();
	if (empty($port)) {
		$port = "0";
	}
	return $port;
}
function updateUgp($uid)
{
	$ngu = \think\Db::name("nav_group_user")->where("uid", $uid ?? 0)->select()->toArray();
	if ($uid != 0 && $uid != "" && !empty($uid)) {
		if (empty($ngu[0])) {
			$ng = \think\Db::name("nav_group")->select()->toArray();
			foreach ($ng as $key => $value) {
				$data = ["groupid" => $value["id"], "uid" => $uid ?? 0, "is_show" => 1];
				\think\Db::name("nav_group_user")->insert($data);
			}
		} else {
			$data = \think\Db::name("nav_group")->alias("ng")->join("nav_group_user agu", "ng.id=agu.groupid")->where("agu.uid", "=", $uid)->field("ng.*")->select()->toArray();
			$ng = \think\Db::name("nav_group")->select()->toArray();
			foreach ($ng as $key => $value) {
				$flag = true;
				foreach ($data as $k => $v) {
					if ($value["id"] == $v["id"]) {
						$flag = false;
						break;
					}
				}
				if ($flag) {
					$data = ["groupid" => $value["id"], "uid" => $uid ?? 0, "is_show" => 0];
					\think\Db::name("nav_group_user")->insert($data);
				}
			}
		}
	}
}
function getZjmfVersion()
{
	return configuration("update_last_version");
}
function checkHostIsCancel($hid)
{
	$host_cancel = \think\Db::name("cancel_requests")->where("relid", $hid)->where("delete_time", 0)->find();
	if (!empty($host_cancel)) {
		return true;
	} else {
		return false;
	}
}
function autoTicket($uid)
{
	$ticket = \think\Db::name("ticket")->where("uid", $uid)->where("is_auto_reply", 0)->select()->toArray();
	foreach ($ticket as $key => $value) {
		$td = \think\Db::name("ticket_department")->where("id", $value["dptid"])->find();
		if ($td["is_open_auto_reply"] == 1) {
			if ($td["time_type"] == 1) {
				$td["minutes"] = $td["minutes"] * 60;
			}
			if ($value["create_time"] + $td["minutes"] <= time()) {
				$data["tid"] = $value["id"];
				$data["uid"] = intval($uid);
				$data["create_time"] = time();
				$data["content"] = $td["bz"];
				$data["admin_id"] = 1;
				$data["admin"] = "admin";
				$data["attachment"] = "";
				\think\Db::startTrans();
				try {
					$r1 = \think\Db::name("ticket_reply")->insertGetId($data);
					$r2 = \think\Db::name("ticket")->where("id", $data["tid"])->update(["is_auto_reply" => 1, "admin_unread" => 1, "last_reply_time" => time()]);
					if ($r1 && $r2) {
						active_logs(sprintf("回复工单#User ID:%d - Ticket ID:%s - %s", $uid, $value["tid"], $value["title"]), $uid);
						active_logs(sprintf("回复工单#User ID:%d - Ticket ID:%s - %s", $uid, $value["tid"], $value["title"]), $uid, "", 2);
						\think\Db::commit();
					} else {
						\think\Db::rollback();
					}
				} catch (Exception $e) {
					var_dump($e->getMessage());
					\think\Db::rollback();
				}
			}
		}
	}
}
/**
 * 对象 转 数组
 *
 * @param object $obj 对象
 * @return array
 */
function object_to_array($obj)
{
	$obj = (array) $obj;
	foreach ($obj as $k => $v) {
		if (gettype($v) == "resource") {
			return null;
		}
		if (gettype($v) == "object" || gettype($v) == "array") {
			$obj[$k] = (array) object_to_array($v);
		}
	}
	return $obj;
}
/**
 * 数组 转 对象
 *
 * @param array $arr 数组
 * @return object
 */
function array_to_object($arr)
{
	if (gettype($arr) != "array") {
		return null;
	}
	foreach ($arr as $k => $v) {
		if (gettype($v) == "array" || getType($v) == "object") {
			$arr[$k] = (object) array_to_object($v);
		}
	}
	return (object) $arr;
}
function getClientCeritfi($uid)
{
	$certifiPerson = \think\Db::name("certifi_person")->where("auth_user_id", $uid)->where("status", 1)->find();
	$status = lang("USER_UNCERTIFI");
	if ($certifiPerson) {
		$status = lang("USER_CERTIFI_PERSON");
	}
	$certifiCompany = \think\Db::name("certifi_company")->where("auth_user_id", $uid)->where("status", 1)->find();
	if ($certifiCompany) {
		$status = lang("USER_CERTIFI_COMPANY");
	}
	return $status;
}
function developer_app_log($pid, $desc, $reason, $type = 0, $amdin = 0)
{
	\think\Db::name("developer_app_log")->insert(["pid" => $pid ?? 0, "desc" => $desc ?? "", "reason" => $reason ?? "", "type" => $type ?? 0, "create_time" => time(), "admin" => $amdin]);
	return true;
}
function quantityStagePrice($cid, $currency, $quantity, $billingcycle, $last_price = 0, $last_setup = 0)
{
	if ($quantity == 0) {
		return [0, 0];
	}
	if (is_array($currency)) {
		$currency = $currency["id"];
	}
	$subs = \think\Db::name("product_config_options_sub")->alias("a")->field("a.qty_minimum,a.qty_maximum")->leftJoin("pricing b", "a.id = b.relid")->where("b.type", "configoptions")->where("b.currency", $currency)->where("a.config_id", $cid)->select()->toArray();
	array_multisort($subs, array_column($subs, "qty_maximum"));
	foreach ($subs as $k => $v) {
		if ($quantity <= $v["qty_maximum"] && $v["qty_minimum"] <= $quantity) {
			$min = $k;
			break;
		}
	}
	$pricing = \think\Db::name("product_config_options_sub")->alias("a")->leftJoin("pricing b", "a.id = b.relid")->where("b.type", "configoptions")->where("b.currency", $currency)->where("a.config_id", $cid)->where("a.qty_minimum", "<=", $quantity)->where("a.qty_maximum", ">=", $quantity)->find();
	$price_type = config("price_type")[$billingcycle];
	if ($pricing["qty_minimum"] != 0) {
		$quantity = $quantity - $pricing["qty_minimum"] + 1;
	}
	if (!empty($pricing)) {
		$price = $pricing[$price_type[0]] * $quantity;
		$setup = $pricing[$price_type[1]];
	} else {
		$price = $last_price * $quantity;
		$setup = $last_setup;
	}
	if ($quantity > 0) {
		if ($pricing["qty_minimum"] > 1) {
			$sum = quantityStagePrice($cid, $currency, intval($subs[$min - 1]["qty_maximum"]), $billingcycle, floatval($pricing[$price_type[0]]), floatval($pricing[$price_type[1]]));
		} else {
			$sum = quantityStagePrice($cid, $currency, intval($subs[$min - 1]["qty_maximum"]), $billingcycle);
		}
		$price = $sum[0] + $price;
	}
	return [$price, floatval($setup)];
}
function second_array_unique_bykey($arr, $key)
{
	$tmp_arr = [];
	foreach ($arr as $k => $v) {
		if (in_array($v[$key], $tmp_arr)) {
			unset($arr[$k]);
		} else {
			$tmp_arr[$k] = $v[$key];
		}
	}
	sort($arr);
	return $arr;
}
function deep_in_array($value, $array)
{
	foreach ($array as $item) {
		if (!is_array($item)) {
			if ($item == $value) {
				return true;
			} else {
				continue;
			}
		}
		if (in_array($value, $item)) {
			return true;
		} else {
			if (deep_in_array($value, $item)) {
				return true;
			}
		}
	}
	return false;
}
/**
 * 修改扩展配置文件
 * @param array  $arr  需要更新或添加的配置
 * @param string $user 修改人
 * @return bool
 */
function editConfig($filename, $pat = [], $rep = [], $user = "admin")
{
	if (is_array($pat) && is_array($rep)) {
		for ($i = 0; $i < count($pat); $i++) {
			$pats[$i] = "/'" . $pat[$i] . "'(.*?),/";
			$reps[$i] = "'" . $pat[$i] . "'" . "=>" . "'" . $rep[$i] . "',";
		}
		var_dump($pats);
		var_dump($reps);
		exit;
		$fileurl = Env::get("APP_PATH") . "config/" . $filename;
		$string = file_get_contents($fileurl);
		$string = preg_replace($pats, $reps, $string);
		file_put_contents($fileurl, $string);
		return true;
	} else {
		return false;
	}
	exit;
	if (is_array($arr)) {
		$filepath = Env::get("APP_PATH") . "config/" . $filename;
		if (!file_exists($filepath)) {
			if (!fopen($filepath, "w")) {
				return "PermissionError1";
			}
		}
		if (!is_writable($filepath)) {
			return "PermissionError2";
		}
		$conf = (include $filepath);
		foreach ($arr as $key => $value) {
			$conf[$key] = $value;
		}
		$time = date("Y/m/d H:i:s");
		$str = "<?php\r\n/**\r\n * 由" . $user . "修改.\r\n * {$time}\r\n */\r\nreturn [\r\n";
		foreach ($conf as $key => $value) {
			if (is_array($value)) {
				$str .= "\t'{$key}'=>[\r\n";
				foreach ($value as $ikey => $r) {
					if (is_numeric($ikey)) {
						$str .= "\t\t'{$r}',";
						$str .= "\r\n";
					} else {
						$str .= "\t\t'{$ikey}' => '{$r}',";
						$str .= "\r\n";
					}
				}
				$str = rtrim($str, ",");
				$str .= "\t],\r\n";
			} else {
				if (is_bool($value)) {
					$str .= "\t'{$key}' => (boole){$value},";
					var_dump($value);
				} else {
					$str .= "\t'{$key}' => '{$value}',";
				}
				$str .= "\r\n";
			}
		}
		exit;
		$str .= "];";
		$result = file_put_contents($filepath, $str);
		if ($result) {
			return "success";
		} else {
			return $result;
		}
	} else {
		return "error";
	}
}
function deleteLog($path, $delDir = false)
{
	if (is_array($path)) {
		foreach ($path as $subPath) {
			delDirAndFile($subPath, $delDir);
		}
	}
	if (is_dir($path)) {
		$handle = opendir($path);
		if ($handle) {
			while (false !== ($item = readdir($handle))) {
				if ($item != "." && $item != "..") {
					is_dir("{$path}/{$item}") ? deleteLog("{$path}/{$item}", $delDir) : unlink("{$path}/{$item}");
				}
			}
			closedir($handle);
			if ($delDir) {
				return rmdir($path);
			}
		}
	} else {
		if (file_exists($path)) {
			return unlink($path);
		} else {
			return false;
		}
	}
}
function sendmsglimit($phone)
{
	\think\Db::name("sendmsglimit")->where("ip", get_client_ip6())->where("time", "lt", strtotime(date("Y-m-d", time())))->delete();
	$sendmsgtimes = 30;
	$sendmsgphone = 5;
	$number = \think\Db::name("sendmsglimit")->where("ip", get_client_ip6())->group("phone")->where("time", "lt", strtotime(date("Y-m-d", strtotime("+1 day"))))->count();
	if ($sendmsgphone <= $number) {
		return ["status" => 400, "msg" => "同一个ip一天不能发送超过" . $sendmsgphone . "个手机"];
	}
	$number = \think\Db::name("sendmsglimit")->where("ip", get_client_ip6())->where("time", "lt", strtotime(date("Y-m-d", strtotime("+1 day"))))->count();
	if ($sendmsgtimes <= $number) {
		return ["status" => 400, "msg" => "同一个ip一天不能发送超过" . $sendmsgtimes . "次"];
	}
	return ["status" => 200, "msg" => "success"];
}
function getRate($method)
{
	$rawFeed = getcurlrequest(config("app.getRateUrl." . $method));
	if ($method == "xml") {
		$rawFeed = explode("\n", $rawFeed);
		$exchangeRates = [];
		$exchangeRates["EUR"] = 1;
		foreach ($rawFeed as $line) {
			$line = trim($line);
			$matchString = "currency='";
			$pos1 = strpos($line, $matchString);
			if ($pos1) {
				$currencySymbol = substr($line, $pos1 + strlen($matchString), 3);
				$matchString = "rate='";
				$pos2 = strpos($line, $matchString);
				$rateString = substr($line, $pos2 + strlen($matchString));
				$pos3 = strpos($rateString, "'");
				$rate = substr($rateString, 0, $pos3);
				$exchangeRates[$currencySymbol] = $rate;
			}
		}
		return $exchangeRates;
	}
	if ($method == "json") {
		$exchangeRates = (array) json_decode($rawFeed)->rates;
		return $exchangeRates;
	}
	exit;
}
function checkDeveloperApp($pid)
{
	$developer_app = \think\Db::name("products")->field("type")->where("id", $pid)->where("hidden", 0)->where("p_uid", ">", 0)->find();
	return $developer_app ?? [];
}
function deleteUnusedFile()
{
	updateconfiguration("web_authcode", config("database.authcode"));
	if (strpos($_SERVER["HTTP_HOST"], "idcsmart.com") !== false) {
		return false;
	}
	if (file_exists(CMF_ROOT . "public/themes/clientarea/default/apps.tpl")) {
		@unlink(CMF_ROOT . "public/themes/clientarea/default/apps.tpl");
	}
	if (file_exists(CMF_ROOT . "public/themes/clientarea/default/appincome.tpl")) {
		@unlink(CMF_ROOT . "public/themes/clientarea/default/appincome.tpl");
	}
	if (file_exists(CMF_ROOT . "public/themes/clientarea/default/applog.tpl")) {
		@unlink(CMF_ROOT . "public/themes/clientarea/default/applog.tpl");
	}
	if (file_exists(CMF_ROOT . "public/themes/clientarea/default/apptransaction.tpl")) {
		@unlink(CMF_ROOT . "public/themes/clientarea/default/apptransaction.tpl");
	}
	if (file_exists(CMF_ROOT . "public/themes/clientarea/default/apps/appsinner.tpl")) {
		@unlink(CMF_ROOT . "public/themes/clientarea/default/apps/appsinner.tpl");
	}
	if (file_exists(CMF_ROOT . "modules/addons/client_care")) {
		deletedir(CMF_ROOT . "modules/addons/client_care");
	}
	if (file_exists(CMF_ROOT . "modules/servers/dcimapp")) {
		deletedir(CMF_ROOT . "modules/servers/dcimapp");
	}
	if (file_exists(CMF_ROOT . "modules/servers/dcimauth")) {
		deletedir(CMF_ROOT . "modules/servers/dcimauth");
	}
	if (file_exists(CMF_ROOT . "modules/servers/zjmfapp")) {
		deletedir(CMF_ROOT . "modules/servers/zjmfapp");
	}
	if (file_exists(CMF_ROOT . "modules/servers/zjmfauth")) {
		deletedir(CMF_ROOT . "modules/servers/zjmfauth");
	}
	if (file_exists(CMF_ROOT . "app/admin/controller/DcimAuthController.php")) {
		@unlink(CMF_ROOT . "app/admin/controller/DcimAuthController.php");
	}
	if (file_exists(CMF_ROOT . "public/tools.php")) {
		@unlink(CMF_ROOT . "public/tools.php");
	}
	if (file_exists(CMF_ROOT . "public/move.php")) {
		@unlink(CMF_ROOT . "public/move.php");
	}
	if (file_exists(CMF_ROOT . "public/config.global.php")) {
		@unlink(CMF_ROOT . "public/config.global.php");
	}
	return true;
}
function isSecondVerify($action, $admin = false, $uid = "")
{
	if (empty($action)) {
		return false;
	}
	$action = cmf_parse_name($action, 0);
	if ($admin) {
		if (configuration("second_verify_admin")) {
			$allow_action = explode(",", configuration("second_verify_action_admin"));
			if (in_array($action, $allow_action)) {
				return true;
			}
		}
	} else {
		$uid = $uid ? intval($uid) : request()->uid;
		$second_verify = \think\Db::name("clients")->where("id", $uid)->value("second_verify");
		if (configuration("second_verify_home") && $second_verify) {
			$allow_action = explode(",", configuration("second_verify_action_home"));
			if (in_array($action, $allow_action)) {
				return true;
			}
		}
	}
	return false;
}
/**
* 字符串加密解密
* @param string $string 原文或者密文
* @param string $operation 操作(ENCODE | DECODE), 默认为 DECODE
* @param string $key 密钥
* @param int $expiry 密文有效期, 加密时候有效， 单位 秒，0 为永久有效
* @return string 处理后的 原文或者 经过 base64_encode 处理后的密文
*
* @example
*
*  $a = authcode('abc', 'ENCODE', 'key');
*  $b = authcode($a, 'DECODE', 'key');  // $b(abc)
*
*  $a = authcode('abc', 'ENCODE', 'key', 3600);
*  $b = authcode('abc', 'DECODE', 'key'); // 在一个小时内，$b(abc)，否则 $b 为空
*/
function _strcode($string = "", $operation = "DECODE", $key = "", $expiry = 0)
{
	$ckey_length = 4;
	$key = md5($key ? $key : "default_key");
	$keya = md5(substr($key, 0, 16));
	$keyb = md5(substr($key, 16, 16));
	$keyc = $ckey_length ? $operation == "DECODE" ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length) : "";
	$cryptkey = $keya . md5($keya . $keyc);
	$key_length = strlen($cryptkey);
	$string = $operation == "DECODE" ? base64_decode(substr($string, $ckey_length)) : sprintf("%010d", $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
	$string_length = strlen($string);
	$result = "";
	$box = range(0, 255);
	$rndkey = [];
	for ($i = 0; $i <= 255; $i++) {
		$rndkey[$i] = ord($cryptkey[$i % $key_length]);
	}
	$j = $i = 0;
	while ($i < 256) {
		$j = ($j + $box[$i] + $rndkey[$i]) % 256;
		$tmp = $box[$i];
		$box[$i] = $box[$j];
		$box[$j] = $tmp;
		$i++;
	}
	$a = $j = $i = 0;
	while ($i < $string_length) {
		$a = ($a + 1) % 256;
		$j = ($j + $box[$a]) % 256;
		$tmp = $box[$a];
		$box[$a] = $box[$j];
		$box[$j] = $tmp;
		$result .= chr(ord($string[$i]) ^ $box[($box[$a] + $box[$j]) % 256]);
		$i++;
	}
	if ($operation == "DECODE") {
		if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
			return substr($result, 26);
		} else {
			return "";
		}
	} else {
		return $keyc . str_replace("=", "", base64_encode($result));
	}
}
function secondVerifyResultHome($action = "")
{
	$action = $action ? $action : request()->action();
	$client = \think\Db::name("clients")->field("id,phonenumber,email")->where("id", request()->uid)->find();
	$mobile = $client["phonenumber"];
	$email = $client["email"];
	if (issecondverify($action, false, $client["id"])) {
		$action = cmf_parse_name($action, 0);
		$code = request()->code;
		if (empty($code)) {
			return ["status" => 400, "msg" => "请输入验证码"];
		}
		if ($code != cache($action . "_" . $mobile) && $code != cache($action . "_" . $email)) {
			return ["status" => 400, "msg" => "验证码错误"];
		}
	}
	cache($action . "_" . $mobile, null);
	cache($action . "_" . $email, null);
	return ["status" => 200];
}
function secondVerifyResultAdmin($action = "")
{
	$action = $action ? $action : request()->action();
	$result = \think\Db::name("user")->where("id", cmf_get_current_admin_id())->find();
	$email = $result["user_email"];
	if (issecondverify($action, true)) {
		$code = request()->code;
		if (empty($code)) {
			return ["status" => 400, "msg" => "验证码不能为空"];
		}
		if (cache($action . "_admin_" . $email) != $code) {
			return ["status" => 400, "msg" => "验证码错误"];
		}
	}
	cache($action . "_admin_" . $email, null);
	return ["status" => 200];
}
function cancelRequest($hid)
{
	$request = \think\Db::name("cancel_requests")->where("relid", $hid)->where("delete_time", 0)->find();
	if (empty($request)) {
		return false;
	} else {
		return true;
	}
}
function getTimeTrans($Ttime)
{
	$str_total = var_export($Ttime, true);
	if (substr_count($str_total, "E")) {
		$float_total = floatval(substr($str_total, 5));
		$Ttime = $float_total / 100000;
	}
	return $Ttime;
}
function deleteHostDuplicate()
{
	$hids = \think\Db::name("host_config_options")->distinct(true)->column("relid");
	$cids = \think\Db::name("host_config_options")->distinct(true)->column("configid");
	foreach ($hids as $hid) {
		foreach ($cids as $cid) {
			$count = \think\Db::name("host_config_options")->where("relid", $hid)->where("configid", $cid)->count();
			if ($count > 1) {
				$config = \think\Db::name("host_config_options")->where("relid", $hid)->where("configid", $cid)->find();
				\think\Db::name("host_config_options")->where("relid", $hid)->where("configid", $cid)->delete();
				\think\Db::name("host_config_options")->insert($config);
			}
		}
	}
}
function dealarr($data = [])
{
	$arr = [];
	foreach ($data as $k => $v) {
		$arr[] = $v;
	}
	return $arr;
}
function getReceiveAdmin()
{
	$arr = \think\Db::name("user")->field("id")->where("is_receive", 1)->where("id", ">", 1)->select()->toArray();
	return $arr;
}
function asyncCurlMulti($data, $timeout = 5)
{
	if (!$data) {
		return false;
	}
	$_data_async_create = [];
	$_data_async = [];
	$_data_async_sms = [];
	foreach ($data as $k => $v) {
		if ($v["url"] == "async_create") {
			$_data_async_create[$k] = $v;
			unset($data[$k]);
		}
		if ($v["url"] == "async") {
			$_data_async[$k] = $v;
			unset($data[$k]);
		}
		if ($v["url"] == "async_sms") {
			$client = \think\Db::name("clients")->where("id", $v["uid"])->find();
			if ($client["send_close"] && in_array($v["name"], [1, 2, 3, 4, 5, 6, 7, 13, 14, 15, 16, 17, 18, 19, 24, 25, 26, 27, 28, 30, 31, 32, 34, 35, 36, 37, 39])) {
			} else {
				$_data_async_sms[$k] = $v;
			}
			unset($data[$k]);
		}
	}
	if (count($_data_async_create) > 0) {
		$async_create["url"] = "async_create";
		$async_create["data"] = $_data_async_create;
		$data[] = $async_create;
	}
	if (count($_data_async) > 0) {
		$async_create["url"] = "async";
		$async_create["data"] = $_data_async;
		$data[] = $async_create;
	}
	if (count($_data_async_sms) > 0) {
		$async_create["url"] = "async_sms";
		$async_create["data"] = $_data_async_sms;
		$data[] = $async_create;
	}
	ksort($data);
	$send_data = $data;
	$data["sign"] = sha1(implode($send_data));
	$token = md5(microtime(true) . rand(10000, 99999));
	\think\facade\Cache::set(md5(json_encode($send_data)), $token, 3600);
	$data["token"] = $token;
	$admin_application = config("database.admin_application") ?? "admin";
	$url = configuration("domain") . "/{$admin_application}/async_curl_multi";
	$curlTime = configuration("curlTime");
	if (!empty($curlTime)) {
		$timeout = $curlTime;
	} else {
		$curlTime = curlTime($url);
		if ($curlTime > 5) {
			updateconfiguration("curlTime", 15);
		}
		$timeout = $curlTime + 1;
	}
	$ssl = substr($url, 0, 8) == "https://" ? true : false;
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	if ($ssl) {
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1);
	}
	curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]);
	curl_setopt($curl, CURLOPT_HEADER, 0);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
	$content = curl_exec($curl);
	$curl_errno = curl_errno($curl);
	curl_close($curl);
}
function curlTime($url)
{
	$data = [];
	$ssl = substr($url, 0, 8) == "https://" ? true : false;
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	if ($ssl) {
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1);
	}
	curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]);
	curl_setopt($curl, CURLOPT_HEADER, 0);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
	$content = curl_exec($curl);
	$info = curl_getinfo($curl);
	curl_close($curl);
	return ceil($info["total_time"]);
}
function unifyConfigOption()
{
	$gids = \think\Db::name("product_config_groups")->column("id");
	foreach ($gids as $gid) {
		$p_count = \think\Db::name("product_config_links")->where("gid", $gid)->count();
		if ($p_count == 0 || $p_count > 1) {
			\think\Db::name("product_config_groups")->where("id", $gid)->update(["global" => 1]);
		}
	}
	return true;
}
function updateProductVersion()
{
	\think\Db::name("products")->where("location_version", "<>", 0)->setInc("location_version");
	\think\Db::name("product_groups")->where("gid", 0)->update(["gid" => 1]);
	return true;
}
function updateMessageLink()
{
	$data = [["type" => 8, "title" => "发送验证码", "content" => "验证码{code}，勿泄漏于他人"], ["type" => "", "title" => "生成账单", "content" => "您好{name},您有一笔账单产生:账单号#{invoiceid},金额{total},请及时付款,以免账单失效。"], ["type" => 2, "title" => "账单支付", "content" => "您好{name},您有一笔账单产生:账单号#{invoiceid},金额{total},请及时付款,以免账单失效。"], ["type" => 3, "title" => "账单支付逾期", "content" => "您有一笔账单已过期,账单号{invoiceid},金额{total},请及时关注."], ["type" => 4, "title" => "提交工单", "content" => "您好,我们已经收到您提交的工单：{subject}.团队将火速处理您的问题.请耐心等待."], ["type" => 5, "title" => "工单回复", "content" => "您提交的工单{subject}有新的回复,请注意查收."], ["type" => 6, "title" => "产品暂停", "content" => "您好,您购买的产品{description}由于{description}的缘故,现已被暂停所有功能.如需恢复使用,请尽快处理."], ["type" => 7, "title" => "未支付账单", "content" => "您好,您已成功支付账单号#{invoiceid},账单金额{total}没有支付"], ["type" => 9, "title" => "登录短信提醒", "content" => "您好,您的账号{account}于{time}时间在以下{address}地址登录.如您未曾尝试登录,请立即更改登录密码,以防账号被盗."], ["type" => 10, "title" => "订单退款", "content" => "订单{order_id},金额{order_total_fee}已退款"], ["type" => 13, "title" => "订单支付提醒", "content" => "您的订单(编号{order_id})已经完成付款,付款金额为：{order_total_fee}"], ["type" => 14, "title" => "订单未付款提醒", "content" => "建议模板: 您购买的产品{product_name}(主机名{hostname})将于{product_end_time}到期.为了保证届时可以正常使用,请在产品到期之前先行续费"], ["type" => 15, "title" => "自动生成续费账单提醒", "content" => "您购买的产品{product_name}(主机名{hostname})将于{product_end_time}到期.为了保证届时可以正常使用,请在产品到期之前先行续费"], ["type" => 16, "title" => "第三次逾期提醒", "content" => "您在{product_first_time}订购的{product_name}产品(主机名：{hostname})支付尚未完成.暂时无法开通.为了避免订单过期,请您及时付款。"], ["type" => 17, "title" => "第二次逾期提醒", "content" => "您在{product_first_time}订购的{product_name}产品(主机名：{hostname})支付尚未完成.暂时无法开通.为了避免订单过期,请您及时付款。"], ["type" => 18, "title" => "第一次逾期提醒", "content" => "您在{product_first_time}订购的{product_name}产品(主机名：{hostname})支付尚未完成.暂时无法开通.为了避免订单过期,请您及时付款。"], ["type" => 19, "title" => "下单提醒", "content" => "您已成功下单{product_name}产品,为期{product_binlly_cycle}.请及时付款,以免订单失效.以下为账单信息产品名称:{product_name}产品单价:{product_price}付款周期:{product_binlly_cycle}订单创建时间:{order_create_time}"], ["type" => 24, "title" => "产品开通提醒", "content" => "您购买的产品{product_name}现已开通,感谢使用!ip地址:{product_mainip},操作系统用户名:{product_user},操作系统密码:{product_passwd},操作系统:{product_dcimbms_os},其他附加ip地址:{product_addonip},购买时间:{product_first_time},到期时间:{product_end_time},付款周期:{product_binlly_cycle}"], ["type" => 26, "title" => "未续期产品删除提醒", "content" => "您购买的产品{product_name}({hostname})由于未能在指定时间内续费,已于{product_terminate_time}自动删除.对因此而造成的不便我们表示歉意,希望您可以选择我们的其它产品."], ["type" => 27, "title" => "续费成功提醒", "content" => "您购买的产品({product_name})现已续费成功,服务将持续至{product_end_time}.感谢您对我们的信赖!"], ["type" => "", "title" => "工单关闭提醒", "content" => "您在{ticket_createtime}提交的工单{ticketnumber_tickettitle}由于长时间未获回复,已于{ticket_reply_time}自动关闭."], ["type" => 31, "title" => "工单开通提醒", "content" => "我们已经收到您在{ticket_createtime}(时间)提交的工单:({ticketnumber_tickettitle}).团队将火速处理您的问题.请耐心等待."], ["type" => 32, "title" => "绑定提醒", "content" => "您的账号{username}与此{epw_type}:({epw_account})已成功进行绑定.如有疑问,请联系客服."], ["type" => 33, "title" => "注册成功", "content" => "您已成功注册{system_companyname}账号,感谢您的使用.请完善账号个人信息并妥善保管.切勿向他人透漏登录密码!"]];
	$id = \think\Db::name("message_template")->field("id")->where("sms_operator", "smsbao")->find();
	if (empty($id)) {
		foreach ($data as $k => $v) {
			$dd = ["title" => $v["title"], "content" => $v["content"], "sms_operator" => "smsbao", "remark" => $v["title"], "status" => 2, "template_id" => "", "range_type" => 0];
			$idd = \think\Db::name("message_template")->insertGetId($dd);
			if (!empty($v["type"])) {
				$dds = ["sms_temp_id" => $idd, "type" => $v["type"], "sms_operator" => "smsbao", "is_use" => 1, "range_type" => 0];
				\think\Db::name("message_template_link")->insertGetId($dds);
			}
		}
	}
	return true;
}
function combine($array, $field, $child)
{
	$tmpArray = [];
	foreach ($array as $row) {
		$fl = array_search($row[$field], $tmpArray);
		if ($fl) {
			$tmpArray[$fl][$child] = array_merge($tmpArray[$fl][$child], $row[$child]);
		} else {
			$tmpArray[] = $row;
		}
	}
	return $tmpArray;
}
function addFileToZip($path, $zip)
{
	$handler = opendir($path);
	while (($filename = readdir($handler)) !== false) {
		if ($filename != "." && $filename != "..") {
			if (is_dir($path . "/" . $filename)) {
				\think\addFileToZip($path . "/" . $filename, $zip);
			} else {
				$zip->addFile($path . "/" . $filename);
			}
		}
	}
	@closedir($path);
}
function getNewOrderArr($arr = [], $arr2 = [])
{
	$i = 1;
	foreach ($arr as $key => $value) {
		while ($i <= count($arr) + count($arr2)) {
			if (!array_key_exists($i, $arr2)) {
				break;
			}
			$i++;
		}
		$arr2[$i] = $value;
	}
	ksort($arr2);
	return $arr2;
}
function updateBates()
{
	$updatebates = configuration("updatebates");
	$updatebates1 = configuration("updatepromobates");
	if (empty($updatebates) || $updatebates == 0) {
		$user_product_bates = \think\Db::name("user_product_bates")->field("id,bates")->where("type", 1)->select()->toArray();
		foreach ($user_product_bates as $key => $value) {
			\think\Db::name("user_product_bates")->where("id", $value["id"])->update(["bates" => 100 - $value["bates"]]);
		}
		$promo_code = \think\Db::name("promo_code")->field("id,value")->where("type", "percent")->select()->toArray();
		foreach ($promo_code as $key => $value) {
			\think\Db::name("promo_code")->where("id", $value["id"])->update(["value" => 100 - $value["value"]]);
		}
		updateconfiguration("updatebates", 1);
	} else {
		if (empty($updatebates1) || $updatebates1 == 0) {
			$promo_code = \think\Db::name("promo_code")->field("id,value")->where("type", "percent")->select()->toArray();
			foreach ($promo_code as $key => $value) {
				\think\Db::name("promo_code")->where("id", $value["id"])->update(["value" => 100 - $value["value"]]);
			}
			updateconfiguration("updatepromobates", 1);
		}
	}
}
function updateAuth()
{
	$file = DATABASE_DOWN_PATH . "auth_rule.sql";
	$auth_rule_m = DATABASE_DOWN_PATH . "auth_rule_m.sql";
	if (file_exists($file)) {
		$sql = file_get_contents($file);
		$sqls = explode(";", $sql);
		$fun = function ($value) {
			if (empty($value)) {
				return false;
			} else {
				return true;
			}
		};
		$sqls = array_filter($sqls, $fun);
		foreach ($sqls as $k => $v) {
			if (!empty($v)) {
				$re = \think\Db::execute($v);
			}
		}
		unlink($file);
		if (file_exists($auth_rule_m)) {
			$sql_m = file_get_contents($auth_rule_m);
			$sqls_m = explode(";", $sql_m);
			$fun_m = function ($value_m) {
				if (empty($value_m)) {
					return false;
				} else {
					return true;
				}
			};
			$sqls_m = array_filter($sqls_m, $fun_m);
			foreach ($sqls_m as $k_m => $v_m) {
				if (!empty($v_m)) {
					\think\Db::execute($v_m);
				}
			}
		}
		return true;
	} else {
		return false;
	}
}
function checkDefaultProductGroup()
{
	$tmp = \think\Db::name("product_first_groups")->where("id", 1)->find();
	if (empty($tmp)) {
		\think\Db::name("product_first_groups")->insert(["id" => 1, "name" => "默认分组", "hidden" => 0, "order" => 0, "create_time" => time(), "update_time" => 0]);
	}
	return true;
}
function advancedConfigOptionFilter($config_id, $configoptions = [])
{
	$configoptions_filter = [];
	if (empty($configoptions)) {
		return $configoptions_filter;
	}
	$need_option_type = \think\Db::name("product_config_options")->where("id", $config_id)->value("option_type");
	$need_sub_id = \think\Db::name("product_config_options_sub")->where("config_id", $config_id)->order("sort_order", "asc")->order("id", "asc")->column("id");
	$need_sub_id_filter = [];
	foreach ($configoptions as $k => $v) {
		$option_type = \think\Db::name("product_config_options")->where("id", $k)->value("option_type");
		$conditions = \think\Db::name("product_config_options_links")->where("config_id", $k)->where("type", "condition")->where("relation_id", 0)->withAttr("sub_id", function ($value) {
			return json_decode($value, true);
		})->select()->toArray();
		foreach ($conditions as $kk => $vv) {
			$condition_relation = $vv["relation"];
			$sub_id = $vv["sub_id"];
			$result = \think\Db::name("product_config_options_links")->where("relation_id", $vv["id"])->where("type", "result")->withAttr("sub_id", function ($value) {
				return json_decode($value, true);
			})->select()->toArray();
			if (judgequantity($option_type)) {
				if (!empty($sub_id)) {
					if ($condition_relation == "seq") {
						foreach ($sub_id as $r => $s) {
							if ($s["qty_minimum"] <= $v && $v <= $s["qty_maximum"]) {
								foreach ($result as $rr => $ss) {
									$result_relation3 = $ss["relation"];
									$result_sub_id3 = $ss["sub_id"];
									if ($ss["config_id"] == $config_id) {
										if ($result_relation3 == "seq") {
											foreach ($result_sub_id3 as $rrr => $sss) {
												foreach ($need_sub_id as $r4 => $s4) {
													if ($rrr == $s4) {
														if (judgequantity($need_option_type)) {
															$need_sub_id_filter[] = $sss["qty_minimum"] > 0 ? $sss["qty_minimum"] : 0;
														} else {
															$need_sub_id_filter[] = $s4;
														}
													}
												}
											}
										} else {
											foreach ($result_sub_id3 as $r5 => $s5) {
												foreach ($need_sub_id as $r6 => $s6) {
													if ($r5 == $s6) {
														unset($need_sub_id[$r6]);
													}
												}
											}
										}
									}
								}
							}
						}
					} else {
						foreach ($sub_id as $r7 => $s7) {
							if ($s7["qty_minimum"] <= $v && $v <= $s7["qty_maximum"]) {
								foreach ($result as $r8 => $s8) {
									$result_relation4 = $s8["relation"];
									$result_sub_id4 = $s8["sub_id"];
									if ($s8["config_id"] == $config_id) {
										if ($result_relation4 == "seq") {
											foreach ($result_sub_id4 as $r9 => $s9) {
												foreach ($need_sub_id as $r10 => $s10) {
													if ($r9 == $s10) {
														if (judgequantity($need_option_type)) {
															$need_sub_id_filter[] = $s9["qty_minimum"] > 0 ? $s9["qty_minimum"] : 0;
														} else {
															$need_sub_id_filter[] = $s10;
														}
													}
												}
											}
										} else {
											foreach ($result_sub_id4 as $r11 => $s11) {
												foreach ($need_sub_id as $r12 => $s12) {
													if ($r11 == $s12) {
														unset($need_sub_id[$r12]);
													}
												}
											}
										}
									}
								}
							}
						}
					}
				}
			} else {
				if (!empty($sub_id)) {
					if ($condition_relation == "seq") {
						if (in_array($v, array_keys($sub_id))) {
							foreach ($result as $nn => $mm) {
								$result_relation = $mm["relation"];
								$result_sub_id = $mm["sub_id"];
								if ($mm["config_id"] == $config_id) {
									if ($result_relation == "seq") {
										foreach ($result_sub_id as $nnn => $mmm) {
											foreach ($need_sub_id as $kkk => $vvv) {
												if ($nnn == $vvv) {
													$need_sub_id_filter[] = $vvv;
												}
											}
										}
									} else {
										foreach ($result_sub_id as $nnn => $mmm) {
											foreach ($need_sub_id as $kkk => $vvv) {
												if ($nnn == $vvv) {
													unset($need_sub_id[$kkk]);
												}
											}
										}
									}
								}
							}
						}
					} else {
						if (!in_array($v, array_keys($sub_id))) {
							foreach ($result as $j => $h) {
								$result_relation2 = $h["relation"];
								$result_sub_id2 = $h["sub_id"];
								if ($h["config_id"] == $config_id) {
									if ($result_relation2 == "seq") {
										foreach ($result_sub_id2 as $jj => $hh) {
											foreach ($need_sub_id as $jjj => $hhh) {
												if ($jj == $hhh) {
													$need_sub_id_filter[] = $hhh;
												}
											}
										}
									} else {
										foreach ($result_sub_id2 as $jj => $hh) {
											foreach ($need_sub_id as $jjj => $hhh) {
												if ($jj == $hhh) {
													unset($need_sub_id[$jjj]);
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}
	if ($need_sub_id_filter) {
		$need_sub_id = $need_sub_id_filter;
		unset($need_sub_id_filter);
	}
	if (!empty($need_sub_id)) {
		$configoptions_filter[$config_id] = array_shift($need_sub_id);
	} else {
		$configoptions_filter[$config_id] = 0;
	}
	return $configoptions_filter;
}
function dataUnitChange($data = "", $decimal = 1024, $to_unit = "", $now_unit = "B")
{
	$unit = ["B", "KB", "MB", "GB", "TB", "PB"];
	$count = array_search($now_unit, $unit) ?: 0;
	$r = array_search($to_unit, $unit);
	if ($r !== false) {
		$count = $r - $count;
		$data = $data / pow($decimal, $count);
	} else {
		while ($decimal <= $data) {
			$data /= $decimal;
			$count++;
			if ($count >= 5) {
				break;
			}
		}
	}
	return round($data, 2) . $unit[$count];
}
function userLevel($uid)
{
	$res = [];
	$expense = \think\Db::name("accounts")->where("uid", $uid)->sum("amount_in");
	$buy_num = \think\Db::name("host")->where("uid", $uid)->count();
	$login_times = \think\Db::name("activity_log")->where("uid", $uid)->where("type", 1)->count();
	$renew_times = \think\Db::name("invoices")->alias("a")->leftJoin("invoice_items b", "a.id = b.invoice_id")->where("b.type", "renew")->where("b.uid", $uid)->where("a.status", "Paid")->count();
	$rules = \think\Db::name("clients_level_rule")->select()->toArray();
	if (!empty($rules[0])) {
		foreach ($rules as $rule) {
			$expense_rule = json_decode($rule["expense"], true);
			if ($expense_rule["min"] != 0 || $expense_rule["max"] != 0) {
				if ($expense_rule["min"] < $expense && $expense <= $expense_rule["max"]) {
					$rule1 = true;
				} else {
					$rule1 = false;
				}
			} else {
				$rule1 = true;
			}
			$buy_num_rule = json_decode($rule["buy_num"], true);
			if ($buy_num_rule["min"] != 0 || $buy_num_rule["max"] != 0) {
				if ($buy_num_rule["min"] < $buy_num && $buy_num <= $buy_num_rule["max"]) {
					$rule2 = true;
				} else {
					$rule2 = false;
				}
			} else {
				$rule2 = true;
			}
			$login_times_rule = json_decode($rule["login_times"], true);
			if ($login_times_rule["min"] != 0 || $login_times_rule["max"] != 0) {
				if ($login_times_rule["min"] < $login_times && $login_times <= $login_times_rule["max"]) {
					$rule3 = true;
				} else {
					$rule3 = false;
				}
			} else {
				$rule3 = true;
			}
			$renew_times_rule = json_decode($rule["renew_times"], true);
			if ($renew_times_rule["min"] != 0 || $renew_times_rule["max"] != 0) {
				if ($renew_times_rule["min"] < $renew_times && $renew_times <= $renew_times_rule["max"]) {
					$rule4 = true;
				} else {
					$rule4 = false;
				}
			} else {
				$rule4 = true;
			}
			if ($rule1 && $rule2 && $rule3 && $rule4) {
				$last_login_times_rule = json_decode($rule["last_login_times"], true);
				if ($last_login_times_rule["min"] != 0 || $last_login_times_rule["max"] != 0) {
					$login_day = $last_login_times_rule["day"];
					$login_day_before = strtotime("-{$login_day} day", time());
					$last_login_times = \think\Db::name("activity_log")->where("create_time", ">=", $login_day_before)->where("uid", $uid)->where("type", 1)->count();
					if ($last_login_times_rule["min"] < $last_login_times && $last_login_times <= $last_login_times_rule["max"]) {
						$rule5 = true;
					} else {
						$rule5 = false;
					}
				} else {
					$rule5 = true;
				}
				if ($rule5) {
					$last_renew_times_rule = json_decode($rule["last_renew_times"], true);
					if ($last_renew_times_rule["min"] != 0 || $last_renew_times_rule["max"] != 0) {
						$renew_day = $last_renew_times_rule["day"];
						$renew_day_before = strtotime("-{$renew_day} day", time());
						$last_renew_times = \think\Db::name("invoices")->alias("a")->leftJoin("invoice_items b", "a.id = b.invoice_id")->where("b.type", "renew")->where("b.uid", $uid)->where("a.status", "Paid")->where("paid_time", ">=", $renew_day_before)->count();
						if ($last_renew_times_rule["min"] < $last_renew_times && $last_renew_times <= $last_renew_times_rule["max"]) {
							$rule6 = true;
						} else {
							$rule6 = false;
						}
					} else {
						$rule6 = true;
					}
					if ($rule6) {
						$res["id"] = $rule["id"];
						$res["level_name"] = $rule["level_name"];
						break;
					}
				}
			}
		}
	}
	return $res;
}
function getEdition()
{
	$zjmf_authorize = configuration("zjmf_authorize");
	if (empty($zjmf_authorize)) {
		return 0;
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
function buyProductMustBindPhone($uid)
{
	$client = \think\Db::name("clients")->where("id", $uid)->find();
	if (intval(configuration("buy_product_must_bind_phone")) && empty($client["phonenumber"])) {
		return false;
	}
	return true;
}
function ticketDeliver($id)
{
	if (function_exists("resourceTicketDeliver")) {
		resourceTicketDeliver($id);
	}
	$ticket = \think\Db::name("ticket_deliver")->alias("a")->leftJoin("ticket_deliver_products b", "b.tdid = a.id")->leftJoin("ticket_deliver_department c", "c.tdid=b.tdid")->leftJoin("ticket d", "d.dptid=c.dptid")->leftJoin("host e", "e.id=d.host_id")->leftJoin("products f", "f.id=e.productid")->leftJoin("ticket_department_upstream g", "g.dptid=c.dptid AND f.zjmf_api_id=g.api_id")->field("f.zjmf_api_id,e.dcimid,d.title,d.content,a.mask_keywords,d.id,g.upstream_dptid,d.attachment,f.api_type,f.id as productid,d.priority")->where("e.productid=b.pid")->where("e.dcimid", ">", 0)->where("d.id", $id)->find();
	if (empty($ticket)) {
		return false;
	}
	$token = [];
	$api = \think\Db::name("zjmf_finance_api")->field("hostname,username,password,is_resource,ticket_open")->where("id", $ticket["zjmf_api_id"])->find();
	$url = rtrim($api["hostname"], "/");
	if ($api["is_resource"] == 1) {
		if (empty($api["ticket_open"])) {
			return false;
		}
		$login_url = $url . "/resource_login";
		$login_data = ["username" => $api["username"], "password" => aespassworddecode($api["password"]), "type" => "agent"];
	} else {
		$login_url = $url . "/zjmf_api_login";
		$login_data = ["username" => $api["username"], "password" => aespassworddecode($api["password"])];
	}
	$jwt = zjmfApiLogin($ticket["zjmf_api_id"], $login_url, $login_data);
	if ($jwt["status"] == 200) {
		$token = ["url" => $url, "jwt" => $jwt["jwt"]];
	}
	if (empty($token)) {
		return false;
	}
	$false = 0;
	if (!empty($ticket["mask_keywords"])) {
		$ticket["mask_keywords"] = explode("\n", $ticket["mask_keywords"]);
		foreach ($ticket["mask_keywords"] as $keywords) {
			if (!empty($keywords)) {
				if (strpos($ticket["title"], $keywords) !== false || strpos($ticket["content"], $keywords) !== false) {
					$false = 1;
				}
			}
		}
		if ($false == 1) {
			return false;
		}
	}
	$attachment = [];
	if (!empty($ticket["attachment"])) {
		$ticket["attachment"] = explode(",", $ticket["attachment"]);
		foreach ($ticket["attachment"] as $key => $value) {
			$res = curlUpload($token["url"] . "/upload_image", config("ticket_attachments") . $value, substr($value, strrpos($value, "^") + 1));
			if ($res !== false) {
				$res = json_decode($res, true);
				if ($res["status"] == 200) {
					if (!empty($res["savename"])) {
						$attachment[] = $res["savename"];
					}
				}
			}
		}
	}
	$data = ["url" => $token["url"] . "/ticket/create", "data" => ["dptid" => intval($ticket["upstream_dptid"]), "hostid" => intval($ticket["dcimid"]), "title" => $ticket["title"], "content" => $ticket["content"], "attachment" => $attachment, "priority" => $ticket["priority"], "is_api" => 1], "header" => ["Authorization: Bearer " . $token["jwt"]]];
	$res = commonCurl($data["url"], $data["data"], 30, "POST", $data["header"]);
	if ($res["status"] == 200) {
		\think\Db::name("ticket")->where("id", $id)->update(["upstream_tid" => $res["data"]["tid"], "is_deliver" => 1]);
		return true;
	} else {
		return false;
	}
}
function ticketReplyDeliver($id)
{
	if (function_exists("resourceTicketReplyDeliver")) {
		resourceTicketReplyDeliver($id);
	}
	$ticket_reply = \think\Db::name("ticket_reply")->alias("a")->leftJoin("ticket b", "b.id=a.tid")->leftJoin("host c", "c.id=b.host_id")->leftJoin("products d", "d.id=c.productid")->field("d.zjmf_api_id,c.dcimid,a.content,a.id,b.upstream_tid,a.attachment,d.api_type,d.id as productid")->where("b.upstream_tid", "<>", "")->where("a.id", $id)->find();
	if (empty($ticket_reply)) {
		return false;
	}
	$token = [];
	$api = \think\Db::name("zjmf_finance_api")->field("hostname,username,password")->where("id", $ticket_reply["zjmf_api_id"])->find();
	$url = rtrim($api["hostname"], "/");
	$login_url = $url . "/zjmf_api_login";
	$login_data = ["username" => $api["username"], "password" => aespassworddecode($api["password"])];
	$jwt = zjmfApiLogin($ticket_reply["zjmf_api_id"], $login_url, $login_data);
	if ($jwt["status"] == 200) {
		$token = ["url" => $url, "jwt" => $jwt["jwt"]];
	}
	if (empty($token)) {
		return false;
	}
	$attachment = [];
	if (!empty($ticket_reply["attachment"])) {
		$ticket_reply["attachment"] = explode(",", $ticket_reply["attachment"]);
		foreach ($ticket_reply["attachment"] as $key => $value) {
			$res = curlUpload($token["url"] . "/upload_image", config("ticket_attachments") . $value, substr($value, strrpos($value, "^") + 1));
			if ($res !== false) {
				$res = json_decode($res, true);
				if ($res["status"] == 200) {
					if (!empty($res["savename"])) {
						$attachment[] = $res["savename"];
					}
				}
			}
		}
	}
	$data = ["url" => $token["url"] . "/ticket/reply", "data" => ["tid" => $ticket_reply["upstream_tid"], "content" => $ticket_reply["content"], "attachment" => $attachment, "is_api" => 1], "header" => ["Authorization: Bearer " . $token["jwt"]]];
	$res = commonCurl($data["url"], $data["data"], 30, "POST", $data["header"]);
	if ($res["status"] == 200) {
		\think\Db::name("ticket_reply")->where("id", $id)->update(["is_deliver" => 1]);
		return true;
	} else {
		return false;
	}
}
function curlUpload($url = "", $filepath = "", $filename = "")
{
	if (!file_exists($filepath)) {
		return false;
	}
	$file = new CURLFile(realpath($filepath));
	$filename = $filename ?: "file" . substr($filepath, strrpos($filepath, "."));
	$file->setPostFilename($filename);
	$post_data = ["file" => $file];
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	$output = curl_exec($ch);
	curl_close($ch);
	return $output;
}
function setTicketHandle()
{
	$tickets = \think\Db::name("ticket")->field("id")->where("status", "<>", 1)->where("handle", 0)->select()->toArray();
	foreach ($tickets as $v) {
		$ticket_reply = \think\Db::name("ticket_reply")->field("id,admin_id")->where("admin_id", ">", 0)->where("tid", $v["id"])->order("create_time", "desc")->find();
		if (!empty($ticket_reply)) {
			\think\Db::name("ticket")->where("id", $v["id"])->update(["handle" => $ticket_reply["admin_id"]]);
		}
	}
}
function changeProductCycle()
{
	if (!configuration("change_product_cycle_to_recurring")) {
		$products = \think\Db::name("products")->field("id,pay_type")->select()->toArray();
		foreach ($products as $product) {
			$pay_type = json_decode($product["pay_type"], true);
			if ($pay_type["pay_type"] == "day" || $pay_type["pay_type"] == "hour") {
				$pay_type["pay_type"] = "recurring";
				$pay_type_new = json_encode($pay_type);
				\think\Db::name("products")->where("id", $product["id"])->update(["pay_type" => $pay_type_new]);
			}
		}
		updateconfiguration("change_product_cycle_to_recurring", 1);
	}
	return true;
}
function fieldsUpdate()
{
	$db_in_config = (include dirname(__DIR__) . "/app/config/database.php");
	$showTablesInfo = \think\Db::query("SHOW TABLE STATUS FROM `{$db_in_config["database"]}`");
	$textArr = ["text" => "", "mediumtext" => "", "longtext" => "", "tinyblob" => "", "blob" => "", "mediumblob" => "", "longblob" => "", "binary" => "", "varbinary" => ""];
	$tableArr = [];
	$keyArr = [];
	foreach ($showTablesInfo as $table) {
		$table = array_change_key_case($table);
		$table = $table["name"];
		$numFieldsall = fieldsNumFields($table);
		foreach ($numFieldsall as $field) {
			$field = array_change_key_case($field);
			if ($field["key"] == "PRI") {
				$keyArr[$table] = $field["field"];
			}
			if ($field["default"] == "'") {
				$tableArr[$table][] = $field["field"];
				if ($field["comment"] == "'") {
					$field["comment"] = "";
				}
				$field["comment"] = trim($field["comment"]);
				if (isset($textArr[$field["type"]])) {
					$alter = "ALTER TABLE `{$table}` MODIFY COLUMN `{$field["field"]}` {$field["type"]} COMMENT '{$field["comment"]}';\r\n";
				} else {
					$alter = "ALTER TABLE `{$table}` MODIFY COLUMN `{$field["field"]}` {$field["type"]} NOT NULL DEFAULT '' COMMENT '{$field["comment"]}';\r\n";
				}
				\think\Db::query($alter);
			}
		}
	}
	$data = [];
	foreach ($tableArr as $table => $fieldArr) {
		if (strpos($table, "_log") !== false) {
			continue;
		}
		$or = "";
		$fields = "";
		$array2 = [];
		foreach ($fieldArr as $field) {
			$or .= "`{$field}`=\"'\" OR ";
			$fields .= "`" . $field . "`,";
		}
		$or = trim($or, " OR ");
		$fields = trim($fields, ",");
		$sql = "SELECT `{$keyArr[$table]}`,{$fields} FROM `{$table}` where {$or}";
		$result = \think\Db::query($sql);
		foreach ($result as $key => $value) {
			foreach ($value as $k => $v) {
				if ($v == "'") {
					$array2[$key][$k] = "";
				} else {
					$array2[$key][$k] = $v;
				}
			}
		}
		if (count($array2) > 0) {
			fieldsBatchUpdate($table, array_merge([$keyArr[$table]], $fieldArr), $array2, $keyArr[$table]);
		}
	}
	return "success";
}
function fieldsBatchUpdate($table, $arrayKey = [], $array2 = [], $fields = "id", $where = "")
{
	$when = "";
	$in_id = "";
	$ke = 0;
	$where = !empty($where) ? " AND " . $where : "";
	foreach ($arrayKey as $val) {
		$keys[] = "`" . $val . "`";
		$when .= "`{$val}`=CASE `{$fields}`";
		foreach ($array2 as $k => $v) {
			$when .= " WHEN '" . $v[$fields] . "' THEN '" . (get_magic_quotes_gpc() ? $v[$val] : addslashes($v[$val])) . "'";
			if ($ke == 0) {
				$in_id .= "'" . $v[$fields] . "',";
			}
		}
		$when .= " END";
		if ($ke + 1 < count($arrayKey)) {
			$when .= ",";
		}
		$ke++;
	}
	$sql = "UPDATE `{$table}` SET " . $when . " WHERE " . $fields . " IN (" . trim($in_id, ",") . ") {$where}";
	return \think\Db::execute($sql);
}
function fieldsNumFields($table_name)
{
	$field_default = ["tinyint" => 0, "smallint" => 0, "mediumint" => 0, "int" => 0, "integer" => 0, "bigint" => 0, "decimal" => 0, "float" => 0, "double" => 0, "real" => 0, "date" => "'0000-00-00'", "time" => "'00:00:00'", "datetime" => "'0000-00-00 00:00:00'", "timestamp" => "'00000000000000'", "year" => "'0000'", "char" => "''", "varchar" => "''", "tinytext" => ""];
	$re = \think\Db::query("SHOW FULL COLUMNS FROM `{$table_name}`");
	return $re;
}
function hook_add($tag, $fun)
{
	if (!in_array($tag, getSystemHook())) {
		return null;
	}
	\think\facade\Hook::add($tag, $fun);
}
function foo($test)
{
	return $test;
}
function cmf_get_plugin_class_shd($name, $type_dir)
{
	$name = ucwords($name);
	$pluginDir = cmf_parse_name($name);
	$class = "{$type_dir}\\{$pluginDir}\\{$name}Plugin";
	return $class;
}
function cmf_get_oauthPlugin_class_shd($name, $type_dir)
{
	$pluginDir = cmf_parse_name($name);
	$class = "{$type_dir}\\{$pluginDir}\\{$name}";
	return $class;
}
/**
 * 生成访问插件的url
 * @param string $url    url格式：插件名://控制器名/方法
 * @param array  $vars   参数
 * @param bool   $home   是否前台
 * @return string
 */
function shd_addon_url($url, $vars = [], $home = false)
{
	$url = parse_url($url);
	$case_insensitive = true;
	$plugin = $case_insensitive ? \think\Loader::parseName($url["scheme"]) : $url["scheme"];
	$controller = $case_insensitive ? \think\Loader::parseName($url["host"]) : $url["host"];
	$action = trim($case_insensitive ? strtolower($url["path"]) : $url["path"], "/");
	if (isset($url["query"])) {
		parse_str($url["query"], $query);
		$vars = array_merge($query, $vars);
	}
	$params = ["_plugin" => $plugin, "_controller" => $controller, "_action" => $action];
	$params = array_merge($params, $vars);
	if ($home) {
		$plugin = cmf_parse_name($plugin, 1);
		$plugin = \think\Db::name("plugin")->where("name", $plugin)->value("id");
		$params["_plugin"] = $plugin;
		$new = "addons?" . http_build_query($params);
	} else {
		$new = "/" . adminaddress() . "/addons?" . http_build_query($params);
	}
	return $new;
}
function format_nextduedate($timestamp)
{
	if (empty($timestamp)) {
		return ["msg" => "不到期", "class" => ""];
	}
	$diff = $timestamp - time();
	if ($diff >= 604800) {
		return ["msg" => "还剩" . floor($diff / 24 / 3600) . "天", "class" => "badge-success"];
	} else {
		if ($diff < 604800 && $diff > 0) {
			return ["msg" => "即将到期", "class" => "badge-warning"];
		} else {
			return ["msg" => "已逾期", "class" => "badge-danger"];
		}
	}
}
function getOsSvg($os)
{
	$os = strtolower($os);
	switch ($os) {
		case "windows":
			$type = 1;
			break;
		case "centos":
			$type = 2;
			break;
		case "ubuntu":
			$type = 3;
			break;
		case "debian":
			$type = 4;
			break;
		case "esxi":
			$type = 5;
			break;
		case "xenserver":
			$type = 6;
			break;
		case "freebsd":
			$type = 7;
			break;
		case "fedora":
			$type = 8;
			break;
		default:
			$type = "";
			break;
	}
	return $type;
}
function getSystemHook()
{
	$systemHooks = config("app.shd_hooks");
	return $systemHooks;
}
function getClientareaThemes()
{
	$themes = configuration("clientarea_default_themes") ?: "default";
	$file = WEB_ROOT . "themes/clientarea/" . $themes;
	$yaml = view_tpl_yaml($file);
	$parent = $yaml["config-parent-theme"];
	if ($parent) {
		return $parent;
	}
	return $themes;
}
function shd_version()
{
	try {
		$version = trim(file_get_contents(CMF_ROOT . "version"));
	} catch (Exception $e) {
		$version = "1.0.0";
	}
	return $version;
}
function sessionInit()
{
	$cancellation_time = configuration("cancellation_time") ?: 1;
	$lifetime = $cancellation_time * 24 * 60 * 60;
	if (!file_exists("/tmp/session")) {
		mkdir("/tmp/session", 493, true);
	}
	$config = array_merge(config("session"), ["expire" => $lifetime, "path" => "/tmp/session"]);
	\think\facade\Session::init($config);
	session_write_close();
	return true;
}
function getAdminThemesAll()
{
	$admin_application = adminaddress();
	$themes = array_map("basename", glob(WEB_ROOT . "{$admin_application}/themes/*", GLOB_ONLYDIR));
	return $themes;
}
function callbackCustom($param, $fn)
{
	return $fn($param);
}
function rangeTypeCheck($phone)
{
	$rangeType = preg_match("/^((\\+86)|(86))?[1][3456789][0-9]{9}\$/", $phone) ? 0 : 1;
	if ($rangeType == 0) {
		if (configuration("shd_allow_sms_send") == 0) {
			return ["status" => 400, "msg" => lang("国内短信功能已关闭")];
		}
	} elseif ($rangeType == 1) {
		if (configuration("shd_allow_sms_send_global") == 0) {
			return ["status" => 400, "msg" => lang("国际短信功能已关闭")];
		}
	}
	return true;
}
function sendGlobal()
{
	if (configuration("allow_login_register") == 1) {
		$allow = 1;
		if (configuration("shd_allow_sms_send_global") == 0) {
			$allow = 0;
		}
	} else {
		$allow = 0;
	}
	return $allow;
}
function zjmfhook($name, $module = "certification", $data = [], $method = "")
{
	$tmp = \think\Db::name("plugin")->where("name", $name)->where("module", $module)->where("status", 1)->find();
	if (empty($tmp) && $module == "certification") {
		return ["status" => 400, "msg" => "无效的认证方式"];
	}
	$lowerName = lower_str_type($name);
	$pluginsClass = "{$module}\\{$lowerName}\\" . $name . "Plugin";
	$methods = get_class_methods($pluginsClass) ?: [];
	$pluginName = $name;
	if (in_array(lcfirst($pluginName) . "idcsmartauthorize", $methods)) {
		$zjmf_authorize = configuration("zjmf_authorize");
		if (empty($zjmf_authorize)) {
			return ["status" => 400, "msg" => "授权错误,请检查域名或ip"];
		} else {
			$auth = de_authorize($zjmf_authorize);
			$ip = de_systemip(configuration("authsystemip"));
			if ($ip != $auth["ip"] && !empty($ip)) {
				return jsonrule(["status" => 307, "msg" => "授权错误,请检查ip"]);
			}
			if (time() > $auth["last_license_time"] + 604800 || ltrim(str_replace("https://", "", str_replace("http://", "", $auth["domain"])), "www.") != ltrim(str_replace("https://", "", str_replace("http://", "", $_SERVER["HTTP_HOST"])), "www.") || $auth["installation_path"] != CMF_ROOT || $auth["license"] != configuration("system_license")) {
				return ["status" => 400, "msg" => "授权错误,请检查域名或ip"];
			} else {
				if (!empty($auth["facetoken"])) {
					return ["status" => 400, "msg" => "您的授权已被暂停,请前往智简魔方会员中心检查授权状态"];
				}
				if ($auth["status"] == "Suspend") {
					return ["status" => 400, "msg" => "您的授权已被暂停,请前往智简魔方会员中心检查授权状态"];
				}
				$app = $auth["app"];
				if (!in_array($pluginName, $app)) {
					return ["status" => 400, "msg" => "安装失败,插件未授权"];
				}
			}
		}
	}
	if (!in_array($method, $methods)) {
		return false;
	}
	return \think\facade\Hook::exec([$pluginsClass, $method], $data);
}
function pluginConfig($name, $module = "certification")
{
	$pluginDb = \think\Db::name("plugin")->where(["module" => $module, "name" => $name])->field("config")->find();
	if (!$pluginDb["config"]) {
		return false;
	}
	return json_decode($pluginDb["config"], true);
}
function getPluginsList($module = "certification", $action = null)
{
	$where = function (\think\db\Query $query) use($module) {
		$query->where("module", $module)->where("status", 1);
	};
	$plugins = \think\Db::name("plugin")->field("id,name,title,config")->where($where)->order("order", "asc")->order("id", "asc")->select()->toArray();
	$plugins_filter = [];
	foreach ($plugins as $key => $plugin) {
		$_config = json_decode($plugin["config"], true);
		$cname = cmf_parse_name($plugin["name"]);
		$plugin["custom_fields"] = getCertificationCustomFields($cname, "certification");
		$plugin["pay"] = floatval($_config["amount"]) > 0 ? true : false;
		unset($plugin["config"]);
		$class = cmf_get_plugin_class_shd($plugin["name"], $module);
		if (!class_exists($class)) {
			continue;
		}
		$methods = get_class_methods($class) ?: [];
		if ($action == "personal") {
			if (in_array("personal", $methods)) {
				$plugins_filter[] = $plugin;
			}
		} elseif ($action == "enterprises") {
			if (in_array("company", $methods)) {
				$plugins_filter[] = $plugin;
			}
		} else {
			$plugins_filter[] = $plugin;
		}
	}
	return $plugins_filter;
}
function getCertificationCustomFields($cname, $module = "certification")
{
	$cname = cmf_parse_name($cname, 1);
	$class = cmf_get_plugin_class_shd($cname, $module);
	if (method_exists($class, "collectionInfo")) {
		$obj = new $class();
		$customfields = $obj->collectionInfo();
	}
	$customfields_filter = [];
	foreach ($customfields as $key => $customfield) {
		$customfield["field"] = $key;
		$customfields_filter[] = $customfield;
	}
	return $customfields_filter;
}
function CompatibleOldVersionThree()
{
	if (!configuration("certification_compatible_old")) {
		$data = [];
		$certifi_select = configuration("certifi_select");
		$arr = explode(",", $certifi_select) ?: [];
		if (in_array("three", $arr)) {
			$certifi_three_type = configuration("certifi_three_type");
			if ($certifi_three_type == "two") {
				$certifi_three_type = 2;
			} elseif ($certifi_three_type == "three") {
				$certifi_three_type = 3;
			} else {
				$certifi_three_type = 4;
			}
			$threehc_config = ["module_name" => "华辰--三要素", "app_code" => configuration("certifi_appcode"), "type" => $certifi_three_type, "amount" => 0, "free" => 0];
			$data[] = ["type" => 1, "has_admin" => 0, "status" => 1, "create_time" => time(), "name" => "Threehc", "title" => "华辰--三要素", "url" => "", "hooks" => "", "author" => "顺戴网络", "author_url" => "", "version" => "1.0", "description" => "三要素--深圳华辰", "config" => json_encode($threehc_config), "module" => "certification", "order" => 0];
		}
		if (in_array("phonethree", $arr)) {
			$phonethree_config = ["module_name" => "手机三要素", "app_code" => configuration("certifi_phonethree_appcode"), "amount" => 0, "free" => 0];
			$data[] = ["type" => 1, "has_admin" => 0, "status" => 1, "create_time" => time(), "name" => "Phonethree", "title" => "手机三要素", "url" => "", "hooks" => "", "author" => "顺戴网络", "author_url" => "", "version" => "1.0", "description" => "手机三要素", "config" => json_encode($phonethree_config), "module" => "certification", "order" => 0];
		}
		if (in_array("ali", $arr)) {
			$ali_config = ["module_name" => "芝麻信用", "app_id" => configuration("certifi_app_id"), "biz_code" => configuration("certifi_alipay_biz_code"), "public_key" => configuration("certifi_alipay_public_key"), "private_key" => configuration("certifi_merchant_private_key"), "amount" => 0, "free" => 0];
			$data[] = ["type" => 1, "has_admin" => 0, "status" => 1, "create_time" => time(), "name" => "Ali", "title" => "芝麻信用", "url" => "", "hooks" => "", "author" => "顺戴网络", "author_url" => "", "version" => "1.0", "description" => "芝麻信用", "config" => json_encode($ali_config), "module" => "certification", "order" => 0];
		}
		if (!empty($data[0])) {
			\think\Db::name("plugin")->insertAll($data);
		}
		if (configuration("smsbao_account")) {
			$sms["smsbao"] = ["title" => "短信宝", "description" => "短信宝", "help_url" => "http://www.smsbao.com/", "config" => ["user" => configuration("smsbao_account"), "pass" => configuration("smsbao_pwd"), "sign" => configuration("smsbao_mobile_signature")]];
		}
		if (configuration("aliyun_mobile_accesskeyid") != "Your Ali Key ID" && configuration("aliyun_mobile_accesskeyid")) {
			$sms["aliyun"] = ["title" => "阿里云", "description" => "阿里云", "help_url" => "https://www.aliyun.com/product/sms", "config" => ["AccessKeyId" => configuration("aliyun_mobile_accesskeyid"), "AccessKeySecret" => configuration("aliyun_mobile_accesskeysecret"), "SignName" => configuration("aliyun_mobile_signature")]];
		}
		if (configuration("submail_app_id") != "Your Submail APP ID" && configuration("submail_app_id")) {
			$sms["submail"] = ["title" => "赛邮", "description" => "赛邮", "help_url" => "https://www.mysubmail.com/", "config" => ["app_id" => configuration("submail_app_id"), "app_key" => configuration("submail_app_key"), "app_sign" => configuration("submail_sign_name"), "international_app_id" => configuration("submail_international_app_id"), "international_app_key" => configuration("submail_international_app_key"), "international_app_sign" => configuration("submail_international_sign_name")]];
		}
		$insertAll = [];
		foreach ($sms as $k => $v) {
			$plug = [];
			$plug["type"] = 1;
			$plug["has_admin"] = 0;
			$plug["status"] = 1;
			$plug["create_time"] = 1618883096;
			$plug["name"] = ucfirst($k);
			$plug["title"] = $v["title"];
			$plug["author"] = "智简魔方";
			$plug["version"] = "1.0";
			$plug["description"] = $v["description"];
			$plug["help_url"] = $v["help_url"];
			$plug["module"] = "sms";
			$plug["config"] = json_encode($v["config"]);
			$insertAll[] = $plug;
		}
		if (count($insertAll)) {
			\think\Db::name("plugin")->insertAll($insertAll);
		}
		productMenu();
		updateconfiguration("certification_compatible_old", 1);
	}
	return true;
}
function shd_certification_url($url, $vars = [], $home = false)
{
	$url = parse_url($url);
	$case_insensitive = true;
	$plugin = $case_insensitive ? \think\Loader::parseName($url["scheme"]) : $url["scheme"];
	$controller = $case_insensitive ? \think\Loader::parseName($url["host"]) : $url["host"];
	$action = trim($case_insensitive ? strtolower($url["path"]) : $url["path"], "/");
	if (isset($url["query"])) {
		parse_str($url["query"], $query);
		$vars = array_merge($query, $vars);
	}
	$params = ["_plugin" => $plugin, "_controller" => $controller, "_action" => $action];
	$params = array_merge($params, $vars);
	if ($home) {
		$plugin = cmf_parse_name($plugin, 1);
		$plugin = \think\Db::name("plugin")->where("name", $plugin)->value("id");
		$params["_plugin"] = $plugin;
		$new = "certification?" . http_build_query($params);
	} else {
		$new = "/" . adminaddress() . "/certification?" . http_build_query($params);
	}
	return $new;
}
function pay($invoiceid)
{
	$paymodal = file_get_contents(WEB_ROOT . "themes/clientarea/default/includes/paymodal.tpl");
	return $paymodal . "<a href=\"javascript: payamount({$invoiceid});\" class=\"text-primary mr-2\" id=\"{$invoiceid}\"><i class=\"fas fa-check-circle\"></i>支付</a>";
}
function updatePersonalCertifiStatus($data)
{
	$param = ["status" => $data["status"] ?: 4, "auth_fail" => $data["auth_fail"] ?: "", "certify_id" => $data["certify_id"] ?: ""];
	$uid = request()->uid;
	\think\Db::name("certifi_person")->where("auth_user_id", $uid)->order("id", "desc")->update($param);
	\think\Db::name("certifi_log")->where("uid", $uid)->order("id", "desc")->limit(1)->update(["status" => $data["status"] ?: 4, "error" => $data["auth_fail"] ?: "", "notes" => $data["notes"] ?: ""]);
	return true;
}
function updateCompanyCertifiStatus($data)
{
	$param = ["status" => $data["status"] ?: 2, "auth_fail" => $data["auth_fail"] ?: "", "certify_id" => $data["certify_id"] ?: ""];
	$uid = request()->uid;
	\think\Db::name("certifi_company")->where("auth_user_id", $uid)->order("id", "desc")->update($param);
	\think\Db::name("certifi_log")->where("uid", $uid)->order("id", "desc")->limit(1)->update(["status" => $data["status"] ?: 4, "error" => $data["auth_fail"] ?: "", "notes" => $data["notes"] ?: ""]);
	return true;
}
function replaceEmailTpl($table_name = "email_tpl_replace")
{
	try {
		return \think\Db::transaction(function () use($table_name) {
			$replace_model = \think\Db::name($table_name)->where("custom", 0)->select()->toArray();
			$ratio_model = \think\Db::name("email_templates_ratio")->where("custom", 0)->select()->toArray();
			$model = \think\Db::name("email_templates")->where("custom", 0)->select()->toArray();
			$arr = ["replace_model", "ratio_model", "model"];
			foreach ($arr as $val) {
				if (!empty(${$val})) {
					${$val} = array_column(${$val}, null, "id");
				}
			}
			foreach ($model as $k => $v) {
				if (isset($ratio_model[$k]) && $v["message"] == $ratio_model[$k]["message"]) {
					isset($replace_model[$k]) && \think\Db::name("email_templates")->where("id", $k)->update(["message" => $replace_model[$k]["message"]]);
					continue;
				}
				foreach ($replace_model as $r_key => $val) {
					if ($val["name"] == $v["name"] && $val["name_en"] == $v["name_en"]) {
						if ($val["message"] == $v["message"]) {
							break;
						}
						\think\Db::name("email_templates")->where("id", $k)->update(["message" => $val["message"]]);
						break;
					}
				}
			}
			\think\Db::query("DROP TABLE `shd_" . $table_name . "`");
			\think\Db::query("DROP TABLE `shd_email_templates_ratio`");
			return true;
		});
	} catch (Throwable $e) {
		return false;
	}
}
function productMenu()
{
	try {
		return (new \app\common\logic\Menu())->productMenu();
	} catch (Throwable $e) {
		return false;
	}
}
function createMenus()
{
	try {
		return (new \app\common\logic\Menu())->createMenus();
	} catch (Throwable $e) {
		return false;
	}
}
function recurseGetLastVersion(&$last, $arr = [])
{
	$allowed_local_test_license = ["7563DA6198C41976A46F4F57D836C0BE", "E54D92AA56A69D8C87DA5B4604ADFCCF", "772EC3A11FC87438E9B7E40AC4E86F8D", "22EC411DD6931A0275EC0B8887D4CC0F"];
	$last = array_pop($arr);
	if (explode(",", $last)[2] == "beta_test") {
		if (in_array(configuration("system_license"), $allowed_local_test_license) || configuration("custom_system_license") == "beta_test") {
			return null;
		} else {
			recurseGetLastVersion($last, $arr);
		}
	}
	return null;
}
function updateSmsConfig()
{
	if (configuration("sms_operator_global") == "update") {
		updateconfiguration("sms_operator_global", configuration("sms_operator"));
	}
}
function getServesId($gid)
{
	$g_model = \think\Db::name("server_groups")->find($gid);
	if (!$g_model) {
		return [];
	}
	$s_model = \think\Db::name("servers")->where("gid", $gid)->where("disabled", 0)->select()->toArray();
	if (empty($s_model)) {
		return [];
	}
	$s_model = array_column($s_model, null, "id");
	foreach ($s_model as $k => $v) {
		$s_model[$k]["use_num"] = \think\Db::name("host")->where("serverid", $v["id"])->count();
	}
	switch ($g_model["mode"]) {
		case 1:
			return ["id" => averageMode($s_model)];
			break;
		case 2:
			return ["id" => oneByMode($s_model)];
			break;
		default:
			return [];
	}
}
/**
 * 平均分配
 * @param $s_model
 * @return false|int|string
 */
function averageMode($s_model)
{
	$min_arr = array_map(function ($v) {
		if ($v["max_accounts"] <= 0) {
			return ["id" => $v["id"], "val" => $v["use_num"] * 100];
		}
		return ["id" => $v["id"], "val" => $v["use_num"] / $v["max_accounts"]];
	}, $s_model);
	$min_arr = array_column($min_arr, "val", "id");
	return array_search(min(array_values($min_arr)), $min_arr);
}
/**
 * 逐一分配
 * @param $s_model
 * @return false|int|mixed|string
 */
function oneByMode($s_model)
{
	foreach ($s_model as $k => $v) {
		if ($v["max_accounts"] <= 0) {
			continue;
		}
		if ($v["use_num"] < $v["max_accounts"]) {
			return $v["id"];
		}
	}
	return averagemode($s_model);
}
/**
 * 添加菜单到默认的菜单导航
 * @return array|int|string
 */
function addNavToMenus()
{
	return (new \app\common\logic\Menu())->addNavToMenus();
}
/**
 * 同步通用接口组，之前的数据
 * @return mixed|void
 */
function serversTransfer()
{
	try {
		return \think\Db::transaction(function () {
			$servers = \think\Db::name("servers")->field("type,gid,id")->select()->toArray();
			if (empty($servers)) {
				return null;
			}
			$servers_group = \think\Db::name("server_groups")->field("type, id")->select()->toArray();
			if (empty($servers_group)) {
				return null;
			}
			$servers = array_column($servers, null, "id");
			$servers_group = array_column($servers_group, null, "id");
			foreach ($servers as $key => $val) {
				if ($val["type"]) {
					continue;
				}
				if (!isset($servers_group[$val["gid"]])) {
					continue;
				}
				if (!$servers_group[$val["gid"]]["type"]) {
					continue;
				}
				\think\Db::name("servers")->where("id", $val["id"])->update(["type" => $servers_group[$val["gid"]]["type"]]);
			}
		});
	} catch (Throwable $e) {
		return null;
	}
}
/**
 * 添加
 * @param string $title
 * @return mixed
 */
function setMenuToRule($value = "下个版本", $name = "title")
{
	return \think\Db::transaction(function () use($value, $name) {
		if (!$value) {
			return null;
		}
		if (!is_array($value)) {
			$value = [$value];
		}
		$menu_id = \think\Db::name("auth_rule")->field("id,name")->whereIn($name, $value)->select()->toArray();
		if (!$menu_id) {
			return null;
		}
		$role = \think\Db::name("role")->field("id,auth_role,name")->select()->toArray();
		foreach ($role as $val) {
			$insert = [];
			foreach ($menu_id as $v) {
				$insert[] = ["role_id" => $val["id"], "rule_name" => $v["name"], "rule_id" => $v["id"], "type" => "admin_url"];
			}
			$insert && \think\Db::name("auth_access")->insertAll($insert);
		}
		return null;
	});
}
function judgeApi($uid)
{
	$client = \think\Db::name("clients")->where("id", $uid)->find();
	$api_open = $client["api_open"];
	if (configuration("allow_resource_api") && $api_open) {
		if (configuration("allow_resource_api_phone") && empty($client["phonenumber"])) {
			return false;
		}
		if (configuration("allow_resource_api_realname") && !checkcertify($uid)) {
			return false;
		}
		return true;
	}
	return false;
}
/**
 * xue
 * 更换授权码动作
 */
function putLicenseAfter()
{
	if (!getedition()) {
		\think\Db::name("user")->where("is_sale", 1)->update(["cat_ownerless" => 1]);
		updateconfiguration("artificial_auto_send_msg", 0);
		updateconfiguration("certifi_business_open", 0);
		updateconfiguration("certifi_business_is_upload", 0);
		updateconfiguration("certifi_business_is_author", 0);
		updateconfiguration("certifi_business_author_path", "");
	}
}
/**
 * xue
 * 免费版不可用以异常的形式抛出
 */
function throwEditionError()
{
	if (!getedition()) {
		throw new Exception("免费版该功能不可用！");
	}
}
function manualHostOld()
{
	\think\Db::startTrans();
	try {
		$upper_reaches = \think\Db::name("upper_reaches")->select()->toArray();
		foreach ($upper_reaches as $v) {
			$insert1 = ["name" => $v["name"], "des" => $v["bz"], "contact_way" => $v["phone"], "create_time" => $v["create_time"], "type" => "manual"];
			$id = \think\Db::name("zjmf_finance_api")->insertGetId($insert1);
			\think\Db::name("upper_reaches_res")->where("pid", $v["id"])->update(["pid" => $id]);
			\think\Db::name("products")->where("upper_reaches_id", $v["id"])->update(["upper_reaches_id" => $id]);
		}
		\think\Db::commit();
		return true;
	} catch (Exception $e) {
		\think\Db::rollback();
		return false;
	}
}
function getLastVersion()
{
	if (!file_exists(CMF_ROOT . "/public/upgrade/upgrade.log")) {
		return false;
	}
	$handle = fopen(CMF_ROOT . "/public/upgrade/upgrade.log", "r");
	$content = "";
	while (!feof($handle)) {
		$content .= fread($handle, 8080);
	}
	fclose($handle);
	$arr = explode("\n", $content);
	$fun = function ($value) {
		if (empty($value)) {
			return false;
		} else {
			return true;
		}
	};
	$arr = array_filter($arr, $fun);
	$arr_last_pop = array_pop($arr);
	$arr_last = explode(",", $arr_last_pop);
	$arr[] = $arr_last_pop;
	return $arr_last[1];
}
function upgradeHandle()
{
	if (file_exists(CMF_ROOT . "/public/upgrade/upgrade.php")) {
		return json(["status" => 1001, "msg" => "请先执行升级文件", "data" => ["url" => "/upgrade/install.php"]]);
	}
	return json(["status" => 1002, "msg" => "升级文件不存在，请重新下载安装包！"]);
}
function upgradeDel($current_version, $version)
{
	if (version_compare($current_version, $version, "==")) {
		if (configuration("executed_update")) {
			if (is_dir(CMF_ROOT . "/public/upgrade")) {
				deletedir(CMF_ROOT . "/public/upgrade");
				updateconfiguration("executed_update", 0);
			}
		}
	}
}
function systemInstallHandle()
{
	if (!configuration("system_install_last")) {
		return null;
	}
	$menu_last_id = \think\Db::name("menus")->order("id", "desc")->find();
	if ($menu_last_id) {
		\think\Db::name("menus")->where("id", "<", $menu_last_id["id"])->delete();
	}
	\think\Db::name("configuration")->where("setting", "system_install_last")->delete();
}
function upgradeSmsTemplate()
{
	$data = ["resume_use", "realname_pass_remind", "binding_remind"];
	$now_sms_plugin = configuration("sms_operator");
	if (empty($now_sms_plugin)) {
		return false;
	}
	$plugin_class = cmf_get_plugin_class_shd($now_sms_plugin, "sms");
	if (!class_exists($plugin_class)) {
		return false;
	}
	$plugin = new $plugin_class();
	$data_install = $plugin->install();
	$message_template_type = config("message_template_type");
	$message_template_type = array_column($message_template_type, "id", "name");
	if (is_array($message_template_type)) {
		foreach ($data_install as $k => $vi) {
			if (!in_array($vi["name"], $data)) {
				continue;
			}
			$message_template_typeid = $message_template_type[$vi["name"]];
			if (!empty($message_template_typeid)) {
				$data_install[$k]["id"] = $message_template_typeid;
			}
		}
	}
	$methods = get_class_methods($plugin_class) ?: [];
	foreach ($methods as $m) {
		if (strpos($m, "GlobalTemplate") !== false || $m == "sendGlobalSms") {
			$methods_global = true;
			break;
		}
	}
	foreach ($data_install as $v) {
		$check = \think\Db::name("message_template")->where("sms_operator", $now_sms_plugin)->where("title", $v["type"])->value("id");
		if ($check) {
			continue;
		}
		$range_type = !empty($v["range_type"]) ? 1 : 0;
		$message_template["range_type"] = $range_type;
		$message_template["title"] = $v["type"];
		$message_template["content"] = $v["var"];
		$message_template["sms_operator"] = $now_sms_plugin;
		$message_template["remark"] = $v["type"];
		$message_template["template_id"] = $v["template_id"] ?? "";
		$message_template["status"] = 0;
		$message_template["create_time"] = time();
		$message_template["update_time"] = time();
		$message_template_link["type"] = $v["id"];
		$message_template_link["range_type"] = $range_type;
		$message_template_link["sms_operator"] = $now_sms_plugin;
		$message_template_link["is_use"] = 1;
		$sms_temp_id = \think\Db::name("message_template")->insertGetId($message_template);
		$message_template_link["sms_temp_id"] = $sms_temp_id;
		\think\Db::name("message_template_link")->insertGetId($message_template_link);
		$ids = [];
		array_push($ids, $sms_temp_id);
		if ($methods_global === true) {
			$message_template["range_type"] = 1;
			$sms_temp_id_global = \think\Db::name("message_template")->insertGetId($message_template);
			$message_template_link["sms_temp_id"] = $sms_temp_id_global;
			$message_template_link["range_type"] = 1;
			\think\Db::name("message_template_link")->insertGetId($message_template_link);
			array_push($ids, $sms_temp_id_global);
		}
		$message_template = $message_template_link = [];
	}
	$config_message = controller("ConfigMessage");
	request()->ids = $ids;
	request()->type = $now_sms_plugin;
	request()->server("REQUEST_METHOD", "POST");
	$config_message->checkPost();
	return true;
}
function upgradeEmilTemplate()
{
	$data = [["name" => "解除暂停提醒(用户)", "name_en" => "Resume_Use", "type" => "product", "custom" => 0, "subject" => "[{SYSTEM_COMPANYNAME}]解除暂停成功，谢谢支持", "message" => "&lt;!DOCTYPE html&gt;
                        &lt;html&gt;
                        &lt;head&gt;
                        &lt;/head&gt;
                        &lt;body&gt;
                        &lt;p&gt;&amp;nbsp;&lt;/p&gt;
                        &lt;div class=&quot;logo_top&quot; style=&quot;padding: 20px 0px;&quot;&gt;&lt;img style=&quot;display: block; width: auto; margin: 0px auto;&quot; src=&quot;{SYSTEM_EMAIL_LOGO_URL}&quot; alt=&quot;&quot; /&gt;&lt;/div&gt;
                        &lt;div class=&quot;card&quot; style=&quot;width: 650px; margin: 0px auto; font-size: 0.8rem; line-height: 22px; padding: 40px 50px; box-sizing: border-box;&quot;&gt;
                        &lt;h2 style=&quot;text-align: center;&quot;&gt;{SYSTEM_COMPANYNAME}[{SYSTEM_COMPANYNAME}]续费成功，谢谢支持&lt;/h2&gt;
                        &lt;br /&gt;&lt;strong&gt;尊敬的用户{USERNAME}&lt;/strong&gt;&lt;br /&gt;&lt;br /&gt;&lt;span style=&quot;margin: 0; padding: 0; display: inline-block; margin-top: 55px;&quot;&gt;您拥有的产品&lt;span style=&quot;font-size: 12.8px;&quot;&gt;（{PRODUCT_NAME}）&lt;/span&gt;现已解除暂停恢复正常使用,感谢您的支持!&lt;/span&gt;&lt;span style=&quot;margin: 0; padding: 0; display: inline-block; margin-top: 60px;&quot;&gt;感谢您对我们的信赖！&lt;/span&gt;&lt;/div&gt;
                        &lt;div class=&quot;card&quot; style=&quot;width: 650px; margin: 0px auto; font-size: 0.8rem; line-height: 22px; padding: 40px 50px; box-sizing: border-box;&quot;&gt;&lt;br /&gt;&lt;span style=&quot;margin: 0; padding: 0; color: #303133; font-family: &#039;Alibaba PuHuiTi&#039;; font-size: 16px;&quot;&gt;&amp;nbsp;&lt;/span&gt;&amp;nbsp;&lt;span style=&quot;margin: 0; padding: 0; display: inline-block; width: 100%; text-align: right;&quot;&gt;&lt;strong&gt;{SYSTEM_COMPANYNAME}&lt;/strong&gt;&lt;/span&gt;&lt;span style=&quot;margin: 0; padding: 0; margin-top: 20px; display: inline-block; width: 100%; text-align: right;&quot;&gt;{SEND_TIME}&lt;/span&gt;&lt;/div&gt;
                        &lt;ul class=&quot;banquan&quot; style=&quot;display: flex; justify-content: center; flex-wrap: nowrap; color: #b7b8b9; font-size: 0.4rem; padding: 20px 0px; margin: 0px;&quot;&gt;
                        &lt;li style=&quot;list-style: none;&quot;&gt;{SYSTEM_COMPANYNAME}&lt;/li&gt;
                        &lt;/ul&gt;
                        &lt;/body&gt;
                        &lt;/html&gt;"]];
	foreach ($data as $v) {
		$isname = \think\Db::name("email_templates")->where("name_en", $v["name_en"])->find();
		if (!empty($isname)) {
			continue;
		}
		$newtemplate["name"] = $v["name"];
		$newtemplate["name_en"] = $v["name_en"];
		$newtemplate["type"] = $v["type"];
		$newtemplate["custom"] = $v["custom"];
		$newtemplate["subject"] = $v["subject"];
		$newtemplate["message"] = $v["message"];
		$newtemplate["create_time"] = time();
		$newtemplate["language"] = "";
		\think\Db::name("email_templates")->insertGetId($newtemplate);
		$allowedlanguage = get_language_list();
		$langs = \think\Db::name("email_templates")->field("language")->group("language")->select();
		if (!empty($langs)) {
			foreach ($langs as $key => $lang) {
				if (in_array($lang["language"], $allowedlanguage)) {
					$newtemplate["language"] = $lang["language"];
					\think\Db::name("email_templates")->insertGetId($newtemplate);
				}
			}
		}
	}
	return true;
}
function pluginIdcsmartauthorize($pluginName)
{
	$zjmf_authorize = configuration("zjmf_authorize");
	if (empty($zjmf_authorize)) {
		return ["status" => 400, "msg" => "授权错误,请检查域名或ip"];
	} else {
		$auth = de_authorize($zjmf_authorize);
		$ip = de_systemip(configuration("authsystemip"));
		if ($ip != $auth["ip"] && !empty($ip)) {
			return jsonrule(["status" => 307, "msg" => "授权错误,请检查ip"]);
		}
		if (time() > $auth["last_license_time"] + 604800 || ltrim(str_replace("https://", "", str_replace("http://", "", $auth["domain"])), "www.") != ltrim(str_replace("https://", "", str_replace("http://", "", $_SERVER["HTTP_HOST"])), "www.") || $auth["installation_path"] != CMF_ROOT || $auth["license"] != configuration("system_license")) {
			return ["status" => 400, "msg" => "授权错误,请检查域名或ip"];
		} else {
			if (!empty($auth["facetoken"])) {
				return ["status" => 400, "msg" => "您的授权已被暂停,请前往智简魔方会员中心检查授权状态"];
			}
			if ($auth["status"] == "Suspend") {
				return ["status" => 400, "msg" => "您的授权已被暂停,请前往智简魔方会员中心检查授权状态"];
			}
			$app = $auth["app"];
			if (!in_array($pluginName, $app)) {
				return ["status" => 400, "msg" => "插件未授权"];
			}
		}
	}
	return ["status" => 200];
}
function serverModuleIdcsmartauthorize($module)
{
	$zjmf_authorize = configuration("zjmf_authorize");
	if (empty($zjmf_authorize)) {
		return ["status" => 400, "msg" => "授权错误,请检查域名或ip"];
	} else {
		$auth = de_authorize($zjmf_authorize);
		$ip = de_systemip(configuration("authsystemip"));
		if ($ip != $auth["ip"] && !empty($ip)) {
			return jsonrule(["status" => 307, "msg" => "授权错误,请检查ip"]);
		}
		if (time() > $auth["last_license_time"] + 604800 || ltrim(str_replace("https://", "", str_replace("http://", "", $auth["domain"])), "www.") != ltrim(str_replace("https://", "", str_replace("http://", "", $_SERVER["HTTP_HOST"])), "www.") || $auth["installation_path"] != CMF_ROOT || $auth["license"] != configuration("system_license")) {
			return ["status" => 400, "msg" => "授权错误,请检查域名或ip"];
		} else {
			if (!empty($auth["facetoken"])) {
				return ["status" => 400, "msg" => "您的授权已被暂停,请前往智简魔方会员中心检查授权状态"];
			}
			if ($auth["status"] == "Suspend") {
				return ["status" => 400, "msg" => "您的授权已被暂停,请前往智简魔方会员中心检查授权状态"];
			}
			$app = $auth["app"];
			if (!in_array($module, $app)) {
				return ["status" => 400, "msg" => "插件未授权"];
			}
		}
	}
	return ["status" => 200];
}
function updateConfigOptionUnit()
{
	$type = config("configurable_option_type_name");
	$new_type = array_column($type, "unit", "id");
	$options = \think\Db::name("product_config_options")->group("option_type")->column("option_type");
	array_walk($options, function ($value) use($new_type) {
		\think\Db::name("product_config_options")->where("option_type", $value)->update(["unit" => $new_type[$value]]);
	});
	return true;
}
function addWebDefaultNav()
{
	$_data2 = [];
	$field = ["name", "url", "pid", "order", "fa_icon", "nav_type", "relid", "menuid", "lang", "plugin", "menu_type"];
	$data = [["首页", "index", 0, 0, "", 0, "", 1, json_encode(["chinese" => "首页", "chinese_tw" => "首頁", "english" => "home page"]), "", 2], ["产品服务", "", 0, 0, "", 1, "", 1, json_encode(["chinese" => "产品服务", "chinese_tw" => "產品服務", "english" => "Product service"]), "", 2]];
	$_data = [];
	foreach ($data as $k => $v) {
		$_data[] = array_combine($field, $v);
	}
	\think\Db::name("nav")->insertAll($_data);
	$data2 = ["服务支持", "", 0, 0, "", 0, "", 1, json_encode(["chinese" => "服务支持", "chinese_tw" => "服務支援", "english" => "Service support"]), "", 2];
	$data2_id = \think\Db::name("nav")->insertGetId(array_combine($field, $data2));
	$data2_son = [["帮助中心", "help", $data2_id, 0, "", 0, "", 1, json_encode(["chinese" => "帮助中心", "chinese_tw" => "幫助中心", "english" => "Help center"]), "", 2], ["管家服务", "management", $data2_id, 0, "", 0, json_encode(["chinese" => "管家服务", "chinese_tw" => "管家服務", "english" => "butler service"]), 1, "", "", 2], ["新闻资讯", "news", $data2_id, 0, "", 0, "", 1, json_encode(["chinese" => "新闻资讯", "chinese_tw" => "新聞資訊", "english" => "News information"]), "", 2]];
	foreach ($data2_son as $k => $v) {
		$_data2[] = array_combine($field, $v);
	}
	\think\Db::name("nav")->insertAll($_data2);
	$data1 = ["关于我们", "about", 0, 0, "", 0, "", 1, json_encode(["chinese" => "关于我们", "chinese_tw" => "關於我們", "english" => "About us"]), "", 2];
	$about_son = ["联系我们", "contact", 0, 0, "", 0, "", 1, json_encode(["chinese" => "联系我们", "chinese_tw" => "聯繫我們", "english" => "contact us"]), "", 2];
	$about_id = \think\Db::name("nav")->insertGetId(array_combine($field, $data1));
	$about_son = array_combine($field, $about_son);
	$about_son["pid"] = $about_id;
	\think\Db::name("nav")->insert($about_son);
}
function addWebFootDefaultNav()
{
	$_data1 = $_data2 = $_data3 = [];
	$field = ["name", "url", "pid", "order", "fa_icon", "nav_type", "relid", "menuid", "lang", "plugin", "menu_type"];
	$data1 = ["主要产品", "", 0, 0, "", 0, "", 1, json_encode(["chinese" => "主要产品", "chinese_tw" => "主要產品", "english" => "main products"]), "", 3];
	$data1_id = \think\Db::name("nav")->insertGetId(array_combine($field, $data1));
	$data1_son = [["云服务器", "cloud", $data1_id, 0, "", 0, "", 1, json_encode(["chinese" => "云服务器", "chinese_tw" => "雲服務器", "english" => "Cloud server"]), "", 3], ["独立服务器", "server", $data1_id, 0, "", 0, "", 1, json_encode(["chinese" => "独立服务器", "chinese_tw" => "獨立服務器", "english" => "Stand alone server"]), "", 3], ["SSL证书代办", "ssl", $data1_id, 0, "", 0, "", 1, json_encode(["chinese" => "SSL证书代办", "chinese_tw" => "SSL證書代辦", "english" => "SSL certificate agent"]), "", 3]];
	foreach ($data1_son as $k => $v) {
		$_data1[] = array_combine($field, $v);
	}
	\think\Db::name("nav")->insertAll($_data1);
	$data2 = ["服务支持", "", 0, 0, "", 0, "", 1, json_encode(["chinese" => "服务支持", "chinese_tw" => "服務支援", "english" => "Service support"]), "", 3];
	$data2_id = \think\Db::name("nav")->insertGetId(array_combine($field, $data2));
	$data2_son = [["帮助中心", "help", $data2_id, 0, "", 0, "", 1, json_encode(["chinese" => "帮助中心", "chinese_tw" => "幫助中心", "english" => "Help center"]), "", 3], ["管家服务", "management", $data2_id, 0, "", 0, json_encode(["chinese" => "管家服务", "chinese_tw" => "管家服務", "english" => "butler service"]), 1, "", "", 3], ["新闻资讯", "news", $data2_id, 0, "", 0, "", 1, json_encode(["chinese" => "新闻资讯", "chinese_tw" => "新聞資訊", "english" => "News information"]), "", 3]];
	foreach ($data2_son as $k => $v) {
		$_data2[] = array_combine($field, $v);
	}
	\think\Db::name("nav")->insertAll($_data2);
	$data3 = ["其他", "", 0, 0, "", 0, "", 1, json_encode(["chinese" => "其他", "chinese_tw" => "其他", "english" => "other"]), "", 3];
	$data3_id = \think\Db::name("nav")->insertGetId(array_combine($field, $data3));
	$data3_son = [["关于我们", "about", $data3_id, 0, "", 0, "", 1, json_encode(["chinese" => "关于我们", "chinese_tw" => "關於我們", "english" => "About us"]), "", 3], ["服务条款", "tos", $data3_id, 0, "", 0, "", 1, json_encode(["chinese" => "服务条款", "chinese_tw" => "服務條款", "english" => "Terms of service"]), "", 3], ["隐私政策", "privacy", $data3_id, 0, "", 0, "", 1, json_encode(["chinese" => "隐私政策", "chinese_tw" => "隱私政策", "english" => "Privacy policy"]), "", 3]];
	foreach ($data3_son as $k => $v) {
		$_data3[] = array_combine($field, $v);
	}
	\think\Db::name("nav")->insertAll($_data3);
}
/**
 * 更新时创建默认的头部 底部导航
 * @throws \think\Exception
 * @throws \think\exception\PDOException
 */
function createWebDefaultMenu()
{
	$menu = new \app\common\logic\Menu();
	$www_top_id = $menu->addMenu(["name" => "官网头部导航", "menu_type" => "2"]);
	$www_bom_id = $menu->addMenu(["name" => "官网底部导航", "menu_type" => "3"]);
	\think\Db::name("menu_active")->where("id", 2)->update(["menuid" => $www_top_id]);
	\think\Db::name("menu_active")->where("id", 3)->update(["menuid" => $www_bom_id]);
}
/**
 * 会员中心导航，产品 高级设置 默认值
 * @return array|int|string
 */
function setNavSenior()
{
	return (new \app\common\logic\Menu())->setNavSenior();
}
function randpw($len = 8, $format = "ALL")
{
	$is_abc = $is_numer = 0;
	$password = $tmp = "";
	switch ($format) {
		case "ALL":
			$chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
			break;
		case "CHAR":
			$chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
			break;
		case "NUMBER":
			$chars = "0123456789";
			break;
		default:
			$chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
			break;
	}
	mt_srand(floatval(microtime()) * 1000000 * getmypid());
	while (strlen($password) < $len) {
		$tmp = substr($chars, mt_rand() % strlen($chars), 1);
		if ($is_numer != 1 && is_numeric($tmp) && $tmp > 0 || $format == "CHAR") {
			$is_numer = 1;
		}
		if ($is_abc != 1 && preg_match("/[a-zA-Z]/", $tmp) || $format == "NUMBER") {
			$is_abc = 1;
		}
		$password .= $tmp;
	}
	if ($is_numer != 1 || $is_abc != 1 || empty($password)) {
		$password = randpw($len, $format);
	}
	return $password;
}
function certifiBusinessBtn()
{
	$certifi_business_open = configuration("certifi_business_open");
	$certifi_business_btn = 0;
	if ($certifi_business_open) {
		$certifi_business_btn = 1;
	}
	updateconfiguration("certifi_business_btn", $certifi_business_btn);
}
function changeTwoArr($tree, $children = "son")
{
	$imparr = [];
	foreach ($tree as $w) {
		if (isset($w[$children])) {
			$t = $w[$children];
			unset($w[$children]);
			$imparr[] = $w;
			if (is_array($t)) {
				$imparr = array_merge($imparr, changeTwoArr($t, $children));
			}
		} else {
			$imparr[] = $w;
		}
	}
	return $imparr;
}
function toTree($data, $son = "son")
{
	if (empty($data)) {
		return [];
	}
	$_data = array_column($data, null, "id");
	$result = [];
	foreach ($_data as $key => $val) {
		if (isset($_data[$val["pid"]])) {
			$_data[$val["pid"]][$son][] =& $_data[$key];
		} else {
			$result[] =& $_data[$key];
		}
	}
	return $result;
}
function dropSslServer()
{
	$file_path = CMF_ROOT . "public/plugins/servers/ssl/ssl.php";
	$dir_path = CMF_ROOT . "public/plugins/servers/ssl";
	if (file_exists($file_path)) {
		unlink($file_path);
	}
	if (is_dir($dir_path)) {
		rmdir($dir_path);
	}
}
function accessToken_encode()
{
	$key_ary = [true, rand(100000, 999999), time()];
	$j_ = json_encode($key_ary);
	$j_rb_ = randstr(6) . base64_encode($j_);
	$j_rb_oe_ = openssl_encrypt($j_rb_, "AES-128-ECB", "165c3bfef79f4c0ebd6ca6ab49ba42c1", 0, "");
	$j_rb_oe_u_ = urlencode($j_rb_oe_);
	$j_rb_oe_u_r = randstr(5) . $j_rb_oe_u_;
	return $j_rb_oe_u_r;
}
function isbase64($str)
{
	return $str == base64_encode(base64_decode($str)) ? true : false;
}
function code_base64_arr($data, $decode = true)
{
	if ($decode) {
		return array_map("base64_decode", $data);
	}
	return array_map("base64_encode", $data);
}
function editEmailTplCompanName()
{
	$str = "{SYSTEM_COMPANYNAME}[{SYSTEM_COMPANYNAME}]";
	$data = \think\Db::name("email_templates")->cursor();
	foreach ($data as $key => $val) {
		if (strpos($val["message"], $str) === false) {
			continue;
		}
		$val["message"] = str_replace($str, "[{SYSTEM_COMPANYNAME}]", $val["message"]);
		\think\Db::name("email_templates")->where("id", $val["id"])->update(["message" => $val["message"]]);
	}
	return true;
}
function emailSmtpInstall()
{
	$pluginName = "Smtp";
	$type_dir = $module = "mail";
	$class = cmf_get_plugin_class_shd($pluginName, $type_dir);
	if (!class_exists($class)) {
		return false;
	}
	$pluginModel = new \app\admin\model\PluginModel();
	$pluginCount = $pluginModel->where("name", $pluginName)->where("module", $type_dir)->count();
	if ($pluginCount > 0) {
		return false;
	}
	$plugin = new $class();
	$info = $plugin->info;
	if (!$info || !$plugin->checkInfo()) {
		return false;
	}
	$info["module"] = $info["module"] ?: $module;
	$installSuccess = $plugin->install();
	if (!$installSuccess) {
		return false;
	}
	$methods = get_class_methods($plugin);
	foreach ($methods as $methodKey => $method) {
		$methods[$methodKey] = cmf_parse_name($method);
	}
	updateconfiguration("email_operator", strtolower($pluginName));
	$systemHooks = $pluginModel->getHooks(true);
	$pluginHooks = array_intersect($systemHooks, $methods);
	$info["hooks"] = implode(",", $pluginHooks);
	if (!empty($plugin->hasAdmin)) {
		$info["has_admin"] = 1;
	} else {
		$info["has_admin"] = 0;
	}
	$info["config"] = $plugin->getConfig();
	$info["config"]["charset"] = configuration("email_charset");
	$info["config"]["port"] = configuration("email_port");
	$info["config"]["host"] = configuration("email_host");
	$info["config"]["username"] = configuration("email_username");
	$info["config"]["password"] = aespassworddecode(configuration("email_password"));
	$info["config"]["smtpsecure"] = configuration("email_smtpsecure");
	$info["config"]["fromname"] = configuration("email_fromname");
	$info["config"]["systememail"] = configuration("email_systememail");
	$info["config"] = json_encode($info["config"]);
	$info["status"] = configuration("shd_allow_email_send");
	$pluginModel->data($info)->allowField(true)->save();
	updateconfiguration("shd_allow_email_send", 1);
	foreach ($pluginHooks as $pluginHook) {
		\think\Db::name("hook_plugin")->insert(["hook" => $pluginHook, "plugin" => $pluginName, "status" => 1, "module" => $module]);
	}
	\think\facade\Cache::clear("init_hook_plugins");
	\think\facade\Cache::clear("init_hook_plugins_system_hook_plugins");
	$menuClientarea = $pluginModel->menuClientarea($pluginName, $module);
	if (!$menuClientarea) {
		return false;
	}
	return true;
}
function getDomain()
{
	return request()->queue_domain ?? request()->domain();
}
function getRootUrl()
{
	return request()->queue_rootUrl ?? request()->rootUrl();
}
function zjmf_private_encrypt($originalData, $private_key)
{
	$crypted = "";
	foreach (str_split($originalData, 117) as $chunk) {
		openssl_private_encrypt($chunk, $encryptData, $private_key);
		$crypted .= $encryptData;
	}
	return base64_encode($crypted);
}
function getPort()
{
	return $_SERVER["SERVER_PORT"];
}
function getUserAgent()
{
	return $_SERVER["HTTP_USER_AGENT"];
}
function getOS()
{
	$os = "";
	$Agent = $_SERVER["HTTP_USER_AGENT"];
	if (preg_match("/win/i", $Agent) && strpos($Agent, "95")) {
		$os = "Windows 95";
	} elseif (preg_match("/win 9x/i", $Agent) && strpos($Agent, "4.90")) {
		$os = "Windows ME";
	} elseif (preg_match("/win/i", $Agent) && preg_match("/98/", $Agent)) {
		$os = "Windows 98";
	} elseif (preg_match("/win/i", $Agent) && preg_match("/nt 5.0/i", $Agent)) {
		$os = "Windows 2000";
	} elseif (preg_match("/win/i", $Agent) && preg_match("/nt 6.0/i", $Agent)) {
		$os = "Windows Vista";
	} elseif (preg_match("/win/i", $Agent) && preg_match("/nt 6.1/i", $Agent)) {
		$os = "Windows 7";
	} elseif (preg_match("/win/i", $Agent) && preg_match("/nt 5.1/i", $Agent)) {
		$os = "Windows XP";
	} elseif (preg_match("/win/i", $Agent) && preg_match("/nt/i", $Agent)) {
		$os = "Windows NT";
	} elseif (preg_match("/win/i", $Agent) && preg_match("/32/", $Agent)) {
		$os = "Windows 32";
	} elseif (preg_match("/linux/i", $Agent)) {
		$os = "Linux";
	} elseif (preg_match("/unix/i", $Agent)) {
		$os = "Unix";
	} elseif (preg_match("/sun/i", $Agent) && preg_match("/os/i", $Agent)) {
		$os = "SunOS";
	} elseif (preg_match("/ibm/i", $Agent) && preg_match("/os/i", $Agent)) {
		$os = "IBM OS/2";
	} elseif (preg_match("/Mac/i", $Agent) && preg_match("/PC/i", $Agent)) {
		$os = "Macintosh";
	} elseif (preg_match("/PowerPC/i", $Agent)) {
		$os = "PowerPC";
	} elseif (preg_match("/AIX/i", $Agent)) {
		$os = "AIX";
	} elseif (preg_match("/HPUX/i", $Agent)) {
		$os = "HPUX";
	} elseif (preg_match("/NetBSD/i", $Agent)) {
		$os = "NetBSD";
	} elseif (preg_match("/BSD/i", $Agent)) {
		$os = "BSD";
	} elseif (preg_match("/OSF1/", $Agent)) {
		$os = "OSF1";
	} elseif (preg_match("/IRIX/", $Agent)) {
		$os = "IRIX";
	} elseif (preg_match("/FreeBSD", $Agent)) {
		$os = "FreeBSD";
	} elseif ($os == "") {
		$os = "Unknown";
	}
	return $os;
}
/**
 * 替换邮件模板中查看订单详情
 * @return mixed
 */
function editEmailTplNullLink()
{
	return \think\Db::transaction(function () {
		$str = "<span style=\"margin: 0; padding: 0; display: inline-block; margin-top: 55px;\">查看订单详情：<span style=\"color: blue;\">链接</span></span>";
		$str = htmlspecialchars($str);
		$data = \think\Db::name("email_templates")->whereLike("message", "%" . $str . "%")->cursor();
		foreach ($data as $key => $val) {
			if (strpos($val["message"], $str) === false) {
				continue;
			}
			$val["message"] = str_replace($str, "", $val["message"]);
			\think\Db::name("email_templates")->where("id", $val["id"])->update(["message" => $val["message"]]);
		}
		return true;
	});
}
function smsReplaceParam()
{
	$template = \think\Db::name("message_template_link")->field("mt.*")->alias("mtl")->leftJoin("message_template mt", "mt.id = mtl.sms_temp_id")->where("mtl.type", 8)->select()->toArray();
	foreach ($template as $v) {
		if (strpos($v["content"], "username") !== false) {
			$v["content"] = substr($v["content"], strpos($v["content"], "，") + 3);
			$type = $v["sms_operator"];
			$sms["config"] = pluginconfig(ucfirst($type), "sms");
			$sms["template_id"] = $v["template_id"];
			$sms["title"] = $v["title"];
			$sms["content"] = $v["content"];
			$sms["remark"] = $v["remark"];
			$param["content"] = $v["content"];
			if (!empty($v["template_id"]) && $v["status"] == 2) {
				if ($v["range_type"] == 0) {
					$putTemplate = "putCnTemplate";
				} elseif ($v["range_type"] == 1) {
					$putTemplate = "putGlobalTemplate";
				}
				$result = zjmfhook(ucfirst($type), "sms", $sms, $putTemplate);
				$param["status"] = 2;
				$res = \think\Db::name("message_template")->where("id", $v["id"])->update($param);
			} else {
				$res = \think\Db::name("message_template")->where("id", $v["id"])->update($param);
			}
		}
	}
}
function gateway_list_openapi($module = "gateways", $status = 1)
{
	if ($status != 1) {
		$where = ["module" => "gateways"];
	} else {
		$where = ["status" => $status, "module" => "gateways"];
	}
	$rows = \think\Db::name("plugin")->field("id,name,title,url,author_url")->where($where)->withAttr("url", function ($value, $data) {
		return "upload/pay/" . $data["name"] . ".png";
	})->withAttr("author_url", function ($value, $data) {
		if (file_exists(CMF_ROOT . "modules/gateways/" . cmf_parse_name($data["name"], 0) . "/" . $data["name"] . ".png")) {
			return base64encodeimage(CMF_ROOT . "modules/gateways/" . cmf_parse_name($data["name"], 0) . "/" . $data["name"] . ".png");
		} else {
			return base64encodeimage(CMF_ROOT . "public/plugins/gateways/" . cmf_parse_name($data["name"], 0) . "/" . $data["name"] . ".png");
		}
	})->order("order", "asc")->order("id", "asc")->select()->toArray();
	return $rows;
}
function password_encrypt($password)
{
    $key = config("app.aes.key");
    $iv = config("app.aes.iv");
    $data = openssl_encrypt($password, "AES-128-CBC", $key, OPENSSL_RAW_DATA, $iv);
    $data = base64_encode($data);
    return $data;
}
function password_decrypt($password)
{
    $default = ["default"];
    $allow = explode(",", configuration("allow_new_login_template") ?? "");
    $use = configuration("clientarea_default_themes") ?? "default";
    $allow = array_merge($default, $allow);
    if (!in_array($use, $allow)) {
        return $password;
    }
    $key = config("app.aes.key");
    $iv = config("app.aes.iv");
    $encrypted = base64_decode($password);
    $plainText = openssl_decrypt($encrypted, "AES-128-CBC", $key, OPENSSL_RAW_DATA, $iv);
    return $plainText;
}