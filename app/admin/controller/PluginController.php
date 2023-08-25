<?php

namespace app\admin\controller;

/**
 * @title 插件管理
 */
class PluginController extends AdminBaseController
{
	protected $pluginModel;
	/**
	 * @title 插件列表
	 * @description 接口说明:
	 * @author shd
	 * @url /admin/pl_index/[:moduleName]/
	 * @method GET
	 * @param .name:moduleName type:string require:0 default: other: desc:插件模块名:gateways支付网关,addons插件,certification实名认证
	 * @return .id:id
	 * @return .status:'状态;1:开启;0:禁用,3:未安装',
	 * @return .has_admin:是否有后台管理,0:没有;1:有
	 * @return .name:'插件标识名,英文字母(惟一)',
	 * @return .title:名称
	 * @return .description:描述
	 * @return .module:所属模块
	 */
	public function plIndex(\think\Request $request)
	{
		$lang = $request->languagesys;
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
		$moduleName = $request->moduleName;
		$pluginModel = new \app\admin\model\PluginModel();
		$plugins = $pluginModel->getList($moduleName, $lang);
		$pluginsFilter = [];
		foreach ($plugins as $k => $v) {
			$pluginsFilter[] = $v;
		}
		return jsonrule(["data" => $pluginsFilter, "status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * @title 修改插件排序
	 * @description 接口说明：修改插件排序
	 * @param .name:id type:int require:1 default: other: desc:插件ID
	 * @param .name:pre_id type:int require:1 default: other: desc:移动后前一个插件ID
	 * @param .name:moduleName type:int require:1 default: other: desc:插件模块名:gateways,addons,servers
	 * @author wyh
	 * @url /admin/pl_sort/[:moduleName]/
	 * @method POST
	 * @time 2020-10-19
	 */
	public function plSort()
	{
		if (!configuration("pl_custom_sort")) {
			$pl = new \app\admin\model\PluginModel();
			$pl->customInit();
			updateConfiguration("pl_custom_sort", 1);
		}
		$params = $this->request->param();
		$id = intval($params["id"]);
		$pre_id = intval($params["pre_id"]);
		$module_name = $params["moduleName"];
		if ($pre_id) {
			$pre_order = \think\Db::name("plugin")->where("id", $pre_id)->value("order");
			\think\Db::name("plugin")->where("order", "<=", $pre_order)->setDec("order");
			\think\Db::name("plugin")->where("id", $id)->update(["order" => $pre_order]);
		} else {
			$min_order = \think\Db::name("plugin")->where("module", $module_name)->min("order");
			\think\Db::name("plugin")->where("id", $id)->update(["order" => $min_order - 1]);
		}
		return jsonrule(["status" => 200, "msg" => lang("修改排序成功")]);
	}
	/**
	 * @title 复制线下支付插件
	 * @description 接口说明：复制线下支付插件
	 * @param .name:name type:int require:1 default: other: desc: 固定值UserCustom
	 * @author wyh
	 * @url /admin/pl_copy
	 * @method POST
	 * @time 2020-08-28
	 */
	public function plCopy()
	{
		$param = $this->request->param();
		$name = $param["name"] ?? "UserCustom";
		$pl = \think\Db::name("plugin")->where("name", "UserCustom")->find();
		unset($pl["id"]);
		$pl["create_time"] = time();
		$id = \think\Db::name("plugin")->insertGetId($pl);
		if ($id) {
			$src = CMF_ROOT . "public/plugins/gateways/user_custom";
			$dst = CMF_ROOT . "public/plugins/gateways/user_custom{$id}";
			$res = recurse_copy($src, $dst);
			if ($res["status"] == 200) {
				$url = $dst . "/UserCustomPlugin.php";
				$str = file_get_contents($url);
				$str = str_replace(["user_custom", "UserCustom"], ["user_custom" . $id, "UserCustom" . $id], $str);
				@unlink($url);
				$re_url = $dst . "/UserCustom{$id}Plugin.php";
				file_put_contents($re_url, $str);
				@rename($dst . "/UserCustom.png", $dst . "/UserCustom{$id}.png");
				\think\Db::name("plugin")->where("id", $id)->update(["name" => "UserCustom" . $id]);
				return jsonrule(["status" => 200, "msg" => lang("DUPLICATE SUCCESS")]);
			} else {
				return jsonrule(["status" => 400, "msg" => lang("DUPLICATE FAIL")]);
			}
		} else {
			return jsonrule(["status" => 400, "msg" => lang("DUPLICATE FAIL")]);
		}
	}
	/**
	 * @title 插件安装
	 * @description 接口说明
	 * @param .name:name type:int require:1 default: other: desc:如GlobalWxpay
	 * @param .name:module type:int require:1 default:'' other: desc:如gateways
	 * @author 上官刀
	 * @url /admin/pl_install
	 * @method POST
	 */
	public function plInstall()
	{
		$pluginName = $this->request->param("name", "", "trim");
		$type_dir = $module = $this->request->param("module", "addons", "trim");
		$class = cmf_get_plugin_class_shd($pluginName, $type_dir);
		if (!class_exists($class)) {
			return jsonrule(["msg" => "插件不存在", "status" => 400]);
		}
		$pluginModel = new \app\admin\model\PluginModel();
		$pluginCount = $pluginModel->where("name", $pluginName)->where("module", $type_dir)->count();
		if ($pluginCount > 0) {
			return jsonrule(["msg" => "插件已安装", "status" => 400]);
		}
		$plugin = new $class();
		$info = $plugin->info;
		if (!$info || !$plugin->checkInfo()) {
			return jsonrule(["msg" => "插件信息缺失", "status" => 400]);
		}
		$info["module"] = $info["module"] ?: $module;
		$installSuccess = $plugin->install();
		if (!$installSuccess) {
			return jsonrule(["msg" => "插件预安装失败", "status" => 400]);
		}
		$methods = get_class_methods($plugin);
		if (in_array(lcfirst($pluginName) . "idcsmartauthorize", $methods) || in_array($pluginName . "idcsmartauthorize", $methods)) {
			$res = pluginIdcsmartauthorize($pluginName);
			if ($res["status"] != 200) {
				return jsonrule($res);
			}
		}
		foreach ($methods as $methodKey => $method) {
			$methods[$methodKey] = cmf_parse_name($method);
		}
		if ($type_dir == "mail") {
			$mail_default_send = $this->request->param("mail_default_send", "", "trim");
			if ($mail_default_send == 1) {
				updateConfiguration("email_operator", strtolower($pluginName));
			}
		}
		if ($type_dir == "sms") {
			$sms_name = strtolower($pluginName);
			$sms_default_send = $this->request->param("sms_default_send", "", "trim");
			if ($sms_default_send == 1) {
				updateConfiguration("sms_operator", $sms_name);
			}
			if (is_array($installSuccess) && count($installSuccess) > 0) {
				$message_template_type = config("message_template_type");
				$message_template_type = array_column($message_template_type, "id", "name");
				foreach ($installSuccess as $k => $v) {
					$message_template_typeid = $message_template_type[$v["name"]];
					$installSuccess[$k] = $v;
					if (!empty($message_template_typeid)) {
						$installSuccess[$k]["id"] = $message_template_typeid;
					}
				}
				$installSuccess2 = [];
				$methods = get_class_methods($class) ?: [];
				foreach ($methods as $method) {
					$num = count($installSuccess2);
					if ($method == "sendCnSms") {
						foreach ($installSuccess as $k => $v) {
							$installSuccess2[$k + $num] = $v;
							$installSuccess2[$k + $num]["range_type"] = 0;
						}
					} elseif ($method == "sendGlobalSms") {
						foreach ($installSuccess as $k => $v) {
							$installSuccess2[$k + $num] = $v;
							$installSuccess2[$k + $num]["range_type"] = 1;
						}
					}
				}
				$time = time();
				$insertAll = [];
				foreach ($installSuccess2 as $v) {
					$range_type = !empty($v["range_type"]) ? 1 : 0;
					$message_template["range_type"] = $range_type;
					$message_template["title"] = $v["type"];
					$message_template["content"] = $v["var"];
					$message_template["sms_operator"] = $sms_name;
					$message_template["remark"] = $v["type"];
					$message_template["template_id"] = $v["template_id"] ?? "";
					$message_template["status"] = 0;
					$message_template["create_time"] = $time;
					$message_template["update_time"] = $time;
					$sms_temp_id = \think\Db::name("message_template")->insertGetId($message_template);
					$message_template_link["sms_temp_id"] = $sms_temp_id;
					$message_template_link["type"] = $v["id"];
					$message_template_link["range_type"] = $range_type;
					$message_template_link["sms_operator"] = $sms_name;
					$message_template_link["is_use"] = 1;
					\think\Db::name("message_template_link")->insertGetId($message_template_link);
				}
			}
		}
		$systemHooks = $pluginModel->getHooks(true);
		$pluginHooks = array_intersect($systemHooks, $methods);
		$info["hooks"] = implode(",", $pluginHooks);
		if (!empty($plugin->hasAdmin)) {
			$info["has_admin"] = 1;
		} else {
			$info["has_admin"] = 0;
		}
		$info["config"] = json_encode($plugin->getConfig());
		$pluginModel->data($info)->allowField(true)->save();
		if ($type_dir == "automatic") {
			$plugin->configInit();
		}
		foreach ($pluginHooks as $pluginHook) {
			\think\Db::name("hook_plugin")->insert(["hook" => $pluginHook, "plugin" => $pluginName, "status" => 1, "module" => $module]);
		}
		\think\facade\Cache::clear("init_hook_plugins");
		\think\facade\Cache::clear("init_hook_plugins_system_hook_plugins");
		$menuClientarea = $pluginModel->menuClientarea($pluginName, $module);
		if (!$menuClientarea) {
			return jsonrule(["msg" => "前台菜单插入失败,请卸载后重新安装", "status" => 400]);
		}
		return jsonrule(["msg" => "安装成功", "status" => 200]);
	}
	/**
	 * @title 插件卸载
	 * @description 接口说明
	 * @param .name:id type:int require:1 default: other: desc:卸载插件id
	 * @param .name:default type:int require:1 default: other: desc:默认id
	 * @author 上官刀
	 * @url /admin/pl_uninstall
	 * @method POST
	 */
	public function plUninstall()
	{
		$pluginModel = new \app\admin\model\PluginModel();
		$params = $this->request->param();
		$id = intval($params["id"]);
		$plugin = \think\Db::name("plugin")->where("id", $id)->field("name,module")->find();
		if ($plugin["module"] == "sms") {
			if (!empty($params["sms_default_send_name"])) {
				updateConfiguration("smg_operator", $params["sms_default_send_name"]);
			}
			if ($params["sms_delete_template"] == 1) {
				$sms_operator = strtolower($plugin["name"]);
				$sms["config"] = pluginConfig(ucfirst($sms_operator), "sms");
				$message_template = \think\Db::name("message_template")->where("sms_operator", $sms_operator)->field("template_id,range_type")->find();
				foreach ($message_template as $smstemplate) {
					$deleteCnTemplate = $smstemplate["range_type"] == 0 ? "deleteCnTemplate" : "deleteGlobalTemplate";
					$sms["template_id"] = $smstemplate["template_id"];
					zjmfhook(ucfirst($type), "sms", $sms, $deleteCnTemplate);
				}
				\think\Db::name("message_template")->where("sms_operator", strtolower($plugin["name"]))->delete();
				\think\Db::name("message_template_link")->where("sms_operator", strtolower($plugin["name"]))->delete();
			}
		} elseif ($plugin["module"] == "automatic") {
			$bot_lgc = new \app\common\logic\Bot();
			$bot_lgc->to_botsys_uninstall_quitbot_all($plugin["name"]);
		} elseif ($plugin["module"] == "mail") {
			if (!empty($params["mail_default_send_name"])) {
				updateConfiguration("email_operator", strtolower($params["mail_default_send_name"]));
			}
		}
		$result = $pluginModel->uninstall($id);
		if ($result !== true) {
			return jsonrule(["msg" => "卸载失败", "status" => 400]);
		}
		$name = \think\Db::name("plugin")->where("id", $id)->where("module", "gateways")->value("name");
		$new = \think\Db::name("plugin")->where("id", intval($params["default"]))->where("module", "gateways")->value("name");
		if (!is_null($name)) {
			$new = $new ?: "";
			\think\Db::name("clients")->where("defaultgateway", $name)->update(["defaultgateway" => $new]);
		}
		\think\facade\Cache::clear("init_hook_plugins");
		\think\facade\Cache::clear("init_hook_plugins_system_hook_plugins");
		return jsonrule(["msg" => "卸载成功", "status" => 200]);
	}
	/**
	 * @title 禁用(启用)插件
	 * @description 接口说明:
	 * @author shd
	 * @url /admin/pl_toggle
	 * @method POST
	 * @param .name:id type:int require:1 default: other: desc:插件id
	 * @param .name:default type:int require:0 default: other: desc:选择的默认插件id
	 * @param .name:enable type:string require:1 default:1 other: desc:二选一(值为1)
	 * @param .name:disable type:string require:1 default:1 other: desc:二选一(值为1)
	 */
	public function plToggle()
	{
		$params = $this->request->param();
		$id = intval($params["id"]);
		$pluginModel = \app\admin\model\PluginModel::get($id);
		if (empty($pluginModel)) {
			return jsonrule(["msg" => "插件不存在", "status" => 400]);
		}
		$status = 1;
		$successMessage = "启用成功！";
		if ($this->request->param("disable")) {
			$status = 0;
			$successMessage = "禁用成功！";
		}
		$pluginModel->startTrans();
		try {
			$pluginModel->save(["status" => $status], ["id" => $id]);
			$hookPluginModel = new \app\admin\model\HookPluginModel();
			$hookPluginModel->save(["status" => $status], ["plugin" => $pluginModel->name]);
			$pluginModel->commit();
			$arr = gateway_list("gateways", 0);
			$arr = array_column($arr, "title", "name");
			if ($status == 1) {
				active_log(sprintf($this->lang["Plugin_admin_plToggle_open"], $arr[$pluginModel->name]));
			} else {
				active_log(sprintf($this->lang["Plugin_admin_plToggle_close"], $arr[$pluginModel->name]));
			}
		} catch (\Exception $e) {
			$pluginModel->rollback();
			return jsonrule(["msg" => "操作失败", "status" => 400]);
		}
		if (!$status) {
			if ($pluginModel->module == "sms") {
				updateConfiguration("sms_operator_global", strtolower($params["default"]));
				updateConfiguration("sms_operator", strtolower($params["default"]));
			}
			if ($pluginModel->module == "mail") {
				updateConfiguration("email_operator", strtolower($params["default"]));
			}
		}
		if ($this->request->param("disable")) {
			$name = \think\Db::name("plugin")->where("id", $id)->value("name");
			$new = \think\Db::name("plugin")->where("id", intval($params["default"]))->value("name");
			\think\Db::name("clients")->where("defaultgateway", $name)->update(["defaultgateway" => $new]);
		}
		if ($this->request->param("disable")) {
			if ($pluginModel->module == "certification") {
				$count = \think\Db::name("plugin")->where("id", "<>", $id)->where("status", 1)->where("module", "certification")->count();
				if ($count < 1) {
					$successMessage = "实名认证接口已全部禁用,将自动转为人工审核";
				}
			}
		}
		if ($this->request->param("enable")) {
			if ($pluginModel->module == "certification") {
				$count = \think\Db::name("plugin")->where("id", "<>", $id)->where("status", 1)->where("module", "certification")->count();
				if ($count < 1) {
					$successMessage = "实名认证接口已启用,人工审核将自动关闭";
				}
			}
		}
		return jsonrule(["msg" => $successMessage, "status" => 200]);
	}
	/**
	 * @title 配置插件
	 * @description 接口说明:
	 * @author shd
	 * @url /admin/pl_setting/[:module]/:id
	 * @method GET
	 * @param .name:id type:string require:1 default: other: desc:插件模id
	 * @param .name:module type:string require:1 default: other: desc:插件模块名:gateways
	 * @return .id:id
	 * @return .status:'状态;1:开启;0:禁用,3:未安装',
	 * @return .name:插件名,
	 * @return .module:所属网关名称,
	 * @return config:配置@
	 * @config AppId:配置字段@
	 * @AppId .title:名称
	 * @AppId .type:字段类型
	 * @AppId .value:字段值
	 * @AppId .tip:提示信息
	 */
	public function plSetting()
	{
		$id = $this->request->param("id", 0, "intval");
		$module = $this->request->param("module", "plugins", "trim");
		$pluginModel = new \app\admin\model\PluginModel();
		$plugin = $pluginModel->find($id);
		if (empty($plugin)) {
			return jsonrule(["status" => 400, "msg" => lang("插件未安装")]);
		}
		$plugin = $plugin->toArray();
		$type_dir = $module;
		$pluginClass = cmf_get_plugin_class_shd($plugin["name"], $type_dir);
		if (!class_exists($pluginClass)) {
			return jsonrule(["status" => 400, "msg" => lang("插件不存在")]);
		}
		$pluginObj = new $pluginClass();
		$pluginName = $plugin["name"];
		$methods = get_class_methods($pluginObj);
		if (in_array(lcfirst($pluginName) . "idcsmartauthorize", $methods) || in_array($pluginName . "idcsmartauthorize", $methods)) {
			$res = pluginIdcsmartauthorize($pluginName);
			if ($res["status"] != 200) {
				return jsonrule($res);
			}
		}
		$pluginConfigInDb = $plugin["config"];
		$plugin["config"] = (include $pluginObj->getConfigFilePath());
		if ($pluginConfigInDb) {
			$pluginConfigInDb = json_decode($pluginConfigInDb, true);
			foreach ($plugin["config"] as $key => $value) {
				if ($value["type"] != "group") {
					$plugin["config"][$key]["field"] = $key;
					if (isset($pluginConfigInDb[$key])) {
						$plugin["config"][$key]["field"] = $key;
						$plugin["config"][$key]["value"] = htmlspecialchars_decode($pluginConfigInDb[$key], ENT_QUOTES);
					}
				} else {
					$plugin["config"][$key]["field"] = $key;
					foreach ($value["options"] as $group => $options) {
						foreach ($options["options"] as $gkey => $value) {
							if (isset($pluginConfigInDb[$gkey])) {
								$plugin["config"][$key]["options"][$group]["options"][$gkey]["value"] = htmlspecialchars_decode($pluginConfigInDb[$gkey], ENT_QUOTES);
							}
						}
					}
				}
			}
		}
		$data = ["data" => $plugin, "id" => $id];
		return jsonrule(["status" => 200, "data" => $data, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * @title 保存插件配置
	 * @description 接口说明:
	 * @author shd
	 * @url /admin/pl_setting_post
	 * @method POST
	 * @param .name:id type:string require:1 default: other: desc:插件id
	 * @param .name:module type:string require:1 default: other: desc:插件模块名:gateways
	 * @param .name:config[字段] type:string require:1 default: other: desc:配置字段值(如：module_name,seller_id,app_id等)
	 */
	public function plSettingPost()
	{
		if ($this->request->isPost()) {
			$id = $this->request->param("id", 0, "intval");
			$pluginModel = new \app\admin\model\PluginModel();
			$plugin = $pluginModel->find($id);
			if (!$plugin) {
				return jsonrule(["status" => 400, "msg" => lang("插件未安装")]);
			}
			$plugin = $plugin->toArray();
			$module = $this->request->param("module", "plugins", "trim");
			$type_dir = $module;
			$pluginClass = cmf_get_plugin_class_shd($plugin["name"], $type_dir);
			if (!class_exists($pluginClass)) {
				return jsonrule(["status" => 400, "msg" => lang("插件不存在")]);
			}
			$pluginObj = new $pluginClass();
			$pluginName = $plugin["name"];
			$methods = get_class_methods($pluginObj);
			if (in_array(lcfirst($pluginName) . "idcsmartauthorize", $methods) || in_array($pluginName . "idcsmartauthorize", $methods)) {
				$res = pluginIdcsmartauthorize($pluginName);
				if ($res["status"] != 200) {
					return jsonrule($res);
				}
			}
			$plugin["config"] = (include $pluginObj->getConfigFilePath());
			$rules = [];
			$messages = [];
			foreach ($plugin["config"] as $key => $value) {
				if ($value["type"] != "group") {
					if (isset($value["rule"])) {
						$rules[$key] = $this->_parseRules($value["rule"]);
					}
					if (isset($value["message"])) {
						foreach ($value["message"] as $rule => $msg) {
							$messages[$key . "." . $rule] = $msg;
						}
					}
				} else {
					foreach ($value["options"] as $group => $options) {
						foreach ($options["options"] as $gkey => $value) {
							if (isset($value["rule"])) {
								$rules[$gkey] = $this->_parseRules($value["rule"]);
							}
							if (isset($value["message"])) {
								foreach ($value["message"] as $rule => $msg) {
									$messages[$gkey . "." . $rule] = $msg;
								}
							}
						}
					}
				}
			}
			$config = $this->request->param("config/a");
			$validate = new \think\Validate($rules, $messages);
			$result = $validate->check($config);
			if ($result !== true) {
				return jsonrule(["status" => 400, "msg" => $validate->getError()]);
			}
			$pluginModel = new \app\admin\model\PluginModel();
			if ($config["module_name"]) {
				$pluginModel->save(["title" => $config["module_name"]], ["id" => $id]);
			}
			if ($config["return_url"]) {
				unset($config["return_url"]);
			}
			if ($config["notify_url"]) {
				unset($config["notify_url"]);
			}
			\think\Db::name("plugin")->where("id", $id)->update(["config" => json_encode($config)]);
			return jsonrule(["status" => 200, "msg" => lang("EDIT SUCCESS")]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * 解析插件配置验证规则
	 * @param $rules
	 * @return array
	 */
	private function _parseRules($rules)
	{
		$newRules = [];
		$simpleRules = ["require", "number", "integer", "float", "boolean", "email", "array", "accepted", "date", "alpha", "alphaNum", "alphaDash", "activeUrl", "url", "ip"];
		foreach ($rules as $key => $rule) {
			if (in_array($key, $simpleRules) && $rule) {
				array_push($newRules, $key);
			}
		}
		return $newRules;
	}
	/**
	 * @title 插件更新
	 * @description 接口说明
	 * @param .name:name type:int require:1 default: other: desc:如GlobalWxpay
	 * @param .name:module type:int require:1 default:'' other: desc:如gateways支付网关,addons插件
	 * @author wyh
	 * @url /admin/pl_update
	 * @method POST
	 */
	public function plUpdate()
	{
		$pluginName = $this->request->param("name", "", "trim");
		$appid = $this->request->param("app_id", "0", "trim");
		$type_dir = $this->request->param("module", "addons", "trim");
		try {
			set_time_limit(0);
			(new \app\admin\model\PluginModel())->checkPlInstall($appid);
			if (in_array($type_dir, ["sms", "oauth"])) {
				$this->noDataUpdate();
				return jsonrule(["status" => 200, "msg" => lang("UPDATE SUCCESS")]);
			}
		} catch (\Throwable $e) {
			return jsonrule(["status" => 400, "msg" => $e->getMessage()]);
		}
		$class = cmf_get_plugin_class_shd($pluginName, $type_dir);
		if (!class_exists($class)) {
			return jsonrule(["status" => 400, "msg" => "插件不存在!"]);
		}
		$plugin = new $class();
		$info = $plugin->info;
		if (!$info || !$plugin->checkInfo()) {
			return jsonrule(["status" => 400, "msg" => "插件信息缺失!"]);
		}
		$info["module"] = $info["module"] ?: $type_dir;
		$pluginModel = new \app\admin\model\PluginModel();
		$methods = get_class_methods($plugin);
		if (in_array(lcfirst($pluginName) . "idcsmartauthorize", $methods) || in_array($pluginName . "idcsmartauthorize", $methods)) {
			$res = pluginIdcsmartauthorize($pluginName);
			if ($res["status"] != 200) {
				return jsonrule($res);
			}
		}
		$flag = true;
		\think\Db::startTrans();
		try {
			\think\Db::name("nav")->where("plugin", $pluginName)->delete();
			$menuClientarea = $pluginModel->menuClientarea($pluginName, $type_dir);
			if (!$menuClientarea) {
				\think\Db::rollback();
				$flag = false;
			}
			\think\Db::commit();
		} catch (\Exception $e) {
			\think\Db::rollback();
			$flag = false;
		}
		if (!$flag) {
			return jsonrule(["status" => 400, "msg" => "插件菜单更新失败"]);
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
		$config = $plugin->getConfig();
		$defaultConfig = $plugin->getDefaultConfig();
		$pluginModel = new \app\admin\model\PluginModel();
		$config = array_merge($defaultConfig, $config);
		$info["config"] = json_encode($config);
		$pluginModel->allowField(true)->save($info, ["name" => $pluginName]);
		$hookPluginModel = new \app\admin\model\HookPluginModel();
		$pluginHooksInDb = $hookPluginModel->where("plugin", $pluginName)->column("hook");
		$samePluginHooks = array_intersect($pluginHooks, $pluginHooksInDb);
		$shouldDeleteHooks = array_diff($samePluginHooks, $pluginHooksInDb);
		$newHooks = array_diff($pluginHooks, $samePluginHooks);
		if (count($shouldDeleteHooks) > 0) {
			$hookPluginModel->where("hook", "in", $shouldDeleteHooks)->delete();
		}
		foreach ($newHooks as $pluginHook) {
			$hookPluginModel->data(["hook" => $pluginHook, "plugin" => $pluginName])->isUpdate(false)->save();
		}
		\think\facade\Cache::clear("init_hook_plugins");
		\think\facade\Cache::clear("init_hook_plugins_system_hook_plugins");
		return jsonrule(["status" => 200, "msg" => lang("UPDATE SUCCESS")]);
	}
	public function noDataUpdate()
	{
		$param = $this->request->param();
		$app_version = (new \app\admin\model\PluginModel())->getNewVersion();
		if (!$param["id"]) {
			throw new \think\Exception("数据信息不存在");
		}
		if (isset($app_version[$param["name"]])) {
		}
	}
}