<?php

namespace app\common\logic;

class Install
{
	public function sp_dir_create($path, $mode = 511)
	{
		if (is_dir($path)) {
			return true;
		}
		$ftp_enable = 0;
		$path = sp_dir_path($path);
		$temp = explode("/", $path);
		$cur_dir = "";
		$max = count($temp) - 1;
		for ($i = 0; $i < $max; $i++) {
			$cur_dir .= $temp[$i] . "/";
			if (@is_dir($cur_dir)) {
			} else {
				@mkdir($cur_dir, 511, true);
				@chmod($cur_dir, 511);
			}
		}
		return is_dir($path);
	}
	public function new_is_writeable($file)
	{
		if (is_dir($file)) {
			$dir = $file;
			if ($fp = @fopen("{$dir}/test.txt", "w")) {
				@fclose($fp);
				@unlink("{$dir}/test.txt");
				$writeable = true;
			} else {
				$writeable = false;
			}
		} else {
			if ($fp = @fopen($file, "a+")) {
				@fclose($fp);
				$writeable = true;
			} else {
				$writeable = false;
			}
		}
		return $writeable;
	}
	public function sp_execute_sql($db, $sql)
	{
		$sql = trim($sql);
		preg_match("/CREATE TABLE .+ `([^ ]*)`/", $sql, $matches);
		if ($matches) {
			$table_name = $matches[1];
			$msg = "创建数据表{$table_name}";
			try {
				$db->execute($sql);
				return ["error" => 0, "message" => $msg . " 成功！"];
			} catch (\Exception $e) {
				return ["error" => 1, "message" => $msg . " 失败！", "exception" => $e->getTraceAsString()];
			}
		} else {
			try {
				$db->execute($sql);
				return ["error" => 0, "message" => "SQL执行成功!"];
			} catch (\Exception $e) {
				return ["error" => 1, "message" => "SQL执行失败！", "exception" => $e->getTraceAsString()];
			}
		}
	}
	public function sp_create_db_config($config)
	{
		if (is_array($config)) {
			$conf = file_get_contents(CMF_ROOT . "/public/install/config.php");
			foreach ($config as $key => $value) {
				$conf = str_replace("#{$key}#", $value, $conf);
			}
			if (strpos(cmf_version(), "5.0.") === false) {
				$confDir = CMF_ROOT . "/app/config/";
			} else {
				$confDir = CMF_DATA . "conf/";
			}
			try {
				if (!file_exists($confDir)) {
					mkdir($confDir, 511, true);
				}
				file_put_contents($confDir . "database.php", $conf);
			} catch (\Exception $e) {
				return false;
			}
			return true;
		}
	}
}