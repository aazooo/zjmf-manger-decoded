<?php

namespace app\api\controller;

class UpgradeSystemController extends \think\Controller
{
	/**
	 * @title 获取版本号
	 * @description 接口说明: 获取版本号
	 * @param
	 * @author x
	 * @url  api/upgrade/version
	 * @method GET
	 */
	public function sysVersion()
	{
		$is_download = 0;
		$upgrade_system_logic = new \app\common\logic\UpgradeSystem();
		$last_version = $upgrade_system_logic->getLastVersion();
		if (empty($last_version)) {
			return jsons(["status" => 400, "msg" => "未获取到本系统版本号,请稍后重试"]);
		}
		$handler = opendir(CMF_ROOT);
		while (($filename = readdir($handler)) !== false) {
			if ($filename == "." && $filename == "..") {
			} else {
				if (preg_match("/" . $last_version . "\\.zip\$/i", $filename)) {
					$is_download = 1;
				}
			}
		}
		closedir($handler);
		$tiem = filemtime($upgrade_system_logic->progress_log);
		if ($tiem < time() - 7200) {
			@unlink($upgrade_system_logic->progress_log);
		}
		$res = file_exists($upgrade_system_logic->progress_log);
		if (!$res && $is_download == 1) {
			$package_name = glob(CMF_ROOT . "*" . $last_version . ".zip");
			$package = array_pop($package_name);
			$progress_log["progress"] = "30%";
			$progress_log["file_name"] = basename($package);
			$progress_log["package_name"] = $last_version;
			$progress_log["setup"] = "unzip";
			$progress_log["msg"] = "已成功下载";
			$progress_log["status"] = 200;
			$upgrade_system_logic->update_progress(json_encode($progress_log));
			session("upgrade.system_version", $last_version);
		}
		return jsons(["status" => 200, "data" => ["version" => getZjmfVersion(), "is_download" => $is_download]]);
	}
	/**
	 * @title 更新系统
	 * @description 更新系统:下载zip包
	 * @author wyh
	 * @url  api/upgrade/autoupdate
	 * @method GET
	 * @time   2020-06-02
	 */
	public function getAutoUpdate()
	{
		$upgrade_system_logic = new \app\common\logic\UpgradeSystem();
		$last_version = $upgrade_system_logic->getLastVersion();
		if (empty($last_version)) {
			return jsons(["status" => 400, "msg" => "未获取到本系统版本号,请稍后重试"]);
		}
		$handler = opendir(CMF_ROOT);
		while (($filename = readdir($handler)) !== false) {
			if ($filename == "." && $filename == "..") {
			} else {
				if (preg_match("/" . $last_version . "\\.zip\$/i", $filename)) {
					$is_download = 1;
				}
			}
		}
		if ($is_download) {
			return jsonrule(["status" => 200, "msg" => "安装包已下载"]);
		}
		if (!extension_loaded("ionCube Loader")) {
			return jsonrule(["status" => 400, "msg" => "请先安装ionCube扩展"]);
		}
		ini_set("max_execution_time", 3600);
		cache("upgrade_system_start", time(), 3600);
		session_write_close();
		if (!configuration("update_dcim_only_once")) {
			$system = new \app\admin\controller\SystemController();
			$system->updateDcim();
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
	 * @url    api/upgrade/checkautoupdate
	 * @method GET
	 * @time   2020-07-02
	 */
	public function getCheckAutoUpdate()
	{
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
			return json(["status" => 200, "msg" => ""]);
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
		if ($data["progress"] == "20%" && $data["status"] == 200) {
			$file_name = $data["file_name"];
			$origin_size = $data["origin_size"];
			$moment_size = filesize(CMF_ROOT . $file_name);
			$moment_size = bcdiv($moment_size, 1048576, 2);
			$data["progress_msg"] = bcmul(0.2 + 0.3 * bcdiv($moment_size, $origin_size, 2), 100, 2) . "%";
			$data["msg"] = $data["msg"] . ";已下载{$moment_size}MB";
			unset($data["file_name"]);
			unset($data["origin_size"]);
		}
		return json($data);
	}
	/**
	 * @title 解压文件
	 * @description
	 * @author wyh
	 * @url    api/upgrade/checkupdateunzip
	 * @method POST
	 * @time   2020-07-02
	 */
	public function getCheckUpdateUnzip()
	{
		$upgrade_system_logic = new \app\common\logic\UpgradeSystem();
		$upgrade_system_logic->updateUnzip();
	}
	/**
	 * @title 文件升级替换
	 * @description
	 * @author wyh
	 * @url    api/upgrade/checkupdatecopy
	 * @method POST
	 * @time   2020-07-02
	 */
	public function getCheckUpdateCopy()
	{
		$upgrade_system_logic = new \app\common\logic\UpgradeSystem();
		$upgrade_system_logic->updateCopy();
	}
	/**
	 * @title 更新数据库(文件覆盖后调用此方法)
	 * @description 更新数据库:文件覆盖后调用此方法
	 * @author wyh
	 * @url    api/upgrade/sqlupdate
	 * @method GET
	 * @time   2020-06-04
	 */
	public function getSqlUpdate()
	{
		$version = configuration("update_last_version");
		if (empty($version)) {
			return jsonrule(["status" => 400, "msg" => "未获取到本系统版本号,请联系系统管理员"]);
		}
		$defaultCharset = "utf8mb4";
		$charset = config("database.charset");
		$charset = $charset ?: $defaultCharset;
		$defaultTablePre = "shd_";
		$prefix = config("database.prefix");
		$prefix = $prefix ?: $defaultTablePre;
		$system_version_type = \think\Db::name("configuration")->where("setting", "system_version_type")->find();
		if ($system_version_type["value"] && $system_version_type["value"] == "beta") {
			$beta_version = \think\Db::name("configuration")->where("setting", "beta_version")->find();
			$version = $beta_version["value"] ?? $version;
		}
		$file_name = CMF_ROOT . "/public/upgrade/upgrade.log";
		$handle = fopen($file_name, "r");
		if (!$handle) {
			return jsonrule(["status" => 400, "msg" => "未找到" . $file_name]);
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
		$arr_last_pop = array_pop($arr);
		$arr_last = explode(",", $arr_last_pop);
		$arr[] = $arr_last_pop;
		$last_version = $arr_last[1];
		if (version_compare($last_version, $version, ">")) {
			foreach ($arr as $v) {
				$v = explode(",", $v);
				$sql_version = $v[1];
				$sql_file = CMF_ROOT . "public/upgrade/" . $v[1] . ".sql";
				if (version_compare($sql_version, $version, ">")) {
					if (file_exists($sql_file)) {
						$sql = file_get_contents($sql_file);
						$sql = str_replace("\r", "\n", $sql);
						$sql = str_replace("BEGIN;\n", "", $sql);
						$sql = str_replace("COMMIT;\n", "", $sql);
						$sql = str_replace($defaultCharset, $charset, $sql);
						$sql = trim($sql);
						$sql = str_replace(" `{$defaultTablePre}", " `{$prefix}", $sql);
						$sqls = explode(";\n", $sql);
						foreach ($sqls as $sql) {
							try {
								\think\Db::execute($sql);
							} catch (\Exception $e) {
							}
						}
					}
				}
			}
		}
		if ($system_version_type["value"] && $system_version_type["value"] == "beta") {
			\think\Db::name("configuration")->where("setting", "beta_version")->update(["value" => $last_version]);
		}
		\think\Db::name("configuration")->where("setting", "update_last_version")->update(["value" => $last_version]);
		\think\Db::name("configuration")->where("setting", "executed_update")->update(["value" => 1]);
		session_start();
		$_SESSION = [];
		if (isset($_COOKIE[session_name()])) {
			setcookie(session_name(), "", time() - 3600, "/");
		}
		session_destroy();
		$upgrade_system_logic = new \app\common\logic\UpgradeSystem();
		@unlink($upgrade_system_logic->progress_log);
		return jsonrule(["status" => 200, "msg" => "恭喜你，升级完成\n系统升级已完成，请删除public/upgrade目录"]);
	}
}