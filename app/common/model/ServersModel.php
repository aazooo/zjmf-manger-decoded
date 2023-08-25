<?php

namespace app\common\model;

class ServersModel extends \think\Model
{
	public function getServerGroups($field = "*", $type = "normal")
	{
		$server_groups = \think\Db::name("server_groups")->field($field)->where("system_type", $type)->select()->toArray();
		if ($type == "normal") {
			$server = [];
			!empty($server_groups) && ($server = \think\Db::name("servers")->field("gid,type")->whereIn("gid", array_column($server_groups, "id"))->group("gid")->select()->toArray());
			!empty($server) && ($server = array_column($server, "type", "gid"));
			$modules = (new \app\common\logic\Provision())->getModules();
			if ($modules) {
				$modules = array_column($modules, "name", "value");
			}
		}
		foreach ($server_groups as $k => &$v) {
			if ($v["system_type"] == "dcim") {
				$server_groups[$k]["name"] = $v["name"] . "(魔方DCIM)";
			} elseif ($v["system_type"] == "dcimcloud") {
				$server_groups[$k]["name"] = $v["name"] . "(智简魔方云)";
			} else {
				$v["type"] = $server[$v["id"]] ?? "";
				if (!$v["type"]) {
					unset($server_groups[$k]);
					continue;
				}
				$server_groups[$k]["name"] = $v["name"] . "(" . ($modules[$v["type"]] ?? $v["type"]) . ")";
			}
		}
		return $server_groups ?: [];
	}
	public function getServers($field = "*")
	{
		$servers = $this->field($field)->select()->toArray();
		return $servers ?: [];
	}
	public function getLink()
	{
		$server_groups = $this->getServerGroups();
		$server_link = [];
		foreach ($server_groups as $k => $v) {
			$servers = $this->field("id,name,ip_address,disabled")->where("gid", $v["id"])->select();
			$v["child"] = $servers;
			$server_link[$k] = $v;
		}
		return $server_link ?: [];
	}
}