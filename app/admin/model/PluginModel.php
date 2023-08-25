<?php

namespace app\admin\model;

class PluginModel extends \think\Model
{
	public function __construct($data = [])
	{
		parent::__construct($data);
	}
	public function customInit()
	{
		$ids = \think\Db::name("plugin")->column("id");
		foreach ($ids as $id) {
			\think\Db::name("plugin")->where("id", $id)->update(["order" => $id]);
		}
		$max_order = array_pop($ids);
		\think\Db::name("plugin")->where("module", "gateways")->where("name", "UserCustom")->update(["order" => $max_order + 1]);
	}
	/**
	 * 获取插件列表
	 */
	public function getList($type = "", $lang = "chinese")
	{
		if (!in_array($type, ["gateways", "addons", "certification", "firewall", "oauth", "sms", "automatic", "mail"])) {
			return false;
		}
		if ($type == "gateways") {
			$dirs_gateways1 = array_map("basename", glob(CMF_ROOT . "modules/{$type}/*", GLOB_ONLYDIR));
			$dirs_gateways2 = array_map("basename", glob(WEB_ROOT . "plugins/{$type}/*", GLOB_ONLYDIR));
			$dirs = array_merge($dirs_gateways1, $dirs_gateways2);
		} else {
			$dirs = array_map("basename", glob(WEB_ROOT . "plugins/{$type}/*", GLOB_ONLYDIR));
		}
		if ($dirs === false) {
			return false;
		}
		$plugins = [];
		if (empty($dirs)) {
			return $plugins;
		}
		$where = [];
		if (!empty($type)) {
			$where["module"] = $type;
		}
		$list = $this->where($where)->order("order", "asc")->order("id", "asc")->select();
		foreach ($list as $plugin) {
			$plugins[$plugin["name"]] = $plugin;
		}
		$type_dir = config("plugins_dir")[$type];
		foreach ($dirs as $k => $dir) {
			$pluginDir = cmf_parse_name($dir, 1);
			if (!isset($plugins[$pluginDir])) {
				$class = cmf_get_plugin_class_shd($pluginDir, $type_dir);
				if (!class_exists($class)) {
					unset($dirs[$k]);
					continue;
				}
				$obj = new $class();
				$plugins[$pluginDir] = $obj->info;
				if (!isset($obj->info["type"]) || $obj->info["type"] == 1) {
					if ($plugins[$pluginDir]) {
						$plugins[$pluginDir]["status"] = 3;
					}
				} else {
					unset($plugins[$pluginDir]);
				}
			}
		}
		$languagesys = request()->languagesys ?: "CN";
		if ($type == "addons") {
			foreach ($plugins as $k => &$v) {
				$class = cmf_get_plugin_class_shd($v["name"], $type_dir);
				if (!class_exists($class)) {
					unset($plugins[$k]);
					continue;
				}
				$obj = new $class();
				if (isset($obj->info["lang"]) && is_array($obj->info)) {
					$v["title"] = $obj->info["lang"][$lang];
				}
				$v["menu"] = "";
				if ($v["status"] == 1) {
					$name = cmf_parse_name($v["name"], 0);
					if (file_exists(WEB_ROOT . "plugins/{$type}/{$name}/menu.php")) {
						$return = (require WEB_ROOT . "plugins/{$type}/{$name}/menu.php");
						$menu = "";
						foreach ($return as $item) {
							if (isset($item["lang"]) && is_array($item["lang"])) {
								$item["name"] = $item["lang"][$lang];
							}
							if (empty($item["custom"])) {
								$url = shd_addon_url($item["url"]);
								$menu = $menu . " " . "<a href='{$url}&languagesys={$languagesys}'>{$item["name"]}</a>";
							} else {
								$url = $item["url"];
								$menu = $menu . " " . "<a href='{$url}' target='_blank'>{$item["name"]}</a>";
							}
						}
						$v["menu"] = $menu;
					}
				}
			}
		} elseif ($type == "certification") {
			foreach ($plugins as $k => &$v) {
				$class = cmf_get_plugin_class_shd($k, $type_dir);
				if (!class_exists($class)) {
					unset($plugins[$k]);
					continue;
				}
				$methods = get_class_methods($class) ?: [];
				$type = [];
				if (in_array("personal", $methods)) {
					$type[] = ["name" => "personal", "name_zh" => "个人"];
				}
				if (in_array("company", $methods)) {
					$type[] = ["name" => "company", "name_zh" => "企业"];
				}
				$v["certifi_type"] = $type;
			}
		} elseif ($type == "sms") {
			foreach ($plugins as $k => &$v) {
				$class = cmf_get_plugin_class_shd($k, $type_dir);
				if (!class_exists($class)) {
					unset($plugins[$k]);
					continue;
				}
				$methods = get_class_methods($class) ?: [];
				$type = [];
				foreach ($methods as $m) {
					if (strpos($m, "GlobalTemplate") !== false || $m == "sendGlobalSms") {
						$type["global"] = ["name" => "global", "name_zh" => "国际"];
					} else {
						if (strpos($m, "CnTemplate") !== false || $m == "sendCnSms") {
							$type["cn"] = ["name" => "cn", "name_zh" => "国内"];
						} else {
							if (strpos($m, "CnProTemplate") !== false || $m == "sendCnProSms") {
								$type["cnpro"] = ["name" => "cnpro", "name_zh" => "营销"];
							}
						}
					}
				}
				$v["sms_type"] = array_values($type);
			}
		} elseif ($type == "mail") {
		}
		try {
			$result_data = $this->getNewVersion();
		} catch (\Throwable $e) {
			$result_data = [];
		}
		foreach ($plugins as $k => &$v) {
			$class = cmf_get_plugin_class_shd($k, $type_dir);
			if (!class_exists($class)) {
				unset($plugins[$k]);
				continue;
			}
		}
		foreach ($plugins as $key => &$val) {
			$val->update_btn = 0;
			$val->update_disable = 1;
			$val->app_id = 0;
			$val->app_version = "";
			$val->version = $val->version ?: "1.0.0";
			if (!isset($result_data[$key])) {
				continue;
			}
			$val->app_version = $result_data[$key]["app_version"];
			$val->version = $result_data[$key]["old_version"] ?: "1.0.0";
			$val->app_id = $result_data[$key]["id"];
			if (version_compare($result_data[$key]["app_version"], $result_data[$key]["old_version"], ">")) {
				$val->update_btn = 1;
			}
			if ($result_data[$key]["nextduedate"] == 0) {
				$val->update_disable = 0;
				continue;
			}
			if ($result_data[$key]["nextduedate"] > time()) {
				$val->update_disable = 0;
			}
		}
		return $plugins;
	}
	public function getNewVersion()
	{
		$result = (new \app\admin\controller\AppStoreController())->getNewVersion();
		if (is_object($result)) {
			$result = $result->getData();
		}
		$result_data = [];
		if ($result["status"] != 200) {
			throw new \think\Exception($result_data["msg"]);
		}
		$result_data = $result["data"] ? array_column($result["data"], null, "uuid") : [];
		return $result_data;
	}
	public function checkPlInstall($uuid)
	{
		$result = (new \app\admin\controller\AppStoreController())->install($uuid);
		$result = $result->getData();
		if ($result["status"] != 200) {
			throw new \think\Exception($result["msg"]);
		}
	}
	public function getPluginsMeun($type = "addons", $lang = "chinese")
	{
		$languagesys = request()->languagesys ?: "CN";
		$plugins = $this->where("status", 1)->where("module", $type)->field("id,name,title,url")->order("order", "asc")->order("id", "asc")->select()->toArray();
		$type_dir = config("plugins_dir")[$type];
		foreach ($plugins as $k => &$v) {
			$class = cmf_get_plugin_class_shd($v["name"], $type_dir);
			if (!class_exists($class)) {
				unset($plugins[$k]);
				continue;
			}
			$obj = new $class();
			if (isset($obj->info["lang"]) && is_array($obj->info)) {
				$v["title"] = $obj->info["lang"][$lang];
			}
			$v["menu"] = [];
			$name = cmf_parse_name($v["name"], 0);
			if (file_exists(WEB_ROOT . "plugins/{$type}/{$name}/menu.php")) {
				$return = (require WEB_ROOT . "plugins/{$type}/{$name}/menu.php");
				if (!empty($return) && is_array($return)) {
					foreach ($return as &$value) {
						if (empty($value["custom"])) {
							$url = shd_addon_url($value["url"]) . "&languagesys={$languagesys}";
						} else {
							$url = $value["url"];
						}
						$value["url"] = $url;
						if (isset($value["lang"]) && is_array($value["lang"])) {
							$value["name"] = $value["lang"][$lang];
						}
					}
				}
				$v["menu"] = $return;
			}
		}
		if ($lang == "chinese") {
			$title = "插件中心";
			$name = "插件列表";
		} elseif ($lang == "english") {
			$title = "Plugin Center";
			$name = "Plugin List";
		} elseif ($lang == "chinese_tw") {
			$title = "插件中心";
			$name = "插件列表";
		}
		array_unshift($plugins, ["id" => 0, "name" => "PluginsList", "title" => $title, "url" => "", "menu" => [["name" => $name, "url" => "/" . adminAddress() . "/plugins?languagesys={$languagesys}"]]]);
		return $plugins;
	}
	public function getPluginsAdminMenu($name, $type = "addons")
	{
		$languagesys = request()->languagesys;
		if ($languagesys == "CN") {
			$lang = "chinese";
		} elseif ($languagesys == "US") {
			$lang = "english";
		} elseif ($languagesys == "HK") {
			$lang = "chinese_tw";
		}
		$menu = [];
		if (file_exists(WEB_ROOT . "plugins/{$type}/{$name}/menu.php")) {
			$return = (require WEB_ROOT . "plugins/{$type}/{$name}/menu.php");
			foreach ($return as $item) {
				if (empty($item["custom"])) {
					$url = shd_addon_url($item["url"]) . "&languagesys={$languagesys}";
				} else {
					$url = $item["url"];
				}
				if (isset($item["lang"]) && is_array($item["lang"])) {
					$item["name"] = $item["lang"][$lang];
				}
				$menu[] = ["name" => $item["name"], "url" => $url, "custom" => $item["custom"]];
			}
		}
		return $menu;
	}
	/**
	 * @TODO
	 * 获取所有钩子，包括系统，应用，模板
	 * @param bool $refresh 是否刷新缓存
	 * @return array
	 */
	public function getHooks($refresh = false)
	{
		if (!$refresh) {
		}
		$returnHooks = [];
		$systemHooks = ["app_init", "app_begin", "module_init", "action_begin", "view_filter", "app_end", "log_write", "log_write_done", "response_end", "home_init", "send_mobile_verification_code", "user_login_start", "body_start", "before_head_end", "before_footer", "footer_start", "before_footer_end", "before_body_end", "left_sidebar_start", "before_left_sidebar_end", "right_sidebar_start", "before_right_sidebar_end", "comment", "guestbook"];
		$systemHooks = array_merge($systemHooks, getSystemHook());
		$dbHooks = \think\Db::name("hook")->column("hook");
		$returnHooks = array_unique(array_merge($systemHooks, $dbHooks));
		return $returnHooks;
	}
	public function uninstall($id)
	{
		$findPlugin = $this->find($id);
		if (empty($findPlugin)) {
			return -1;
		}
		$class = cmf_get_plugin_class_shd($findPlugin["name"], $findPlugin["module"]);
		\think\Db::startTrans();
		try {
			$this->where("name", $findPlugin["name"])->delete();
			\think\Db::name("hook_plugin")->where("plugin", $findPlugin["name"])->delete();
			\think\Db::name("nav")->where("plugin", $findPlugin["name"])->delete();
			if (class_exists($class)) {
				$plugin = new $class();
				$uninstallSuccess = $plugin->uninstall();
				if (!$uninstallSuccess) {
					\think\Db::rollback();
					return -2;
				}
			}
			\think\Db::commit();
		} catch (\Exception $e) {
			\think\Db::rollback();
			return false;
		}
		return true;
	}
	public function menuClientarea($pluginName, $module = "addons")
	{
		$pluginDir = cmf_parse_name($pluginName);
		if (file_exists(WEB_ROOT . "plugins/{$module}/{$pluginDir}/menuclientarea.php")) {
			$menus = (require WEB_ROOT . "plugins/{$module}/{$pluginDir}/menuclientarea.php");
			if (!empty($menus[0])) {
				\think\Db::startTrans();
				try {
					foreach ($menus as $menu) {
						$this->pluginsHomeMenu($menu, $pluginName);
					}
					(new \app\common\logic\Menu())->addHookNav([$pluginName]);
					\think\Db::commit();
				} catch (\Exception $e) {
					\think\Db::rollback();
					return false;
				}
			}
		}
		return true;
	}
	private function pluginsHomeMenu($menu, $pluginName = "")
	{
		if (empty($menu)) {
			return null;
		}
		$nav = ["name" => trim($menu["name"] ?: ""), "url" => !empty($menu["url"]) ? shd_addon_url($menu["url"], [], true) : "", "fa_icon" => $menu["fa_icon"] ?: "", "lang" => json_encode($menu["lang"]), "pid" => $menu["pid"] ?: 0, "order" => 0, "nav_type" => 3, "relid" => 1, "menuid" => 1, "menu_type" => 1, "plugin" => $pluginName ?: ""];
		$id = \think\Db::name("nav")->insertGetId($nav);
		if (empty($menu["child"])) {
			return null;
		}
		$child = $menu["child"];
		foreach ($child as $item) {
			$item["pid"] = $id;
			$this->pluginsHomeMenu($item, $pluginName);
		}
	}
}