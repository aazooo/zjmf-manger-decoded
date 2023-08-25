<?php

namespace app\admin\controller;

/**
 * @title 后台设置三方登录
 * @description 三方登录接口说明
 */
class OauthController extends AdminBaseController
{
	private $modules = "oauth";
	public function __construct()
	{
		$this->domain = configuration("domain");
	}
	/**
	 * @title 所有三方登录
	 * @description 接口说明:所有三方登录
	 * 时间 2020/11/30
	 * @author xionglingyuan
	 * @url /admin/oauth
	 * @method GET
	 * @return .id:id
	 * @return .status:'状态;1开启;0禁用,3未安装',
	 * @return .name:'插件标识名,英文字母(惟一)',
	 * @return .title:名称
	 * @return .description:描述
	 * @return .module:所属模块	 
	 * @return .img:图片	 
	 */
	public function listing()
	{
		$oauth = array_map("basename", glob(CMF_ROOT . "modules/{$this->modules}/*", GLOB_ONLYDIR));
		$oauth2 = array_map("basename", glob(WEB_ROOT . "plugins/{$this->modules}/*", GLOB_ONLYDIR));
		$oauth = array_merge($oauth, $oauth2);
		$plugins = [];
		if (empty($oauth)) {
			return $plugins;
		}
		$list = \think\Db::name("plugin")->where(["module" => $this->modules])->order("order", "asc")->select();
		foreach ($list as $plugin) {
			$plugins[$plugin["name"]] = $plugin;
			$plugins[$plugin["name"]]["dirName"] = $plugin["name"];
			$plugins[$plugin["name"]]["name"] = $plugin["title"];
		}
		foreach ($oauth as $k => $pluginDir) {
			$class = cmf_get_oauthPlugin_class_shd($pluginDir, $this->modules);
			if (!class_exists($class)) {
				unset($oauth[$k]);
				continue;
			}
			$obj = new $class();
			$meta = $obj->meta();
			if (!isset($plugins[$pluginDir])) {
				$plugins[$pluginDir] = $meta;
				$plugins[$pluginDir]["module"] = $this->modules;
				if ($plugins[$pluginDir]) {
					$plugins[$pluginDir]["status"] = 3;
					$plugins[$pluginDir]["title"] = $plugins[$pluginDir]["name"];
					$plugins[$pluginDir]["dirName"] = $pluginDir;
				}
			}
			$plugins[$pluginDir]["url"] = $meta["logo_url"];
		}
		$i = 0;
		foreach ($plugins as $k => $v) {
			if (!file_exists(WEB_ROOT . "plugins/oauth/{$v["dirName"]}")) {
				unset($plugins[$k]);
				continue;
			}
			$pluginsa[$i] = $v;
			if (in_array($v["dirName"], $oauth2)) {
				$oauth_img = WEB_ROOT . "plugins/oauth/{$v["dirName"]}/{$v["url"]}";
			}
			if (in_array($v["dirName"], $oauth)) {
				$oauth_img = CMF_ROOT . "modules/oauth/{$v["dirName"]}/{$v["url"]}";
			}
			if (stripos($oauth_img, ".svg") === false) {
				$pluginsa[$i]["img"] = "<img width=30 height=30 src=\"" . base64EncodeImage($oauth_img) . "\" />";
			} else {
				$pluginsa[$i]["img"] = file_get_contents($oauth_img);
			}
			$i++;
			unset($plugins[$k]["url"]);
			unset($plugins[$k]["logo_url"]);
			unset($plugins[$k]["config"]);
		}
		try {
			$result_data = (new \app\admin\model\PluginModel())->getNewVersion();
		} catch (\Throwable $e) {
			$result_data = [];
		}
		foreach ($pluginsa as $key => &$val) {
			$val["update_btn"] = 0;
			$val["update_disable"] = 1;
			$val["app_version"] = "";
			$val["version"] = "";
			$val["app_id"] = 0;
			if (!isset($result_data[$val["dirName"]])) {
				continue;
			}
			$val["app_version"] = $result_data[$val["dirName"]]["app_version"];
			$val["version"] = $result_data[$val["dirName"]]["old_version"];
			$val["app_id"] = $result_data[$val["dirName"]]["id"];
			if (version_compare($result_data[$val["dirName"]]["app_version"], $result_data[$val["dirName"]]["old_version"], ">")) {
				$val["update_btn"] = 1;
			}
			if ($result_data[$val["dirName"]]["nextduedate"] == 0) {
				$val["update_disable"] = 0;
				continue;
			}
			if ($result_data[$val["dirName"]]["nextduedate"] > time()) {
				$val["update_disable"] = 0;
			}
		}
		return jsonrule(["data" => $pluginsa, "status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * @title 激活三方登录
	 * @description 接口说明:激活三方登录
	 * 时间 2020/11/30
	 * @author xionglingyuan
	 * @url /admin/oauth/active
	 * @method POST
	 * @param .name:dirName type:string require:1 default: other: desc:三方登录模块目录名称
	 */
	public function active(\think\Request $request)
	{
		$zjmf_authorize = configuration("zjmf_authorize");
		if (empty($zjmf_authorize)) {
			$app = [];
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
			if (time() > $auth["last_license_time"] + 1296000 || ltrim(str_replace("https://", "", str_replace("http://", "", $auth["domain"])), "www.") != ltrim(str_replace("https://", "", str_replace("http://", "", $_SERVER["HTTP_HOST"])), "www.") || $auth["installation_path"] != CMF_ROOT || $auth["license"] != configuration("system_license")) {
				$app = [];
			} else {
				$app = $auth["app"];
			}
		}
		if (!in_array("Oauth", $app)) {
			return jsonrule(["status" => 400, "msg" => "免费版该功能不可用"]);
		}
		$param = $request->param();
		if (!$param["dirName"]) {
			return jsonrule(["status" => 400, "msg" => lang("ILLEGAL_PARAM")]);
		}
		$dirName = $request->param("dirName", "", "trim");
		$class = cmf_get_oauthPlugin_class_shd($dirName, $this->modules);
		if (!class_exists($class)) {
			return jsonrule(["msg" => "三方登录{$dirName}不存在", "status" => 400]);
		}
		$pluginCount = \think\Db::name("plugin")->where(["name" => $dirName, "module" => $this->modules])->count();
		if ($pluginCount > 0) {
			active_log("三方登录{$meta["name"]}卸载成功");
			\think\Db::name("plugin")->where(["name" => $dirName, "module" => $this->modules])->delete();
			return jsonrule(["msg" => $meta["name"] . "卸载成功", "status" => 200]);
		}
		$plugin = new $class();
		$methods = get_class_methods($plugin);
		$pluginName = $dirName;
		if (in_array(lcfirst($pluginName) . "idcsmartauthorize", $methods) || in_array($pluginName . "idcsmartauthorize", $methods)) {
			$res = pluginIdcsmartauthorize($pluginName);
			if ($res["status"] != 200) {
				return jsonrule($res);
			}
		}
		$meta = $plugin->meta();
		if (!$meta["name"]) {
			return jsonrule(["msg" => $meta["title"] . "信息缺失", "status" => 400]);
		}
		$plugin = ["status" => 1, "create_time" => time(), "name" => $dirName, "title" => $meta["name"], "description" => $meta["description"], "url" => $meta["logo_url"] ? $meta["logo_url"] : "", "author" => $meta["author"], "version" => $meta["version"] ?: 0, "config" => "", "module" => $this->modules];
		$sub_id = \think\Db::name("plugin")->insert($plugin);
		active_log("三方登录{$meta["name"]}激活成功");
		return jsonrule(["msg" => $meta["name"] . "激活成功", "status" => 200]);
	}
	/**
	 * @title 三方登录接口配置信息
	 * @description 接口说明:三方登录接口配置信息，返回在程序中自定义的配置项
	 * 时间 2020/11/30
	 * @author xionglingyuan
	 * @url /admin/oauth/config
	 * @method GET
	 */
	public function config(\think\Request $request)
	{
		$pluginDbAll = \think\Db::name("plugin")->where(["module" => $this->modules])->select();
		foreach ($pluginDbAll as $pluginDbk => $pluginDb) {
			if ($pluginDb) {
				$configDb = json_decode($pluginDb["config"], true);
			}
			$class = cmf_get_oauthPlugin_class_shd($pluginDb["name"], $this->modules);
			if (!class_exists($class)) {
				unset($pluginDbAll[$pluginDbk]);
				continue;
			}
			$obj = new $class();
			$config = $obj->config();
			$plugins[$pluginDbk]["dirName"] = $pluginDb["name"];
			$plugins[$pluginDbk]["name"] = $pluginDb["title"];
			$plugins[$pluginDbk]["status"] = $pluginDb["status"];
			$plugins[$pluginDbk]["callback_url"] = "回调地址：" . $this->domain . "/oauth/callback/{$pluginDb["name"]}";
			foreach ($config as $k => $v) {
				$plugins[$pluginDbk]["config"][$k]["value"] = $configDb[$v["name"]] ? $configDb[$v["name"]] : "";
				$plugins[$pluginDbk]["config"][$k]["type"] = $v["type"];
				$plugins[$pluginDbk]["config"][$k]["name"] = $v["name"];
				$plugins[$pluginDbk]["config"][$k]["desc"] = $v["desc"];
			}
		}
		return jsonrule(["data" => $plugins, "status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * @title 保存三方登录接口参数
	 * @description 接口说明:保存三方登录接口参数
	 * 时间 2020/11/30
	 * @author xionglingyuan
	 * @url /admin/oauth/config_post
	 * @method POST
	 * @param .name:dirName type:string require:1 default: other: desc:三方登录名称
	 * @param .name:app[] type:array require:1 default: other: desc:三方登录接口参数一维数组，接口配置都提交过来
	 */
	public function configSave(\think\Request $request)
	{
		$zjmf_authorize = configuration("zjmf_authorize");
		if (empty($zjmf_authorize)) {
			$app = [];
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
			if (time() > $auth["last_license_time"] + 1296000 || ltrim(str_replace("https://", "", str_replace("http://", "", $auth["domain"])), "www.") != ltrim(str_replace("https://", "", str_replace("http://", "", $_SERVER["HTTP_HOST"])), "www.") || $auth["installation_path"] != CMF_ROOT || $auth["license"] != configuration("system_license")) {
				$app = [];
			} else {
				$app = $auth["app"];
			}
		}
		if (!in_array("Oauth", $app)) {
			return jsonrule(["status" => 400, "msg" => "免费版该功能不可用"]);
		}
		$param = $request->param();
		$dirName = $request->param("dirName", "", "trim");
		if (!$dirName) {
			return jsonrule(["status" => 400, "msg" => lang("ILLEGAL_PARAM")]);
		}
		$class = cmf_get_oauthPlugin_class_shd($dirName, $this->modules);
		$obj = new $class();
		$pluginName = $dirName;
		$methods = get_class_methods($obj);
		if (in_array(lcfirst($pluginName) . "idcsmartauthorize", $methods) || in_array($pluginName . "idcsmartauthorize", $methods)) {
			$res = pluginIdcsmartauthorize($pluginName);
			if ($res["status"] != 200) {
				return jsonrule($res);
			}
		}
		$pluginDb = \think\Db::name("plugin")->where(["module" => $this->modules, "name" => $dirName])->field("id")->find();
		if (!$pluginDb) {
			return jsonrule(["msg" => "三方登录{$dirName}未激活", "status" => 400]);
		}
		$config = $param["app"] ? json_encode($param["app"]) : "";
		\think\Db::name("plugin")->where(["module" => $this->modules, "name" => $dirName])->update(["config" => $config]);
		active_log("三方登录{$dirName}接口参数保存成功");
		return jsonrule(["msg" => "三方登录{$dirName}接口参数保存成功", "status" => 200]);
	}
	/**
	 * @title 停用
	 * @description 接口说明:停用三方登录接口
	 * 时间 2020/11/30
	 * @author xionglingyuan
	 * @url /admin/oauth/suspend
	 * @method POST
	 * @param .name:dirName type:string require:1 default: other: desc:三方登录名称
	 */
	public function suspend(\think\Request $request)
	{
		$zjmf_authorize = configuration("zjmf_authorize");
		if (empty($zjmf_authorize)) {
			$app = [];
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
			if (time() > $auth["last_license_time"] + 1296000 || ltrim(str_replace("https://", "", str_replace("http://", "", $auth["domain"])), "www.") != ltrim(str_replace("https://", "", str_replace("http://", "", $_SERVER["HTTP_HOST"])), "www.") || $auth["installation_path"] != CMF_ROOT || $auth["license"] != configuration("system_license")) {
				$app = [];
			} else {
				$app = $auth["app"];
			}
		}
		if (!in_array("Oauth", $app)) {
			return jsonrule(["status" => 400, "msg" => "免费版该功能不可用"]);
		}
		$param = $request->param();
		$dirName = $request->param("dirName", "", "trim");
		if (!$dirName) {
			return jsonrule(["status" => 400, "msg" => lang("ILLEGAL_PARAM")]);
		}
		$pluginDb = \think\Db::name("plugin")->where(["module" => $this->modules, "name" => $dirName])->field("id,status")->find();
		if (!$pluginDb) {
			return jsonrule(["msg" => "三方登录{$dirName}未激活", "status" => 400]);
		}
		if ($pluginDb["status"] == 1) {
			$update = ["status" => 0];
			$info = "三方登录{$dirName}接口暂停成功";
		} else {
			$info = "三方登录{$dirName}接口开启成功";
			$update = ["status" => 1];
		}
		\think\Db::name("plugin")->where(["module" => $this->modules, "name" => $dirName])->update($update);
		active_log($info);
		return jsonrule(["msg" => $info, "status" => 200]);
	}
}