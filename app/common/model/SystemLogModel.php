<?php

namespace app\common\model;

class SystemLogModel extends \think\Model
{
	public static function log($description = "", $log_type = "system", $relid = 0)
	{
		$ip = get_client_ip();
		$admin_id = intval(cmf_get_current_admin_id());
		$relid = intval($relid);
		$info = \think\Db::name("user")->where("id", $admin_id)->find();
		if (empty($info) && ($ip != "::1" || empty($ip))) {
			return null;
		}
		$insert = ["create_time" => time(), "description" => $description, "user" => $info["user_login"] ?: "SYSTEM", "uid" => $admin_id, "user_type" => 0, "ip" => $ip, "log_type" => $log_type, "relid" => $relid];
		return \think\Db::name("system_log")->insert($insert);
	}
}