<?php

namespace app\admin\controller;

class PluginAdminBaseController extends PluginBaseController
{
	const ACTIVE_MENU = "插件";
	protected function fetch($template = "", $vars = [], $replace = [], $config = [])
	{
		$template = "/admin" . $template;
		$vars["topMenu"] = $this->getTopMenu();
		$vars["svg_menu"] = svgBase64EncodeImage(request()->domain() . "/" . config("database.admin_application") . "/themes/default/assets/images/arrow.svg");
		return parent::fetch($template, $vars, $replace, $config);
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
	/**
	 *  检查后台用户访问权限
	 * @param int $userId 后台用户id
	 * @return boolean 检查通过返回true
	 */
	private function getAuthname($userId)
	{
		$param = $this->request->param();
		$module = $param["_plugin"];
		$controller = $param["_controller"];
		$action = $param["_action"];
		$rule = "addons\\" . $module . "\\controller\\" . $controller . "controller::" . $action;
		$auth = \think\Db::name("auth_rule")->where("name", $rule)->order("id", "DESC")->find();
		if (!isset($auth["id"])) {
			return $rule;
		} else {
			return $auth["title"];
		}
	}
	protected function initialize()
	{
		if (request()->isPost() && !request()->isAjax()) {
			$param = request()->param();
			$param = md5(json_encode($param));
			if ($param == session("post")) {
				if ($_SERVER["HTTP_REFERER"]) {
					$refererUrl = $_SERVER["HTTP_REFERER"];
				} else {
					$refererUrl = request()->domain() . "/" . adminAddress() . "/plugins";
				}
				header("location:" . $refererUrl);
				exit;
			}
			session("post", $param);
		}
		$name = $this->request->_plugin;
		$plugin_model = new \app\admin\model\PluginModel();
		$PluginsAdminMenu = $plugin_model->getPluginsAdminMenu($name);
		$this->assign("PluginsAdminMenu", $PluginsAdminMenu);
		$lang = $_GET["languagesys"];
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
		$PluginsMenu = $plugin_model->getPluginsMeun("addons", $lang);
		$this->assign("PluginsMenu", $PluginsMenu);
		$this->assign("Themes", cmf_get_root() . "/" . adminAddress() . "/themes/default");
		$this->assign("Admin", adminAddress());
		$plugin = cmf_parse_name($name, 1);
		$this->assign("Addons", $plugin ?: "PluginsList");
		$this->assign("PluginUrl", request()->url());
		$tmp = \think\Db::name("plugin")->where("name", $plugin)->where("status", 1)->find();
		if (empty($tmp)) {
			echo json_encode(["status" => 400, "msg" => "插件不可用"]);
			exit;
		}
		$adminId = cmf_get_current_admin_id();
		if (!empty($adminId)) {
			if (!$this->checkAccess($adminId)) {
				$name = $this->getAuthname($adminId);
				$auth_id = session("AUTH_IDS_" . $adminId);
				$auth_role_id = session("AUTH_ROLE_IDS_" . $adminId);
				$data["rule"] = json_decode($auth_id);
				$data["auth"] = $auth_role_id;
				echo json_encode(["status" => 401, "msg" => "您没有访问" . $name . "页面权限！", "name" => $name, "rule" => $data["rule"], "auth" => $data["auth"], "identify" => sub_strs(request()->url())]);
				exit;
				$this->error("您没有访问权限！");
			}
		} else {
			if ($this->request->isAjax()) {
				echo json_encode(["status" => 405, "msg" => "您还没有登录"]);
				exit;
				$this->error("您还没有登录！", url("admin/Public/login"));
			} else {
				echo json_encode(["status" => 405, "msg" => "您还没有登录"]);
				exit;
				header("Location:" . url("admin/Public/login"));
				exit;
			}
		}
	}
	/**
	 *  检查后台用户访问权限
	 * @param int $userId 后台用户id
	 * @return boolean 检查通过返回true
	 */
	private function checkAccess($userId)
	{
		if ($userId == 1) {
			return true;
		}
		$pluginName = $this->request->param("_plugin");
		$controller = $this->request->param("_controller");
		$controller = cmf_parse_name($controller, 1);
		$action = $this->request->param("_action");
		return cmf_auth_check($userId, "addons/{$pluginName}/{$controller}/{$action}");
	}
}