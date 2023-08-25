<?php

namespace app\admin\lib;

/**
 * 数据库备份
 */
class Backup
{
	private $tables = [];
	private $path;
	private $dbname;
	private $model;
	public function __construct($path, $database)
	{
		$this->path = $path;
		$this->dbname = $database;
		$this->check_path();
		$this->get_tables();
	}
	public function backupAll()
	{
		if ($this->tables) {
			$data = $this->genTitle();
			foreach ($this->tables as $table) {
				$ctable = $this->get_create_table($table);
				$data .= $this->get_table_structure($ctable);
				$data .= $this->get_table_records($table);
			}
			$filename = "backup_" . date("YmdHis") . ".sql";
			$file_path = $this->path . $filename;
			$res = file_put_contents($file_path, $data);
			chmod($file_path, 493);
			if (!$res) {
				throw new \Exception("备份数据库失败");
			}
			if (file_exists($file_path)) {
				$suffix = pathinfo($file_path)["extension"];
				$zipClass = new \ZipArchive();
				$zip_name = $this->path . pathinfo($file_path)["filename"] . ".zip";
				if ($zipClass->open($zip_name, \ZipArchive::CREATE) !== true) {
					exit("cannot create " . $zip_name);
				} else {
					$this->addFileToZip($file_path, $filename, $zipClass);
					$zipClass->close();
					chmod($zip_name, 493);
					unlink($file_path);
				}
				return pathinfo($file_path)["filename"] . ".zip";
			} else {
				return false;
			}
		}
		return false;
	}
	public function addFileToZip($path, $prix_name, $zip)
	{
		$zip->addFile($path, $prix_name);
		@closedir($path);
		return true;
	}
	public function unlinkDir($move_zip_dir)
	{
		if (is_dir($move_zip_dir)) {
			$handle = opendir($move_zip_dir);
			while (($file = readdir($handle)) !== false) {
				if ($file != "." && $file != "..") {
					$dir = $move_zip_dir . "/" . $file;
					is_dir($dir) ? $this->unlinkDir($dir) : unlink($dir);
				}
			}
			closedir($handle);
			rmdir($move_zip_dir);
		}
	}
	private function restore($file)
	{
		$filename = $file;
		if (!file_exists($filename)) {
			return false;
		}
		$str = fread($hd = fopen($filename, "rb"), filesize($filename));
		$sqls = explode(";\r\n", $str);
		if ($sqls) {
			foreach ($sqls as $sql) {
				\think\Db::query($sql);
			}
		}
		fclose($hd);
		return true;
	}
	public function getFileInfo()
	{
		$temp = [];
		if (is_dir($this->path)) {
			$handler = opendir($this->path);
			$num = 0;
			while ($file = readdir($handler)) {
				if ($file !== "." && $file !== "..") {
					$filename = $this->path . $file;
					$temp[$num]["name"] = $file;
					$temp[$num]["size"] = ceil(filesize($filename) / 1024);
					$temp[$num]["time"] = date("Y-m-d H:i:s", filemtime($filename));
					$temp[$num]["path"] = $filename;
					$num++;
				}
			}
		}
		return $temp;
	}
	private function delFile($file)
	{
		if (file_exists($file)) {
			return unlink($file);
		}
		return false;
	}
	private function genTitle()
	{
		$time = date("Y-m-d H:i:s", time());
		$str = "/*************************\r\n";
		$str .= " * {$time} \r\n";
		$str .= " ************************/\r\n";
		$str .= "SET FOREIGN_KEY_CHECKS=0;\r\n";
		return $str;
	}
	private function get_tables()
	{
		$sql = "show tables";
		if ($data = \think\Db::query($sql)) {
			foreach ($data as $val) {
				$this->tables[] = $val["Tables_in_" . $this->dbname];
			}
		}
	}
	private function get_create_table($table)
	{
		$sql = "show create table {$table}";
		$arr = \think\Db::query($sql)[0];
		return array_values($arr);
	}
	private function get_table_structure($ctable)
	{
		$str = "-- ----------------------------\r\n";
		$str .= "-- Table structure for `{$ctable[0]}`\r\n";
		$str .= "-- ----------------------------\r\n";
		$str .= "DROP TABLE IF EXISTS `{$ctable[0]}`;\r\n" . $ctable[1] . ";\r\n\r\n";
		return $str;
	}
	private function get_table_records($table)
	{
		$sql = "select * from {$table}";
		if ($data = \think\Db::query($sql)) {
			$str = "-- ----------------------------\r\n";
			$str .= "-- Records of {$table} \r\n";
			$str .= "-- ----------------------------\r\n";
			foreach ($data as $val) {
				if ($val) {
					$valArr = [];
					foreach ($val as $k => $v) {
						$valArr[] = "'" . str_replace(["'", "\r\n"], ["\\'", "\\r\\n"], $v) . "'";
					}
					$values = implode(", ", $valArr);
					$str .= "INSERT INTO `{$table}` VALUES ({$values});\r\n";
				}
			}
			$str .= "\r\n";
			return $str;
		}
		return "";
	}
	private function check_path()
	{
		if (!is_dir($this->path)) {
			mkdir($this->path, 493, true);
		}
	}
}