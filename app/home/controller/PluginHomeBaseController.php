<?php

namespace app\home\controller;

define("VIEW_TEMPLATE_DIRECTORY", "clientarea");
define("VIEW_TEMPLATE_HOME_PLUGINS", true);
define("VIEW_TEMPLATE_WEBSITE", true);
define("VIEW_TEMPLATE_RETURN_ARRAY", true);
define("VIEW_TEMPLATE_SUFFIX", "tpl");
define("VIEW_TEMPLATE_SETTING_NAME", "clientarea_default_themes");
class PluginHomeBaseController extends \app\admin\controller\PluginBaseController
{
	protected $ViewModel;
	protected function fetch($template = "", $vars = [], $replace = [], $config = [])
	{
		sessionInit();
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
		$this->ViewModel = new \app\home\model\ViewModel();
		if (empty($uid)) {
			header("location:{$this->ViewModel->domain}/login");
			exit;
		}
		$template = $tplName = "/clientarea" . $template;
		$setting["Setting"] = $this->ViewModel->setting();
		$User = new UserController();
		$data["Userinfo"] = $User->index(request());
		if (count($data["Userinfo"]["second_verify_action_home"]) > 0) {
			$getSecondVerifyPage = $User->getSecondVerifyPage();
			$data["AllowType"] = $getSecondVerifyPage["data"]["allow_type"];
		} else {
			$data["AllowType"] = [];
		}
		$verify["Verify"] = $this->ViewModel->verify();
		$data["ShowBreadcrumb"] = $data["Breadcrumb"] ? true : false;
		$_LANG = [];
		$lang = get_lang("all");
		$language = load_lang($lang);
		include CMF_ROOT . "public/language/" . $language . ".php";
		$theme_name = configuration(VIEW_TEMPLATE_SETTING_NAME);
		if (!$theme_name) {
			$theme_name = "default";
		}
		$custom_lang = get_custom_lang("clientarea", $theme_name, "all");
		$lang = array_merge($lang, $custom_lang);
		if (!empty($custom_lang)) {
			$custom_language = customload_lang($custom_lang);
			if (file_exists(CMF_ROOT . "public/themes/clientarea/" . $theme_name . "/language/" . $custom_language . ".php")) {
				include CMF_ROOT . "public/themes/clientarea/" . $theme_name . "/language/" . $custom_language . ".php";
			}
		}
		$pluginName = $this->request->param("_plugin");
		if ($custom_language == "chinese") {
			$plugin_lang = "zh-cn";
		} elseif ($custom_language == "english") {
			$plugin_lang = "en-us";
		} elseif ($custom_language == "chinese_tw") {
			$plugin_lang = "zh-tw";
		}
		$langFromPlugin = [];
		if (file_exists(CMF_ROOT . "public/plugins/" . $this->module . "/{$pluginName}/lang/{$plugin_lang}.php")) {
			$langFromPlugin = (include CMF_ROOT . "public/plugins/" . $this->module . "/{$pluginName}/lang/{$plugin_lang}.php");
		}
		$_LANG = array_merge($_LANG, $langFromPlugin);
		$data["Lang"] = $_LANG;
		$data["LanguageCheck"] = $lang[$language];
		$data["Language"] = $lang;
		$data["Ver"] = md5(configuration("beta_version"));
		$data["TplName"] = $tplName;
		$data["RouteName"] = $tplName;
		$data["Date"] = date("Y-m-d H:i:s");
		$data["Token"] = md5(time());
		$data["Get"] = request()->get();
		$post = request()->post();
		unset($post["token"]);
		$data["Nav"] = (new \app\common\logic\Menu())->getNavs("client", $setting["Setting"]["web_url"], $language);
		$data = array_merge($verify, $data);
		$data = array_merge($setting, $data);
		$data = array_merge($vars, $data);
		$_SESSION["view_tpl_data"] = $data;
		$_SESSION["paramsData"] = [];
		$this->assign($data);
		$template = parent::fetch($template, $vars, $replace, $config);
		if (!(new ViewBaseController())->zjmf_authorize() && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) != "xmlhttprequest") {
			$template = $template . "<a style=\"position: absolute;right: 10px;bottom: 20px;color:#555;z-index:9999;display: block!important;\" href=\"https://www.idcsmart.com\" target=\"_blank\"> Powered by &copy;智简魔方</a></body>";
		}
		return $template;
	}
	protected function initialize()
	{
	}
}