<?php

namespace app\admin\controller;

define("VIEW_TEMPLATE_ADMIN", true);
define("VIEW_TEMPLATE_RETURN_ARRAY", true);
define("VIEW_TEMPLATE_SETTING_NAME", "admin_default_theme");
define("VIEW_TEMPLATE_DEFAULT", "default");
define("VIEW_TEMPLATE_SUFFIX", "tpl");
class ViewAdminBaseController extends \cmf\controller\BaseController
{
	public function __construct()
	{
		sessionInit();
	}
	protected function adminlogout()
	{
		$Public = controller("Public");
		$Public->ad_logout();
		header("location:/admin/login");
		exit;
	}
	protected function viewOutData($view_tpl_file, $paramsData = [])
	{
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
		foreach ($tagArray as $v) {
			if ($paramsData[trim($v)]) {
				$tagDataArray[trim($v)] = $paramsData[trim($v)];
			}
		}
		return $tagDataArray;
	}
	protected function ajaxPages($showdata = [], $listRow = 10, $curpage = 1, $total = 0)
	{
		$url = "/" . adminAddress() . "/" . request()->action();
		$p = \think\paginator\driver\Bootstrap::make($showdata, $listRow, $curpage, $total, false, ["var_page" => "page", "path" => $url, "fragment" => "", "query" => $_GET]);
		$pages = $p->render();
		$default_pages = "<li class=\"page-item disabled\"><a class=\"page-link\" href=\"#\">&laquo;</a></li>
	<li class=\"page-item active\"><a class=\"page-link\" href=\"#\">1</a></li>
	<li class=\"page-item disabled\"><a class=\"page-link\" href=\"#\">&raquo;</a></li>";
		$pages = !empty($pages) ? $pages : $default_pages;
		return $pages;
	}
	protected function view($tplName, $data = [], $config = [])
	{
		$view_tpl_file = view_tpl_file($tplName);
		$tagDataArray = $this->viewOutData($view_tpl_file);
		$lang = request()->languagesys;
		if (empty($lang)) {
			$lang = configuration("language") ? configuration("language") : config("default_lang");
		}
		if ($lang == "CN") {
			$lang = "chinese";
		} elseif ($lang == "US") {
			$lang = "english";
		} elseif ($lang == "HK") {
			$lang = "chinese_tw";
		}
		include CMF_ROOT . "public/language/" . $lang . ".php";
		$data["ShowBreadcrumb"] = $data["Breadcrumb"] ? true : false;
		$data["Lang"] = $_LANG;
		$data["TplName"] = $tplName;
		$data["RouteName"] = $tplName;
		$data["Date"] = date("Y-m-d H:i:s");
		$data["Token"] = md5(time());
		$data["Get"] = $_GET;
		$data["Themes"] = cmf_get_root() . "/" . adminAddress() . "/themes/" . adminTheme();
		$data["Admin"] = adminAddress();
		$data["Weburl"] = request()->domain();
		$data["PluginsMenu"] = (new \app\admin\model\PluginModel())->getPluginsMeun("addons", $lang);
		$data["Addons"] = "PluginsList";
		$data["PluginUrl"] = request()->url();
		$_SESSION["view_tpl_data"] = $data;
		$_SESSION["paramsData"] = [];
		$tplName = !empty($tplName) ? $tplName : request()->action();
		$view = new \think\View();
		$view->init("Think");
		return $view->fetch($tplName, $data, $config);
	}
}