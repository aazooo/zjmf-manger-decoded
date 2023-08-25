<?php

namespace app\common\logic;

/**
 *  更新系统逻辑类
 *  wyh
 *  2020-06-02
 *  升级包规定的目录结构
 *  版本号.zip(如：1.0.0.zip)
 *   |
 *   |————public/upgrade
 *   |    |
 *   |    |___1.0.0.sql
 *   |    |___1.0.1.sql
 *   |    |___……
 *   |    |___upgrade.php(升级)
 *   |
 *   |____
 *
 */
class UpgradeSystem
{
	private $allowed_local_test_license = ["7563DA6198C41976A46F4F57D836C0BE", "E54D92AA56A69D8C87DA5B4604ADFCCF", "772EC3A11FC87438E9B7E40AC4E86F8D", "22EC411DD6931A0275EC0B8887D4CC0F"];
	public $upgrade_log = "https://license.soft13.idcsmart.com/upgrade/stable/upgrade.php";
	public $upload_dir = CMF_ROOT;
	public $root_dir = CMF_ROOT;
	public $progress_log = CMF_ROOT . "progress_log.log";
	public $sys_version_num;
	public $update_log = CMF_ROOT . "update_log.log";
	public $return_log = CMF_ROOT . "return_log.log";
	public $aFile = ["log", "runtime"];
	public function __construct()
	{
		$update_last_version = configuration("update_last_version");
		if (empty($update_last_version)) {
			return ["status" => 400, "msg" => "未获取到本系统版本号,请联系系统管理员"];
			exit;
		}
		$this->sys_version_num = $update_last_version;
		$version_type = configuration("system_version_type");
		if ($version_type == "beta") {
			$this->upgrade_log = "https://license.soft13.idcsmart.com/upgrade/beta/upgrade.php";
		}
	}
	private function getUpgradeLog()
	{
		$timeout = ["http" => ["timeout" => 10]];
		$ctx = stream_context_create($timeout);
		$handle = fopen($this->upgrade_log, "r", false, $ctx);
		if (!$handle) {
			return ["status" => 400, "msg" => "打开远程文件失败！！"];
		}
		$content = "";
		while (!feof($handle)) {
			$content .= fread($handle, 80800);
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
		recurseGetLastVersion($last, $arr);
		return ["last" => $last, "arr" => $arr];
	}
	public function getLastVersion()
	{
		$data = $this->getUpgradeLog();
		if ($data["status"] && $data["status"] == 400) {
			return $data;
		}
		$arr = explode(",", $data["last"]);
		return $arr[1];
	}
	public function getHistoryVersion()
	{
		$data = $this->getUpgradeLog();
		if ($data["status"] && $data["status"] == 400) {
			return $data;
		}
		$arr = explode(",", $data["last"]);
		return ["last" => $arr[1], "all_version" => $data["arr"]];
	}
	/**
	 * 处理升级
	 */
	public function upload()
	{
		$progress_log = [];
		if (!is_readable($this->upload_dir) || !shd_new_is_writeable($this->upload_dir)) {
			$progress_log["progress"] = "10%";
			$progress_log["msg"] = "根目录不可读/写";
			$progress_log["status"] = 400;
			$this->update_progress(json_encode($progress_log));
			exit;
		}
		$progress_log["progress"] = "10%";
		$progress_log["msg"] = "开始升级系统,检测升级版本";
		$progress_log["status"] = 200;
		$this->update_progress(json_encode($progress_log));
		$res = $this->getUpgradeLog();
		if ($res["status"] && $res["status"] == 400) {
			$progress_log["progress"] = "10%";
			$progress_log["msg"] = $res["msg"];
			$progress_log["status"] = 400;
			$this->update_progress(json_encode($progress_log));
			exit;
		}
		$arr = explode(",", $res["last"]);
		if (empty($arr)) {
			$progress_log["progress"] = "10%";
			$progress_log["msg"] = "版本升级记录错误";
			$progress_log["status"] = 400;
			$this->update_progress(json_encode($progress_log));
			exit;
		}
		$version_num = $arr[1];
		$url = $arr[3];
		if (!$this->compare_version($version_num)) {
			$progress_log["progress"] = "10%";
			$progress_log["msg"] = "您的系统已经是最新版本，无需升级";
			$progress_log["status"] = 400;
			$this->update_progress(json_encode($progress_log));
			exit;
		}
		$down_res = $this->downloadUnzip($url);
		if ($down_res["status"] != 200) {
			exit;
		}
		$file_name = $down_res["data"];
		$url = trim($url);
		$package_name = str_replace(".zip", "", basename($url));
		return true;
	}
	private function downloadUnzip($url)
	{
		$progress_log = [];
		$url = urldecode($url);
		$fname = basename($url);
		$str_name = pathinfo($fname);
		$time = date("Ymd", time());
		$file_name = $time . rand(1000, 9999) . "^" . $str_name["filename"] . ".zip";
		$dir = $this->upload_dir . $file_name;
		if (!file_exists($this->upload_dir)) {
			mkdir($this->upload_dir, 511, true);
		}
		chmod($dir, 511);
		$url = dirname($url) . "/" . $str_name["filename"] . ".zip";
		$origin_size = get_headers($url, 1);
		$origin_size = $origin_size["Content-Length"] ?? 0;
		$origin_size = bcdiv($origin_size, 1048576, 2) ?? number_format(0, 2);
		$progress_log["progress"] = "20%";
		$progress_log["file_name"] = $file_name;
		$progress_log["origin_size"] = $origin_size;
		$progress_log["msg"] = "正在下载解压包,解压包大小:" . "{$origin_size}MB" . ";下载存储路径:" . $this->upload_dir;
		$progress_log["status"] = 200;
		$this->update_progress(json_encode($progress_log));
		unset($progress_log["file_name"]);
		unset($progress_log["origin_size"]);
		$content = curl_download($url, $file_name);
		$url = trim($url);
		$package_name = str_replace(".zip", "", basename($url));
		session("upgrade.system_version", $package_name);
		if ($content) {
			if (file_exists($this->upload_dir . $str_name["filename"])) {
				deleteDir($this->upload_dir . $str_name["filename"]);
			}
			$progress_log["progress"] = "30%";
			$progress_log["file_name"] = $file_name;
			$progress_log["package_name"] = $package_name;
			$progress_log["setup"] = "unzip";
			$progress_log["msg"] = "已成功下载";
			$progress_log["status"] = 200;
			$this->update_progress(json_encode($progress_log));
		} else {
			$progress_log["progress"] = "20%";
			$progress_log["msg"] = "下载压缩包失败";
			$progress_log["status"] = 400;
			$this->update_progress(json_encode($progress_log));
			$this->deleteUpgrdeFile($file_name, $package_name);
			return ["status" => 400, "msg" => "下载压缩包失败"];
		}
	}
	public function updateUnzip()
	{
		$progresslog = file_get_contents($this->progress_log);
		$progresslog = json_decode($progresslog, true);
		if ($progresslog["setup"] == "unzip") {
			$progress_log["progress"] = "40%";
			$progress_log["file_name"] = $progresslog["file_name"];
			$progress_log["package_name"] = $progresslog["package_name"];
			$progress_log["msg"] = "正在解压";
			$progress_log["status"] = 200;
			$this->update_progress(json_encode($progress_log));
			$res = unzip($this->upload_dir . $progresslog["file_name"], $this->upload_dir);
			if ($res["status"] == 200) {
				$progress_log["progress"] = "50%";
				$progress_log["file_name"] = $progresslog["file_name"];
				$progress_log["package_name"] = $progresslog["package_name"];
				$progress_log["setup"] = "copy";
				$progress_log["setup_copy"] = "no";
				$progress_log["msg"] = "解压成功";
				$progress_log["status"] = 200;
				$this->update_progress(json_encode($progress_log));
			} else {
				$progress_log["progress"] = "40%";
				$progress_log["msg"] = "解压失败,失败code:" . $res["msg"] . ";请到网站目录下解压下载的文件或者重新更新系统";
				$progress_log["status"] = 400;
				$this->update_progress(json_encode($progress_log));
				$this->deleteUpgrdeFile($progresslog["file_name"], $progresslog["package_name"]);
			}
		}
	}
	public function updateCopy()
	{
		$progresslog = file_get_contents($this->progress_log);
		$progresslog = json_decode($progresslog, true);
		if ($progresslog["setup"] == "copy") {
			$package_name = $progresslog["package_name"];
			$file_name = $progresslog["file_name"];
			$progress_log["progress"] = "60%";
			$progress_log["msg"] = "正在复制文件";
			$progress_log["status"] = 200;
			$this->update_progress(json_encode($progress_log));
			$php_address = $this->upload_dir . $package_name;
			if (is_dir($php_address)) {
				$admin_application = config("database.admin_application") ?? "admin";
				$res = recurse_copy($php_address, $this->root_dir);
				if ($res["status"] == 200) {
					chmod($this->upload_dir . $package_name, 511);
					deleteDir($this->upload_dir . $package_name);
					unlink($this->upload_dir . $file_name);
					if ($admin_application != "admin") {
						if (is_dir($this->upload_dir . "public/admin")) {
							$custom_dir = array_diff(scandir($this->upload_dir . "public/" . $admin_application), scandir($this->upload_dir . "public/admin"));
							if (is_array($custom_dir)) {
								foreach ($custom_dir as $v) {
									$admin_path = $this->upload_dir . "public/admin/" . $v;
									$original_path = $this->upload_dir . "public/" . $admin_application . "/" . $v;
									if (is_dir($original_path)) {
										recurse_copy($original_path, $admin_path);
									} else {
										if (is_file($original_path)) {
											copy($original_path, $admin_path);
										}
									}
								}
							}
							deleteDir($this->upload_dir . "public/" . $admin_application . "_old");
							usleep(100000);
							rename($this->upload_dir . "public/" . $admin_application, $this->upload_dir . "public/" . $admin_application . "_old");
						}
						usleep(100000);
						rename($this->upload_dir . "public/admin", $this->upload_dir . "public/" . $admin_application);
					}
					$progress_log["progress"] = "100%";
					$progress_log["msg"] = "升级包安装完成";
					$progress_log["status"] = 200;
					$this->update_progress(json_encode($progress_log));
				} else {
					$progress_log["progress"] = "60%";
					$progress_log["msg"] = "升级失败:文件" . $res["data"] . "复制出错";
					$progress_log["status"] = 400;
					$this->update_progress(json_encode($progress_log));
					$this->deleteUpgrdeFile($file_name, $package_name);
				}
			}
		}
	}
	/**
	 * 升级操作
	 * @return [type] [description]
	 */
	private function execute_update($package_name)
	{
		$php_address = $this->upload_dir . $package_name;
		if (is_dir($php_address)) {
			$admin_application = config("database.admin_application") ?? "admin";
			$res = recurse_copy($php_address, $this->root_dir);
			if ($res["status"] == 400) {
				return ["status" => 400, "msg" => ":文件" . $res["data"] . "复制出错"];
			}
		}
		return ["status" => 200, "msg" => "下载成功，点击开始安装升级包"];
	}
	/**
	 * 比较代码版本
	 * @return [type] [description]
	 */
	private function compare_version($version_num = "1.0.0")
	{
		return version_compare($version_num, $this->sys_version_num, ">");
	}
	/**
	 * 备份代码
	 */
	private function backup_code()
	{
		$cmd = "cd {$this->root_dir} && cd ..  && rsync -av ";
		foreach ($this->aFile as $key => $value) {
			$cmd .= "--exclude " . basename($this->root_dir) . "/" . $value . " ";
		}
		$cmd .= basename($this->root_dir) . " " . $this->backup_dir . "/" . $this->sys_version_num;
		exec($cmd, $mdata, $status);
		if ($status != 0) {
			return false;
		}
		return true;
	}
	/**
	 * 数据库操作
	 */
	public function database_operation($file)
	{
		$sqls = file_get_contents($file);
		$sqls = explode(";", $sqls);
		$fun = function ($value) {
			if (empty($value)) {
				return false;
			} else {
				return true;
			}
		};
		$sqls = array_filter($sqls, $fun);
		foreach ($sqls as $sql) {
			$sql = "        {$sql};";
			if (!empty($sql)) {
				\think\Db::execute($sql);
			}
		}
	}
	/**
	 * 返回系统升级的进度
	 */
	public function update_progress($progress)
	{
		file_put_contents($this->progress_log, $progress . "\n");
	}
	/**
	 * 记录日志
	 */
	public function save_log($msg, $action = "update")
	{
		$msg .= date("Y-m-d H:i:s") . ":" . $msg . "\n";
		if ($action == "update") {
			exec(" echo '" . $msg . "' >>  {$this->update_log} ");
		} else {
			exec(" echo '" . $msg . "' >>  {$this->return_log} ");
		}
	}
	public function diffVersion($last_version, $version)
	{
		$a = explode(".", $last_version);
		$b = explode(".", $version);
		$arr = [];
		$num1 = $a[0] * 100 + $a[1] * 10 + $a[2];
		for ($num2 = $b[0] * 100 + $b[1] * 10 + $b[2]; $num2 <= $num1; $num2++) {
			$hundred = floor($num2 / 100);
			$ten = floor(($num2 - 100 * $hundred) / 10);
			$unit = floor($num2 - 100 * $hundred - 10 * $ten);
			$version = $hundred . "." . $ten . "." . $unit;
			$arr[] = $version;
		}
		return $arr;
	}
	public function deleteUpgrdeFile($file_name, $package_name)
	{
		$check_version = session("upgrade.system_version");
		if (empty($check_version)) {
			return false;
		}
		if (!empty($file_name) && strpos($file_name, $check_version) !== false) {
			@unlink($this->upload_dir . $file_name);
		}
		if (!empty($package_name) && strpos($package_name, $check_version) !== false) {
			chmod($this->upload_dir . $package_name, 511);
			deleteDir($this->upload_dir . $package_name);
		}
	}
}