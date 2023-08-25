<?php

namespace app\home\controller;

define("VIEW_TEMPLATE_DIRECTORY", "web");
define("VIEW_TEMPLATE_WEBSITE", true);
define("VIEW_TEMPLATE_RETURN_ARRAY", true);
define("VIEW_TEMPLATE_SETTING_NAME", "themes_templates");
define("VIEW_TEMPLATE_DEFAULT", "clientareaonly");
define("VIEW_TEMPLATE_SUFFIX", "html");
class ViewController extends ViewBaseController
{
	public function index(\think\Request $request)
	{
		$param = $request->param();
		if ($request->html == "userlogout") {
			$this->userlogout();
		}
		if (empty($request->html)) {
			$request->html = "index";
		} else {
			if (!empty($request->html3)) {
				$fileHtml3 = $request->html . "/" . $request->html2 . "/" . $request->html3;
				$view_tpl_file = view_tpl_file($fileHtml3);
				if (!empty($view_tpl_file)) {
					$request->html = $fileHtml3;
				}
			} else {
				if (!empty($request->html2)) {
					$fileHtml2 = $request->html . "/" . $request->html2;
					$view_tpl_file = view_tpl_file($fileHtml2);
					if (!empty($view_tpl_file)) {
						$request->html = $fileHtml2;
					}
				}
			}
		}
		if (empty($view_tpl_file)) {
			$view_tpl_file = view_tpl_file($request->html);
			if (empty($view_tpl_file)) {
				header("location:{$this->ViewModel->domain}/clientarea");
				exit;
			}
			if ($request->html2 && !is_numeric($request->html2) || $request->html3 && !is_numeric($request->html3)) {
				return $this->webview("404", ["setting" => $this->ViewModel->setting()]);
			}
			if ($param["page"] && !is_numeric($param["page"])) {
				return $this->webview("404", ["setting" => $this->ViewModel->setting()]);
			}
		}
		$paramsDefaultData = ["setting" => "网站设置数据（默认输出）", "userInfo" => "登录用户信息（默认输出，为空是未登录）"];
		$paramsData = ["newsCate" => "获取新闻分类（无参数）", "newsList" => "获取新闻列表（参数格式newsList[cid:1|cid:2|num:5|order:desc]cid是分类ID，num分页数据条数，order排序desc是降序asc升序）", "newsListCate" => "获取新闻分类列表（无参数）", "newsSearch" => "获取新闻搜索（无参数）", "newsContent" => "获取新闻内容（无参数）", "helpCate" => "获取帮助分类（无参数）", "helpList" => "获取帮助列表（参数格式helpList[cid:1|cid:2|num:5|order:desc]cid是分类ID，num分页数据条数，order排序desc是降序asc升序）", "helpListCate" => "获取帮助分类列表（无参数）", "helpSearch" => "获取帮助搜索（无参数）", "helpContent" => "获取帮助内容（无参数）", "firstGroups" => "产品一级分类（参数格式firstGroups[fid:1|fid:2]输出ID=1和ID=2的一级分类，firstGroups输出所有一级分类）", "secondGroups" => "产品二级分类（参数格式secondGroups[sid:1|sid:2]输出ID=1和ID=2的二级分类，secondGroups输出所有二级分类）", "product" => "获取产品（参数格式product[pid:1|pid:3]输出ID=1和ID=3的产品,参数不能为空", "productGroups" => "获取二级分类ID下产品（参数格式productGroups[sid:1]输出产品分类ID=1关联的产品,参数不能为空"];
		$paramsData = array_merge($paramsDefaultData, $paramsData);
		$tagDataArray = $this->viewOutData($view_tpl_file, $paramsData);
		$tagDataArray = array_merge($paramsDefaultData, $tagDataArray);
		$paramsData404 = ["newsList", "newsListCate", "newsSearch", "helpList", "helpListCate", "helpSearch"];
		$paramsData404 = array_flip($paramsData404);
		$r404 = 0;
		foreach ($tagDataArray as $tagDataKey => $tagDataVal) {
			$return[$tagDataKey] = $this->ViewModel->{$tagDataKey}($param, $tagDataVal);
			if ($tagDataKey == "newsContent" || $tagDataKey == "helpContent") {
				if (empty($return[$tagDataKey])) {
					$r404++;
				}
			} else {
				if (isset($paramsData404[$tagDataKey])) {
					if ($return[$tagDataKey]["total_page"] && $param["page"] > $return[$tagDataKey]["total_page"]) {
						$r404++;
					}
					if (empty($return[$tagDataKey]["page"])) {
						$r404++;
					}
				}
			}
		}
		if ($r404 > 0) {
			return $this->webview("404", ["setting" => $this->ViewModel->setting()]);
		}
		if ($return["userInfo"]) {
			$return["username"] = $return["userInfo"]["username"];
			$return["email"] = $return["userInfo"]["email"];
			$return["phonenumber"] = $return["userInfo"]["phonenumber"];
			$return["accounts"] = \think\Db::name("accounts")->field("amount_in,amount_out,fees")->where("uid", $return["userInfo"]["id"])->sum("amount_in");
			$aff_data = \think\Db::name("affiliates")->alias("a")->join("clients c", "c.id=a.uid")->leftJoin("currencies cu", "cu.id = c.currency")->field("a.*,cu.suffix,cu.prefix")->where("uid", $return["userInfo"]["id"])->find();
			$return["aff"] = ["affStatus" => $aff_data ? 1 : 0, "affNum" => $aff_data ? $aff_data["visitors"] : 0];
		}
		$return["viewName"] = $request->html;
		$_SESSION["paramsData"] = $paramsData;
		return $this->webview($request->html, $return);
	}
	public function webview($tplName, $data)
	{
		$view = new \think\View();
		$view->init("Think");
		$_LANG = [];
		$custom_language = "";
		$theme_name = configuration(VIEW_TEMPLATE_SETTING_NAME);
		if (!$theme_name) {
			$theme_name = VIEW_TEMPLATE_DEFAULT;
		}
		$custom_lang = get_custom_lang("web", $theme_name, "all");
		if (!empty($custom_lang)) {
			$custom_language = customload_lang($custom_lang);
			if (file_exists(CMF_ROOT . "public/themes/web/" . $theme_name . "/language/" . $custom_language . ".php")) {
				include CMF_ROOT . "public/themes/web/" . $theme_name . "/language/" . $custom_language . ".php";
			}
		}
		$menu_model = new \app\common\logic\Menu();
		$data["www_top"] = $menu_model->getWebNav("www_top", "", $custom_language, false);
		$data["www_bottom"] = $menu_model->getWebNav("www_bottom", "", $custom_language, false);
		$data["f_links"] = \think\Db::name("friendly_links")->where("is_open", 1)->select()->toArray();
		$data["Lang"] = $_LANG;
		$data["CustomDepot"] = $this->ViewModel->getDepot();
		$_SESSION["view_tpl_data"] = $data;
		if (!configuration("is_themes")) {
			header("location:{$this->ViewModel->domain}/clientarea");
			exit;
		}
		return $view->fetch($tplName, $data);
	}
}