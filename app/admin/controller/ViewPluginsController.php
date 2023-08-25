<?php

namespace app\admin\controller;

class ViewPluginsController extends ViewAdminBaseController
{
	const ACTIVE_MENU = "插件";
	public function index(\think\Request $request)
	{
		$data["Title"] = "插件";
		$request->moduleName = "addons";
		$plugin = controller("Plugin");
		$plugin_data = $plugin->plIndex($request);
		$param = $request->param();
		$page = !empty($param["page"]) ? intval($param["page"]) : 1;
		$limit = !empty($param["limit"]) ? intval($param["limit"]) : 50;
		$plugins = $plugin_data["data"];
		$plugins = array_filter($plugins, function ($v) use($param) {
			if (!empty($param["keywords"])) {
				$str = $v->name . " " . $v->title . " " . $v->author . " " . $v->description;
				if (strstr($str, $param["keywords"]) !== false) {
					return true;
				}
				return false;
			} else {
				return true;
			}
		});
		$count = count($plugins);
		request()->setAction("plugins");
		$data["Plugins"] = array_slice($plugins, ($page - 1) * $limit, $limit);
		$data["topMenu"] = $this->getTopMenu();
		$data["svg_menu"] = svgBase64EncodeImage(request()->domain() . "/" . config("database.admin_application") . "/themes/default/assets/images/arrow.svg");
		$languagesys = request()->languagesys;
		$status = ["chinese" => [1 => "开启", 0 => "禁用", 3 => "未安装"], "english" => [1 => "Open", 0 => "Disabled", 3 => "Uninstall"], "chinese_tw" => [1 => "開啟", 0 => "禁用", 3 => "未安裝"]];
		if ($languagesys == "CN") {
			$lang = "chinese";
		} elseif ($languagesys == "US") {
			$lang = "english";
		} elseif ($languagesys == "HK") {
			$lang = "chinese_tw";
		}
		$data["status"] = $status[$lang];
		$data["pageInfo"] = ["count" => $count, "limit" => $limit, "pages" => $this->ajaxPages(array_slice($plugins, ($page - 1) * $limit, $limit), $limit, $page, $count)];
		return $this->view("plugins", $data);
	}
	private function getTopMenu()
	{
		$fix_url = request()->domain() . "/" . adminAddress();
		$lang = request()->languagesys;
		if (empty($lang)) {
			$lang = configuration("language") ? configuration("language") : config("default_lang");
		}
		$menu = \think\Db::name("auth_rule")->where("pid", 0)->where("status", 1)->where("is_display", 1)->limit(11)->order("order")->select()->toArray();
		$fun = function ($v) use($fix_url, $lang) {
			$v["is_active"] = 0;
			$title = json_decode($v["language_map"], true)[$lang] ?: $v["title"];
			if ($v["title"] == self::ACTIVE_MENU) {
				$v["title"] = $title;
				$v["is_active"] = 1;
				return $v;
			}
			$v["url"] = $fix_url . "/#" . $v["url"];
			$v["title"] = $title;
			return $v;
		};
		foreach ($menu as &$v1) {
			if (!in_array($v1["id"], [1, 2, 3, 4])) {
				$tmps = \think\Db::name("auth_rule")->where("pid", $v1["id"])->where("is_display", 1)->where("status", 1)->order("order")->select()->toArray();
				$v1["child"] = array_map($fun, $tmps);
			}
		}
		return array_map($fun, $menu);
	}
}