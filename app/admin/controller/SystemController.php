<?php

namespace app\admin\controller;

/**
 * @title 系统相关
 * @description 接口描述
 */
class SystemController extends AdminBaseController
{
	private $auth_url = "https://license.soft13.idcsmart.com";
	public function initialize()
	{
		parent::initialize();
		$this->auth_url = config("auth_url");
	}
	/**
	 * @title 获取系统信息
	 * @description 获取系统信息
	 * @author xiong
	 * @url    admin/system/commoninfo
	 * @method GET
	 * @time   2021-06-23
	 */
	public function getcommoninfo()
	{
		$data["license_type"] = intval(\is_profession());
		return jsonrule(["status" => 200, "data" => $data]);
	}
	/**
	 * @title 获取版本更新内容
	 * @description 获取版本更新内容
	 * @author wyh
	 * @url    admin/system/updatecontent
	 * @method GET
	 * @time   2020-09-10
	 */
	public function getUpdateContent()
	{
		$version = getZjmfVersion();
		$upgrade_system_logic = new \app\common\logic\UpgradeSystem();
		$last_version = $upgrade_system_logic->getHistoryVersion();
		$str = "";
		if (version_compare($last_version["last"], $version, ">=")) {
			$arr = $upgrade_system_logic->diffVersion($last_version["last"], $version);
			$arr = array_reverse($arr);
			array_shift($arr);
			$str = file_get_contents($this->auth_url . "/upgrade/{$last_version["last"]}.php");
			if ($arr) {
				$str .= "<h1>历史更新</h1>";
				foreach ($arr as $v) {
					$str .= file_get_contents($this->auth_url . "/upgrade/{$v}.php");
				}
			}
		}
		return jsonrule(["status" => 200, "data" => mb_convert_encoding(iconv("utf-8", "gbk//IGNORE", $str), "utf-8", "GBK")]);
	}
	/**
	 * @title 系统信息
	 * @description 系统信息
	 * @author 上官刃
	 * @url    admin/system/info
	 * @method GET
	 * @time   2019-11-27
	 * @return server_ip:服务器ip
	 * @return server_name:域名
	 * @return server_port:端口号
	 * @return server_version:服务器系统版本
	 * @return server_system: 服务器操作系统
	 * @return php_version:php版本
	 * @return include_path:获取PHP安装路径
	 * @return php_sapi_name:PHP运行方式
	 * @return now_time:服务器时间
	 * @return upload_max_filesize:服务器上传限制
	 * @return max_execution_time:服务脚本最大执行时间
	 * @return memory_limit:内存占用
	 * @return processor_identifier:脚本运行占用最大内存
	 * @return system_root:系统根目录
	 * @return http_accept_language:获取服务器语言
	 * @return system_token:系统唯一识别码
	 * @return install_version:系统当前版本
	 * @return last_version:系统最新版本
	 * @return mysql_version:数据库版本
	 * @return system_version_type:版本类型：beta测试版 stable稳定版
	 * @return zjmf_system_version_type_last:当前版本：beta测试版 stable稳定版
	 */
	public function getInfo()
	{
		$mysql_version = (array) \think\Db::query("select VERSION()");
		$mysql_version = $mysql_version[0]["VERSION()"] ? str_replace("-log", "", $mysql_version[0]["VERSION()"]) : "获取数据库版本失败";
		$zjmf_authorize = configuration("zjmf_authorize");
		if (empty($zjmf_authorize)) {
			$auth_status = "";
			$auth_suspend_reason = "";
			$auth_app = [];
			$auth_due_time = "";
			$service_due_time = "";
		} else {
			$_strcode = _strcode($zjmf_authorize, "DECODE", "zjmf_key_strcode");
			$_strcode = explode("|zjmf|", $_strcode);
			$authkey = "-----BEGIN PUBLIC KEY-----\nMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDg6DKmQVwkQCzKcFYb0BBW7N2f\nI7DqL4MaiT6vibgEzH3EUFuBCRg3cXqCplJlk13PPbKMWMYsrc5cz7+k08kgTpD4\ntevlKOMNhYeXNk5ftZ0b6MAR0u5tiyEiATAjRwTpVmhOHOOh32MMBkf+NNWrZA/n\nzcLRV8GU7+LcJ8AH/QIDAQAB\n-----END PUBLIC KEY-----";
			$pu_key = openssl_pkey_get_public($authkey);
			foreach ($_strcode as $v) {
				openssl_public_decrypt(base64_decode($v), $de, $pu_key);
				$de_str .= $de;
			}
			$auth = json_decode($de_str, true);
			$auth_status = $auth["status"];
			$auth_suspend_reason = $auth["suspend_reason"];
			$auth_app = $auth["app"];
			$service_due_time = !empty($auth["due_time"]) ? $auth["due_time"] : date("Y-m-d H:i:s", strtotime($auth["create_time"]) + 31536000);
			$auth_due_time = !empty($auth["auth_due_time"]) ? $auth["auth_due_time"] : "2039-12-31 23:59:59";
		}
		$data = ["server_ip" => !!configuration("authsystemip") ? \de_systemip(configuration("authsystemip")) : gethostbyname($_SERVER["SERVER_NAME"]), "server_name" => $_SERVER["SERVER_NAME"], "server_port" => $_SERVER["SERVER_PORT"], "server_version" => php_uname("s") . php_uname("r"), "server_system" => php_uname(), "php_version" => PHP_VERSION, "include_path" => DEFAULT_INCLUDE_PATH, "php_sapi_name" => php_sapi_name(), "now_time" => date("Y-m-d H:i:s"), "upload_max_filesize" => get_cfg_var("upload_max_filesize"), "max_execution_time" => get_cfg_var("max_execution_time") . "秒 ", "memory_limit" => get_cfg_var("memory_limit") ? get_cfg_var("memory_limit") : "无", "processor_identifier" => ini_get("memory_limit"), "system_root" => CMF_ROOT, "http_accept_language" => $_SERVER["HTTP_ACCEPT_LANGUAGE"] ?? "", "system_token" => \think\Db::name("configuration")->where("setting", "system_token")->value("value") ?? "", "install_version" => getZjmfVersion(), "mysql_version" => $mysql_version, "system_version_type" => configuration("system_version_type") ?? "stable", "zjmf_system_version_type_last" => configuration("zjmf_system_version_type_last") ?? "stable", "system_license" => configuration("system_license") ?? "", "auth_status" => $auth_status, "auth_suspend_reason" => $auth_suspend_reason, "auth_app" => $auth_app, "auth_due_time" => $auth_due_time, "service_due_time" => $service_due_time];
		return jsonrule(["status" => 200, "data" => $data]);
	}
	/**
	 * @title 系统信息:最新版本号,授权类型
	 * @description 系统信息:curl接口拆分
	 * @author wyh
	 * @url    admin/system/lastversion
	 * @method GET
	 * @time   2020-11-19
	 * @return last_version:系统最新版本
	 * @return last_version_check:no_response时 ，表示未检测到最新版本
	 * @return license_type:授权类型:0免费版，1专业版
	 */
	public function getLastVersion()
	{
		$zjmf_authorize = configuration("zjmf_authorize");
		if (empty($zjmf_authorize)) {
			$getEdition = "error";
		} else {
			$_strcode = _strcode($zjmf_authorize, "DECODE", "zjmf_key_strcode");
			$_strcode = explode("|zjmf|", $_strcode);
			$authkey = "-----BEGIN PUBLIC KEY-----\nMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDg6DKmQVwkQCzKcFYb0BBW7N2f\nI7DqL4MaiT6vibgEzH3EUFuBCRg3cXqCplJlk13PPbKMWMYsrc5cz7+k08kgTpD4\ntevlKOMNhYeXNk5ftZ0b6MAR0u5tiyEiATAjRwTpVmhOHOOh32MMBkf+NNWrZA/n\nzcLRV8GU7+LcJ8AH/QIDAQAB\n-----END PUBLIC KEY-----";
			$pu_key = openssl_pkey_get_public($authkey);
			foreach ($_strcode as $v) {
				openssl_public_decrypt(base64_decode($v), $de, $pu_key);
				$de_str .= $de;
			}
			$auth = json_decode($de_str, true);
			$getEdition = intval($auth["edition"]);
		}
		$upgrade_system_logic = new \app\common\logic\UpgradeSystem();
		$last_version = $upgrade_system_logic->getLastVersion();
		if ($last_version["status"] && $last_version["status"] == 400) {
			$last_version = "未检测到最新版本";
			$last_version_check = "no_response";
		}
		$data = ["last_version" => $last_version, "last_version_check" => $last_version_check ?: "", "license_type" => $getEdition];
		return jsonrule(["status" => 200, "data" => $data]);
	}
	/**
	 * @title php信息
	 * @description php信息
	 * @author 上官刃
	 * @url    admin/system/phpinfo
	 * @method GET
	 * @time   2019-12-28
	 * @return report_array:php信息html
	 */
	public function getPhpInfo()
	{
		ob_start();
		phpinfo();
		$info = ob_get_contents();
		ob_end_clean();
		$info = preg_replace("%^.*<body>(.*)</body>.*\$%ms", "\$1", $info);
		ob_start();
		echo "<style type=\"text/css\">
.e {background-color: #EFF2F9; font-weight: bold; color: #000000;}
.v {background-color: #efefef; color: #000000;}
.vr {background-color: #efefef; text-align: right; color: #000000;}
hr {width: 600px; background-color: #cccccc; border: 0px; height: 1px; color: #000000;}
</style>
";
		echo $info;
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}
	/**
	 * @title 数据库状态数据
	 * @description 数据库状态数据
	 * @author 萧十一郎
	 * @url    admin/system/databaseinfo
	 * @method GET
	 * @time   2019-12-28
	 * @return report_array:表数据@
	 * @report_array  name:表名称
	 * @report_array  rows:表行数
	 * @report_array  size:表大小
	 * @return total_count:总表数
	 * @return total_rows:总行数
	 * @return total_size:总大小
	 */
	public function getDatabaseInfo()
	{
		$table_data = \think\Db::query("SHOW TABLE STATUS");
		$returndata = [];
		$report_array = [];
		$i = 0;
		$totalrows = 0;
		$size = 0;
		foreach ($table_data as $key => $val) {
			$name = $val["Name"];
			$rows = $val["Rows"];
			$datalen = $val["Data_length"];
			$indexlen = $val["Index_length"];
			$totalsize = $datalen + $indexlen;
			$totalrows += $rows;
			$size += $totalsize;
			$report_array[] = ["name" => $name, "rows" => $rows, "size" => round($totalsize / 1024, 2) . " kb"];
			$i++;
		}
		$returndata["report_array"] = $report_array;
		$returndata["total_count"] = $i;
		$returndata["total_rows"] = $totalrows;
		$returndata["total_size"] = round($size / 1024, 2) . " kb";
		return jsonrule(["status" => 200, "data" => $returndata]);
	}
	/**
	 * @title 优化数据表
	 * @description 清除数据表空洞，OPTIMIZE TABLE
	 * @author 萧十一郎
	 * @url    admin/system/optimizetables
	 * @method GET
	 * @time   2019-12-28
	 */
	public function postOptimizeTables()
	{
		try {
			$table_list = $this->listTables();
			$this->optimizeTables($table_list);
			active_log($this->lang["System_admin_postOptimizeTables"]);
		} catch (\Exception $e) {
		}
		return jsonrule(["status" => 200, "msg" => "执行成功"]);
	}
	/**
	 * @title 下载数据库备份
	 * @description 下载数据库备份
	 * @author 萧十一郎
	 * @url    admin/system/downdatabackup
	 * @method POST
	 * @time   2019-12-28
	 */
	public function postDownDataBackup()
	{
		$backup_class = new \app\admin\lib\Backup(DATABASE_DOWN_PATH, config("database.database"));
		try {
			active_log($this->lang["System_admin_postDownDataBackup"]);
			$filename = $backup_class->backupAll();
			\ob_clean();
			header("Access-Control-Expose-Headers: Content-disposition");
			header("File_name: " . $filename);
			if (file_exists(DATABASE_DOWN_PATH . $filename)) {
				return download(DATABASE_DOWN_PATH . $filename, $filename);
			} else {
				return jsonrule(["status" => "400", "msg" => "没有此文件"]);
			}
		} catch (\Exception $e) {
			return jsonrule(["status" => "406", "msg" => $e->getMessage()]);
		}
	}
	/**
	 * @title 切换版本
	 * @description 在正式版stable、测试版beta之间切换
	 * @author wyh
	 * @url    admin/system/toggleversion
	 * @param  .name:type type:string require:1 default: desc: 版本类型:正式版stable、测试版beta
	 * @method POST
	 * @time   2020-08-25
	 */
	public function postToggleVersion()
	{
		$params = $this->request->param();
		$version = $params["type"] ?? "stable";
		if (!in_array($version, ["stable", "beta"])) {
			$version = "stable";
		}
		$system_license = configuration("system_license");
		postRequest($this->auth_url . "/app/api/toggle_version", ["license" => $system_license, "type" => $version, "token" => config("auth_token")]);
		updateConfiguration("system_version_type", $version);
		return jsonrule(["status" => 200, "msg" => "版本切换成功"]);
	}
	/**
	 * @title 更新系统
	 * @description 更新系统:下载zip包，解压,执行sql语句,覆盖系统原文件
	 * @author wyh
	 * @url    admin/system/autoupdate
	 * @method GET
	 * @time   2020-06-02
	 */
	public function getAutoUpdate()
	{
		if (!extension_loaded("ionCube Loader")) {
			return jsonrule(["status" => 400, "msg" => "请先安装ionCube扩展"]);
		}
		$zjmf_authorize = configuration("zjmf_authorize");
		if (empty($zjmf_authorize)) {
			\compareLicense();
		}
		if (time() > configuration("last_license_time") + 86400) {
			\compareLicense();
		}
		$zjmf_authorize = configuration("zjmf_authorize");
		if (empty($zjmf_authorize)) {
			return jsonrule(["status" => 307, "msg" => "授权错误,请检查域名或ip"]);
		} else {
			$auth = \de_authorize($zjmf_authorize);
			$ip = \de_systemip(configuration("authsystemip"));
			if (time() > $auth["last_license_time"] + 604800 && time() > $auth["license_error_time"] + 60) {
				\compareLicense();
				$zjmf_authorize = configuration("zjmf_authorize");
				$auth = \de_authorize($zjmf_authorize);
				updateConfiguration("license_error_time", time());
			}
			if ($ip != $auth["ip"] && !empty($ip)) {
				return jsonrule(["status" => 307, "msg" => "授权错误,请检查ip"]);
			}
			if (time() > $auth["last_license_time"] + 604800 || ltrim(str_replace("https://", "", str_replace("http://", "", $auth["domain"])), "www.") != ltrim(str_replace("https://", "", str_replace("http://", "", $_SERVER["HTTP_HOST"])), "www.") || $auth["installation_path"] != CMF_ROOT || $auth["license"] != configuration("system_license")) {
				return jsonrule(["status" => 307, "msg" => "授权错误,请检查域名或ip"]);
			}
			if (!empty($auth["facetoken"])) {
				return jsonrule(["status" => 307, "msg" => "您的授权已被暂停,请前往智简魔方会员中心检查授权状态"]);
			}
			if ($auth["status"] == "Suspend") {
				return jsonrule(["status" => 307, "msg" => "您的授权已被暂停,请前往智简魔方会员中心检查授权状态"]);
			}
			if (!empty($auth["due_time"]) && $auth["due_time"] < time() && $auth["edition"] == 1) {
				return jsonrule(["status" => 307, "msg" => "您的升级与支持服务已到期，无法升级"]);
			}
		}
		ini_set("max_execution_time", 3600);
		cache("upgrade_system_start", time(), 3600);
		session_write_close();
		if (!configuration("update_dcim_only_once")) {
			$this->updateDcim();
		}
		updateConfiguration("update_dcim_only_once", 1);
		$upgrade_system_logic = new \app\common\logic\UpgradeSystem();
		$upgrade_system_logic->upload();
		updateConfiguration("zjmf_system_version_type_last", configuration("system_version_type"));
		$src = WEB_ROOT . "themes";
		$dst = WEB_ROOT . "themes/web";
		$out = ["cart", "clientarea", "web"];
		$res = recurse_copy($src, $dst, $out);
		if ($res["status"] == 200) {
			deleteDir($src, $out);
		}
		\compareLicense();
	}
	/**
	 * @title 检测系统更新进度
	 * @description 前端定时调用此接口,获取系统更新最新进度
	 * @author wyh
	 * @url    admin/system/checkautoupdate
	 * @method GET
	 * @time   2020-07-02
	 */
	public function getCheckAutoUpdate()
	{
		if (time() - cache("upgrade_system_start") > get_cfg_var("max_execution_time")) {
			return jsonrule(["status" => 400, "msg" => "请求接口超时"]);
		}
		$upgrade_system_logic = new \app\common\logic\UpgradeSystem();
		$upload_dir = $upgrade_system_logic->upload_dir;
		$data = [];
		$data["progress"] = "0%";
		$data["msg"] = "检测根目录权限";
		$data["status"] = 200;
		if (!is_readable($upload_dir) || !shd_new_is_writeable($upload_dir)) {
			$data["progress"] = "10%";
			$data["msg"] = "根目录不可读/写";
			$data["status"] = 400;
		}
		$progress_log = $upgrade_system_logic->progress_log;
		$timeout = ["http" => ["timeout" => 10]];
		$ctx = stream_context_create($timeout);
		$handle = fopen($progress_log, "r", false, $ctx);
		if (!$handle) {
			return json(["status" => 400, "msg" => "升级失败"]);
		}
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
		$last = array_pop($arr);
		$data = json_decode($last, true);
		if (($data["progress"] == "20%" || $data["progress"] == "50%") && $data["status"] == 200) {
			$file_name = $data["file_name"];
			$origin_size = $data["origin_size"];
			$moment_size = filesize(CMF_ROOT . $file_name);
			$moment_size = bcdiv($moment_size, 1048576, 2);
			$data["progress"] = bcmul(0.2 + 0.3 * bcdiv($moment_size, $origin_size, 2), 100, 2) . "%";
			$data["msg"] = $data["msg"] . ";已下载{$moment_size}MB";
			unset($data["file_name"]);
			unset($data["origin_size"]);
		}
		$upgrade_system_logic->updateUnzip();
		$upgrade_system_logic->updateCopy();
		return json($data);
	}
	private function listTables()
	{
		$tables = \think\Db::query("SHOW TABLES");
		$tableArray = [];
		foreach ($tables as $table) {
			$tableArray[] = $table[0];
		}
		return $tableArray;
	}
	private function optimizeTables(array $tables)
	{
		$optimisedTables = [];
		try {
			foreach ($tables as $table) {
				$statement = "OPTIMIZE TABLE `" . $table . "`;";
				\think\Db::query($statement);
				$optimisedTables[] = $table;
			}
		} catch (\Exception $e) {
			$tableList = implode(", ", $optimisedTables);
			$exceptionMessage = "Optimising table failed.";
			if ($tableList) {
				$exceptionMessage .= " Successfully optimised tables are: " . $tableList;
			}
			throw new \Exception($exceptionMessage);
		}
	}
	public function updateDcim()
	{
		$svg = [1 => "Windows", 2 => "CentOS", 3 => "Ubuntu", 4 => "Debian", 5 => "ESXi", 6 => "XenServer", 7 => "FreeBSD", 8 => "Fedora", 9 => "其他"];
		$server = \think\Db::name("servers")->alias("a")->field("a.id,a.gid,b.area,b.os")->leftJoin("dcim_servers b", "a.id=b.serverid")->where("a.server_type", "dcim")->select()->toArray();
		if (empty($server)) {
			return false;
		}
		$group_area = [];
		$group_os = [];
		$product_area_config = [];
		$product_os_config = [];
		$price = [];
		foreach ($server as $k => $v) {
			$v["area"] = json_decode($v["area"], true);
			if (!empty($v["area"])) {
				foreach ($v["area"] as $vv) {
					$group_area[$v["gid"]][] = ["id" => $vv["id"], "option_name" => $vv["id"] . "|" . $vv["area"] . "^" . ($vv["name"] ?: $vv["area"])];
				}
			}
			$v["os"] = json_decode($v["os"], true);
			$os_group = array_column($v["os"]["group"], "svg", "id");
			foreach ($v["os"]["os"] as $vv) {
				$group_os[$v["gid"]][] = ["id" => $vv["id"], "option_name" => $vv["id"] . "|" . $svg[$os_group[$vv["group_id"]]] . "^" . $vv["name"]];
			}
		}
		$products = \think\Db::name("products")->field("id,server_group")->where("type", "dcim")->whereIn("server_group", array_column($server, "gid"))->whereIn("api_type", ["", "normal"])->select()->toArray();
		if (empty($products)) {
			return false;
		}
		foreach ($products as $k => $v) {
			$gid = \think\Db::name("product_config_links")->where("pid", $v["id"])->value("gid");
			if (empty($gid)) {
				continue;
			}
			$configid = \think\Db::name("product_config_options")->where("gid", $gid)->where("option_type", 12)->value("id");
			if (empty($configid)) {
				$configid = \think\Db::name("product_config_options")->insertGetId(["gid" => $gid, "option_name" => "area|区域", "option_type" => 12, "qty_minimum" => 0, "qty_maximum" => 0, "order" => 0, "hidden" => 0, "upgrade" => 0, "upstream_id" => 0]);
			}
			$product_area_config[$v["id"]] = $configid;
			foreach ($group_area[$v["server_group"]] as $vv) {
				$is_add = \think\Db::name("product_config_options_sub")->where("config_id", $configid)->whereLike("option_name", $vv["id"] . "|%")->value("id");
				if ($is_add) {
					continue;
				}
				$sub_id = \think\Db::name("product_config_options_sub")->insertGetId(["config_id" => $configid, "qty_minimum" => 0, "qty_maximum" => 0, "option_name" => $vv["option_name"]]);
				$pricing[] = ["type" => "configoptions", "relid" => $sub_id, "monthly" => 0];
			}
			$configid = \think\Db::name("product_config_options")->where("gid", $gid)->where("option_type", 5)->value("id");
			if (empty($configid)) {
				$configid = \think\Db::name("product_config_options")->insertGetId(["gid" => $gid, "option_name" => "os|操作系统", "option_type" => 5, "qty_minimum" => 0, "qty_maximum" => 0, "order" => 1, "hidden" => 0, "upgrade" => 0, "upstream_id" => 0]);
			}
			$product_os_config[$v["id"]] = $configid;
			foreach ($group_os[$v["server_group"]] as $vv) {
				$is_add = \think\Db::name("product_config_options_sub")->where("config_id", $configid)->whereLike("option_name", $vv["id"] . "|%")->value("id");
				if ($is_add) {
					continue;
				}
				$sub_id = \think\Db::name("product_config_options_sub")->insertGetId(["config_id" => $configid, "qty_minimum" => 0, "qty_maximum" => 0, "option_name" => $vv["option_name"]]);
				$pricing[] = ["type" => "configoptions", "relid" => $sub_id, "monthly" => 0];
			}
		}
		if (!empty($pricing)) {
			$currency = \think\Db::name("currencies")->column("id");
			foreach ($currency as $v) {
				foreach ($pricing as $kk => $vv) {
					$pricing[$kk]["currency"] = $v;
				}
				\think\Db::name("pricing")->data($pricing)->limit(50)->insertAll();
			}
			unset($pricing);
		}
		$product_ids = array_column($products, "id");
		$host = \think\Db::name("host")->alias("a")->field("a.id,a.productid,a.dcim_area,a.os,b.gid")->leftJoin("servers b", "a.serverid=b.id")->whereIn("a.productid", $product_ids)->select()->toArray();
		foreach ($host as $v) {
			if (!empty($product_area_config[$v["productid"]])) {
				$sub_id = \think\Db::name("product_config_options_sub")->where("config_id", $product_area_config[$v["productid"]])->whereLike("option_name", $v["dcim_area"] . "|%")->value("id");
				if (!empty($sub_id)) {
					$r = \think\Db::name("host_config_options")->where("relid", $v["id"])->where("configid", $product_area_config[$v["productid"]])->value("id");
					if (!empty($r)) {
						\think\Db::name("host_config_options")->where("id", $r)->update(["optionid" => $sub_id, "qty" => 0]);
					} else {
						\think\Db::name("host_config_options")->insert(["relid" => $v["id"], "configid" => $product_area_config[$v["productid"]], "optionid" => $sub_id, "qty" => 0]);
					}
				}
			}
			if (!empty($product_os_config[$v["productid"]])) {
				$sub = \think\Db::name("product_config_options_sub")->field("id,option_name")->where("config_id", $product_os_config[$v["productid"]])->whereLike("option_name", "%" . $v["os"] . "%")->find();
				if (!empty($sub)) {
					$r = \think\Db::name("host_config_options")->where("relid", $v["id"])->where("configid", $product_os_config[$v["productid"]])->value("id");
					if (!empty($r)) {
						\think\Db::name("host_config_options")->where("id", $r)->update(["optionid" => $sub["id"], "qty" => 0]);
					} else {
						\think\Db::name("host_config_options")->insert(["relid" => $v["id"], "configid" => $product_os_config[$v["productid"]], "optionid" => $sub["id"], "qty" => 0]);
					}
					$os_url = explode("^", explode($sub["option_name"], "|")[1])[0] ?: "";
					\think\Db::name("host")->strict(false)->where("id", $v["id"])->update(["os_url" => $os_url]);
				}
			}
		}
		return true;
	}
	/**
	 * @title 更换授权码
	 * @description 更换授权码
	 * @author xj
	 * @url    admin/system/authorize
	 * @method GET
	 * @time   2020-11-17
	 */
	public function getAuthorize()
	{
		$res = \compareLicense();
		if ($res === false) {
			return json(["status" => 400, "msg" => "授权获取失败, 无法连接到授权服务器, 请检查网络"]);
		}
		if ($res["status"] == 400) {
			return json(["status" => 400, "msg" => "授权获取失败, 授权码错误"]);
		}
		if ($res["status"] == 401) {
			return json(["status" => 400, "msg" => "授权获取失败, 该授权已使用, 请重置授权后重试"]);
		}
		return json(["status" => 200, "msg" => "授权获取成功"]);
	}
	/**
	 * @title 更换授权码
	 * @description 更换授权码
	 * @author xj
	 * @url    admin/system/license
	 * @method PUT
	 * @param  .name:license type:string require:1 default: desc:授权码
	 * @time   2020-11-17
	 */
	public function putLicense()
	{
		$params = $this->request->param();
		$license = $params["license"] ?? "";
		if (empty($license)) {
			return json(["status" => 400, "msg" => "授权码不能为空"]);
		}
		\think\Db::startTrans();
		try {
			\think\Db::name("configuration")->where("setting", "system_license")->update(["value" => $license]);
			$res = \compareLicense();
			if ($res === false) {
				throw new \Exception("授权更换失败, 无法连接到授权服务器, 请检查网络");
			}
			if ($res["status"] == 400) {
				throw new \Exception("授权更换失败, 授权码错误");
			}
			if ($res["status"] == 401) {
				throw new \Exception("授权更换失败, 该授权已使用, 请重置授权后重试");
			}
			active_log("授权码修改成功");
			\think\Db::commit();
			putLicenseAfter();
			return json(["status" => 200, "msg" => "授权更换成功"]);
		} catch (\Exception $e) {
			\think\Db::rollback();
			return json(["status" => 400, "msg" => $e->getMessage()]);
		}
	}
	/**
	 * @title 数据迁移
	 * @description 数据迁移:whmcs迁移工具下载
	 * @author wyh
	 * @url    admin/system/datamigrate
	 * @method GET
	 * @time   2020-11-25
	 * @return last_version:系统最新版本
	 */
	public function getDataMigrate()
	{
		$down_url = $this->auth_url . "/tool/move.php";
		\ob_clean();
		header("Access-Control-Expose-Headers: Content-disposition");
		return download($down_url, "move.php");
	}
	/**
	 * @title 路由菜单
	 * @description 根据语言返回路由菜单
	 * @author x
	 * @url    admin/system/systemAuthRuleLanguage
	 * @method GET
	 * @return array
	 */
	public function getSystemAuthRuleLanguage()
	{
		$admin_id = cmf_get_current_admin_id();
		$AdminUserModel = new \app\admin\model\AdminUserModel();
		$data = $AdminUserModel->get_rule($admin_id);
		return json(["status" => 200, "data" => $data]);
	}
}