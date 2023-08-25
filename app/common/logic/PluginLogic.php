<?php

namespace app\common\logic;

class PluginLogic
{
	/**
	 * 安装应用
	 */
	public static function install($pluginName)
	{
		$class = cmf_get_plugin_class($pluginName);
		if (!class_exists($class)) {
			return "插件不存在!";
		}
		$pluginModel = new \app\admin\model\PluginModel();
		$pluginCount = $pluginModel->where("name", $pluginName)->count();
		if ($pluginCount > 0) {
			return "插件已安装!";
		}
		$plugin = new $class();
		$info = $plugin->info;
		if (!$info || !$plugin->checkInfo()) {
			return "插件信息缺失!";
		}
		$installSuccess = $plugin->install();
		if (!$installSuccess) {
			return "插件预安装失败!";
		}
		$methods = get_class_methods($plugin);
		foreach ($methods as $methodKey => $method) {
			$methods[$methodKey] = cmf_parse_name($method);
		}
		$systemHooks = $pluginModel->getHooks(true);
		$pluginHooks = array_intersect($systemHooks, $methods);
		if (!empty($plugin->hasAdmin)) {
			$info["has_admin"] = 1;
		} else {
			$info["has_admin"] = 0;
		}
		$info["config"] = json_encode($plugin->getConfig());
		$pluginModel->save($info);
		foreach ($pluginHooks as $pluginHook) {
			$hookPluginModel = new \app\admin\model\HookPluginModel();
			$hookPluginModel->save(["hook" => $pluginHook, "plugin" => $pluginName, "status" => 1]);
		}
		self::getActions($pluginName);
		\think\facade\Cache::clear("init_hook_plugins");
		\think\facade\Cache::clear("admin_menus");
		return true;
	}
	public static function update($pluginName)
	{
		$class = cmf_get_plugin_class($pluginName);
		if (!class_exists($class)) {
			return "插件不存在!";
		}
		$plugin = new $class();
		$info = $plugin->info;
		if (!$info || !$plugin->checkInfo()) {
			return "插件信息缺失!";
		}
		if (method_exists($plugin, "update")) {
			$updateSuccess = $plugin->update();
			if (!$updateSuccess) {
				return "插件预升级失败!";
			}
		}
		$methods = get_class_methods($plugin);
		foreach ($methods as $methodKey => $method) {
			$methods[$methodKey] = cmf_parse_name($method);
		}
		$pluginModel = new \app\admin\model\PluginModel();
		$systemHooks = $pluginModel->getHooks(true);
		$pluginHooks = array_intersect($systemHooks, $methods);
		if (!empty($plugin->hasAdmin)) {
			$info["has_admin"] = 1;
		} else {
			$info["has_admin"] = 0;
		}
		$config = $plugin->getConfig();
		$defaultConfig = $plugin->getDefaultConfig();
		$pluginModel = new \app\admin\model\PluginModel();
		$config = array_merge($defaultConfig, $config);
		$info["config"] = json_encode($config);
		$pluginModel->where("name", $pluginName)->update($info);
		$hookPluginModel = new \app\admin\model\HookPluginModel();
		$pluginHooksInDb = $hookPluginModel->where("plugin", $pluginName)->column("hook");
		$samePluginHooks = array_intersect($pluginHooks, $pluginHooksInDb);
		$shouldDeleteHooks = array_diff($samePluginHooks, $pluginHooksInDb);
		$newHooks = array_diff($pluginHooks, $samePluginHooks);
		if (count($shouldDeleteHooks) > 0) {
			$hookPluginModel->where("hook", "in", $shouldDeleteHooks)->delete();
		}
		foreach ($newHooks as $pluginHook) {
			$hookPluginModel->save(["hook" => $pluginHook, "plugin" => $pluginName]);
		}
		self::getActions($pluginName);
		\think\facade\Cache::clear("init_hook_plugins");
		\think\facade\Cache::clear("admin_menus");
		return true;
	}
	public static function getActions($pluginName)
	{
		\mindplay\annotations\Annotations::$config["cache"] = false;
		$annotationManager = \mindplay\annotations\Annotations::getManager();
		$annotationManager->registry["adminMenu"] = "app\\admin\\annotation\\AdminMenuAnnotation";
		$annotationManager->registry["adminMenuRoot"] = "app\\admin\\annotation\\AdminMenuRootAnnotation";
		$newMenus = [];
		$pluginDir = cmf_parse_name($pluginName);
		$filePatten = WEB_ROOT . "plugins/" . $pluginDir . "/controller/Admin*Controller.php";
		$controllers = cmf_scan_dir($filePatten);
		$app = "plugin/" . $pluginName;
		if (!empty($controllers)) {
			foreach ($controllers as $controller) {
				$controller = preg_replace("/\\.php\$/", "", $controller);
				$controllerName = preg_replace("/Controller\$/", "", $controller);
				$controllerClass = "plugins\\{$pluginDir}\\controller\\{$controller}";
				$menuAnnotations = \mindplay\annotations\Annotations::ofClass($controllerClass, "@adminMenuRoot");
				if (!empty($menuAnnotations)) {
					foreach ($menuAnnotations as $menuAnnotation) {
						$name = $menuAnnotation->name;
						$icon = $menuAnnotation->icon;
						$type = 0;
						$action = $menuAnnotation->action;
						$status = empty($menuAnnotation->display) ? 0 : 1;
						$listOrder = floatval($menuAnnotation->order);
						$param = $menuAnnotation->param;
						$remark = $menuAnnotation->remark;
						if (empty($menuAnnotation->parent)) {
							$parentId = 0;
						} else {
							$parent = explode("/", $menuAnnotation->parent);
							$countParent = count($parent);
							if ($countParent > 3) {
								throw new \Exception($controllerClass . ":" . $action . "  @adminMenuRoot parent格式不正确!");
							}
							$parentApp = $app;
							$parentController = $controllerName;
							$parentAction = "";
							switch ($countParent) {
								case 1:
									$parentAction = $parent[0];
									break;
								case 2:
									$parentController = $parent[0];
									$parentAction = $parent[1];
									break;
								case 3:
									$parentApp = $parent[0];
									$parentController = $parent[1];
									$parentAction = $parent[2];
									break;
							}
							$findParentAdminMenu = \app\admin\model\AdminMenuModel::where(["app" => $parentApp, "controller" => $parentController, "action" => $parentAction])->find();
							if (empty($findParentAdminMenu)) {
								$parentId = \app\admin\model\AdminMenuModel::insertGetId(["app" => $parentApp, "controller" => $parentController, "action" => $parentAction, "name" => "--new--"]);
							} else {
								$parentId = $findParentAdminMenu["id"];
							}
						}
						$findAdminMenu = \app\admin\model\AdminMenuModel::where(["app" => $app, "controller" => $controllerName, "action" => $action])->find();
						if (empty($findAdminMenu)) {
							\app\admin\model\AdminMenuModel::insert(["parent_id" => $parentId, "type" => $type, "status" => $status, "list_order" => $listOrder, "app" => $app, "controller" => $controllerName, "action" => $action, "param" => $param, "name" => $name, "icon" => $icon, "remark" => $remark]);
							$menuName = $name;
						} else {
							if ($findAdminMenu["name"] == "--new--") {
								\app\admin\model\AdminMenuModel::where(["app" => $app, "controller" => $controllerName, "action" => $action])->update(["parent_id" => $parentId, "type" => $type, "status" => $status, "list_order" => $listOrder, "param" => $param, "name" => $name, "icon" => $icon, "remark" => $remark]);
								$menuName = $name;
							} else {
								\app\admin\model\AdminMenuModel::where(["app" => $app, "controller" => $controllerName, "action" => $action])->update(["type" => $type]);
								$menuName = $findAdminMenu["name"];
							}
						}
						$authRuleName = "plugin/{$pluginName}/{$controllerName}/{$action}";
						$findAuthRuleCount = \app\admin\model\AuthRuleModel::where(["app" => $app, "name" => $authRuleName, "type" => "admin_url"])->count();
						if ($findAuthRuleCount == 0) {
							\app\admin\model\AuthRuleModel::insert(["app" => $app, "name" => $authRuleName, "type" => "admin_url", "param" => $param, "title" => $menuName]);
						} else {
							\app\admin\model\AuthRuleModel::where(["app" => $app, "name" => $authRuleName, "type" => "admin_url"])->update(["param" => $param, "title" => $menuName]);
						}
					}
				}
				$reflect = new \ReflectionClass($controllerClass);
				$methods = $reflect->getMethods(\ReflectionMethod::IS_PUBLIC);
				if (!empty($methods)) {
					foreach ($methods as $method) {
						if ($method->class == $controllerClass && strpos($method->name, "_") !== 0) {
							$menuAnnotations = \mindplay\annotations\Annotations::ofMethod($controllerClass, $method->name, "@adminMenu");
							if (!empty($menuAnnotations)) {
								$menuAnnotation = $menuAnnotations[0];
								$name = $menuAnnotation->name;
								$icon = $menuAnnotation->icon;
								$type = $menuAnnotation->hasView ? 1 : 2;
								$action = $method->name;
								$status = empty($menuAnnotation->display) ? 0 : 1;
								$listOrder = floatval($menuAnnotation->order);
								$param = $menuAnnotation->param;
								$remark = $menuAnnotation->remark;
								if (empty($menuAnnotation->parent)) {
									$parentId = 0;
								} else {
									$parent = explode("/", $menuAnnotation->parent);
									$countParent = count($parent);
									if ($countParent > 3) {
										throw new \Exception($controllerClass . ":" . $action . "  @menuRoot parent格式不正确!");
									}
									$parentApp = $app;
									$parentController = $controllerName;
									$parentAction = "";
									switch ($countParent) {
										case 1:
											$parentAction = $parent[0];
											break;
										case 2:
											$parentController = $parent[0];
											$parentAction = $parent[1];
											break;
										case 3:
											$parentApp = $parent[0];
											$parentController = $parent[1];
											$parentAction = $parent[2];
											break;
									}
									$findParentAdminMenu = \app\admin\model\AdminMenuModel::where(["app" => $parentApp, "controller" => $parentController, "action" => $parentAction])->find();
									if (empty($findParentAdminMenu)) {
										$parentId = \app\admin\model\AdminMenuModel::insertGetId(["app" => $parentApp, "controller" => $parentController, "action" => $parentAction, "name" => "--new--"]);
									} else {
										$parentId = $findParentAdminMenu["id"];
									}
								}
								$findAdminMenu = \app\admin\model\AdminMenuModel::where(["app" => $app, "controller" => $controllerName, "action" => $action])->find();
								if (empty($findAdminMenu)) {
									\app\admin\model\AdminMenuModel::insert(["parent_id" => $parentId, "type" => $type, "status" => $status, "list_order" => $listOrder, "app" => $app, "controller" => $controllerName, "action" => $action, "param" => $param, "name" => $name, "icon" => $icon, "remark" => $remark]);
									$menuName = $name;
								} else {
									if ($findAdminMenu["name"] == "--new--") {
										\app\admin\model\AdminMenuModel::where(["app" => $app, "controller" => $controllerName, "action" => $action])->update(["parent_id" => $parentId, "type" => $type, "status" => $status, "list_order" => $listOrder, "param" => $param, "name" => $name, "icon" => $icon, "remark" => $remark]);
										$menuName = $name;
									} else {
										\app\admin\model\AdminMenuModel::where(["app" => $app, "controller" => $controllerName, "action" => $action])->update(["type" => $type]);
										$menuName = $findAdminMenu["name"];
									}
								}
								$authRuleName = "plugin/{$pluginName}/{$controllerName}/{$action}";
								$findAuthRuleCount = \app\admin\model\AuthRuleModel::where(["app" => $app, "name" => $authRuleName, "type" => "plugin_url"])->count();
								if ($findAuthRuleCount == 0) {
									\app\admin\model\AuthRuleModel::insert(["app" => $app, "name" => $authRuleName, "type" => "plugin_url", "param" => $param, "title" => $menuName]);
								} else {
									\app\admin\model\AuthRuleModel::where(["app" => $app, "name" => $authRuleName, "type" => "plugin_url"])->update(["param" => $param, "title" => $menuName]);
								}
							}
						}
					}
				}
			}
		}
	}
}