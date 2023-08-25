<?php

namespace app\common\logic;

class DcimCloud
{
	public $url = "";
	public $username = "";
	public $password = "";
	public $error = false;
	public $link_error_msg = "";
	public $curl_error = "";
	public $is_admin = false;
	public $server_type = "dcimcloud";
	public $serverid = 0;
	private $dir = CMF_ROOT . "public/vendor/dcimcloud";
	public $user_prefix = "";
	public $log_prefix_error = "魔方云模块错误:";
	public function __construct($serverid = 0)
	{
		$this->setUrl($serverid);
	}
	public function setUrl($serverid)
	{
		if (!empty($serverid)) {
			$server_info = \think\Db::name("servers")->field("id,hostname,username,password,secure,port,accesshash")->where("id", $serverid)->where("server_type", $this->server_type)->find();
			if (empty($server_info)) {
				$this->error = true;
			} else {
				$protocol = $server_info["secure"] == 1 ? "https://" : "http://";
				$url = $protocol . $server_info["hostname"];
				if (!empty($server_info["port"])) {
					$url .= ":" . $server_info["port"];
				}
				$this->serverid = $serverid;
				$this->url = $url;
				$this->username = $server_info["username"];
				$this->password = aesPasswordDecode($server_info["password"]);
				$this->formatAccesshash($server_info["accesshash"]);
			}
		}
	}
	public function setUrlByHost($hostid)
	{
		if (!empty($hostid)) {
			$server_info = \think\Db::name("host")->alias("a")->field("a.dcimid,b.id,b.hostname,b.username,b.password,b.secure,b.port,b.accesshash")->leftJoin("servers b", "a.serverid=b.id")->where("a.id", $hostid)->where("b.server_type", $this->server_type)->find();
			if (empty($server_info)) {
				$this->error = true;
			} else {
				$protocol = $server_info["secure"] == 1 ? "https://" : "http://";
				$url = $protocol . $server_info["hostname"];
				if (!empty($server_info["port"])) {
					$url .= ":" . $server_info["port"];
				}
				$this->serverid = $server_info["id"];
				$this->url = $url;
				$this->username = $server_info["username"];
				$this->password = aesPasswordDecode($server_info["password"]);
				$this->formatAccesshash($server_info["accesshash"]);
			}
		}
		return \intval($server_info["dcimid"]);
	}
	public function setUrlByServer($server_info = [])
	{
		if (empty($server_info)) {
			$this->error = true;
		} else {
			$protocol = $server_info["secure"] == 1 ? "https://" : "http://";
			$url = $protocol . $server_info["hostname"];
			if (!empty($server_info["port"])) {
				$url .= ":" . $server_info["port"];
			}
			$this->serverid = $server_info["serverid"];
			$this->url = $url;
			$this->username = $server_info["username"];
			$this->password = aesPasswordDecode($server_info["password"]);
			$this->formatAccesshash($server_info["accesshash"]);
		}
	}
	public function formatAccesshash($accesshash)
	{
		$accesshash = trim($accesshash);
		if (!empty($accesshash)) {
			$accesshash = explode(PHP_EOL, $accesshash);
			foreach ($accesshash as $v) {
				$v = explode(":", trim($v));
				if (!empty($v)) {
					$this->{$v[0]} = trim($v[1]);
				}
			}
		}
	}
	public function getOs()
	{
		$result = [];
		if ($this->account_type == "agent") {
		} else {
			$res = $this->curl("/image?per_page=9999&sort=asc", [], 15, "GET");
			if ($res["status"] == "success") {
				$rescue = ["Linux_Rescue.qcow2", "Linux_Rescue.raw", "Windows_Rescue.qcow2", "Windows_Rescue.raw"];
				foreach ($res["data"]["data"] as $v) {
					if ($v["group"]["svg"] == 10 || in_array($v["filename"], $rescue)) {
						continue;
					}
					if ($v["status"] == 1) {
						$result[] = ["name" => $v["id"] . "|" . ($v["group_name"] ?? $v["group"]["name"]) . "^" . $v["name"]];
					}
				}
			}
		}
		return $result;
	}
	public function getArea()
	{
		$result = [];
		if ($this->account_type == "agent") {
		} else {
			$res = $this->curl("/areas?sort=asc&list_type=all", [], 10, "GET");
			if ($res["status"] == "success") {
				foreach ($res["data"]["data"] as $v) {
					$result[] = ["name" => $v["id"] . "|" . $v["country_code"] . "^" . $v["name"]];
				}
			}
		}
		return $result;
	}
	public function moduleClientArea($id)
	{
		$mod = new \app\common\model\HostModel();
		$params = $mod->getProvisionParams($id);
		if (empty($params["dcimid"])) {
			return [];
		}
		$data = [];
		$name = "";
		if (is_numeric($params["configoptions"]["snap_num"]) && $params["configoptions"]["snap_num"] >= 0) {
			$name = "快照";
		}
		if (is_numeric($params["configoptions"]["backup_num"]) && $params["configoptions"]["backup_num"] >= 0) {
			$name = $name ? $name . "/备份" : "备份";
		}
		if (!empty($name)) {
			$data[] = ["name" => $name, "key" => "snapshot"];
		}
		$data[] = ["name" => "安全组", "key" => "security_groups"];
		$data[] = ["name" => "设置", "key" => "setting"];
		if (empty($params["configoptions"]["type"]) || $params["configoptions"]["type"] == "host" || $params["configoptions"]["type"] == "lightHost") {
			if (is_numeric($params["configoptions"]["nat_acl_limit"]) && $params["configoptions"]["nat_acl_limit"] >= 0) {
				$data[] = ["name" => "NAT转发", "key" => "nat_acl"];
			}
			if (is_numeric($params["configoptions"]["nat_web_limit"]) && $params["configoptions"]["nat_web_limit"] >= 0) {
				$data[] = ["name" => "共享建站", "key" => "nat_web"];
			}
		}
		return $data;
	}
	public function moduleClientAreaDetail($id, $key, $api_url = "", $v10 = false)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			return "";
		}
		$this->setUrl($product["serverid"]);
		if ($this->error) {
			return "";
		}
		if (empty($product["dcimid"])) {
			return "";
		}
		$cloud = $this->curl("/clouds/" . $product["dcimid"], [], 30, "GET");
		if ($key == "snapshot") {
			$mod = new \app\common\model\HostModel();
			$params = $mod->getProvisionConfigOption($id);
			$support_snap = \intval($params["configoptions"]["snap_num"]) >= 0;
			$support_backup = \intval($params["configoptions"]["backup_num"]) >= 0;
			$detail = $this->curl("/clouds/" . $product["dcimid"], [], 30, "GET");
			$host_type = $detail["data"]["type"] ?: "host";
			if ($host_type == "hyperv") {
				$support_snap = false;
			}
			$res = $this->curl("/clouds/" . $product["dcimid"] . "/snapshots", ["per_page" => 100], 30, "GET");
			$res = ["template" => "snapshot.html", "vars" => ["list" => $res["data"]["data"], "disk" => $cloud["data"]["disk"], "support_snap" => $support_snap, "support_backup" => $support_backup, "host_type" => $host_type]];
			if ($v10) {
				return ["status" => 200, "msg" => "请求成功", "data" => ["list" => $res["vars"]["list"], "disk" => $cloud["data"]["disk"], "support_snap" => $support_snap, "support_backup" => $support_backup, "host_type" => $host_type, "backup_num" => isset($params["configoptions"]["backup_num"]) ? \intval($params["configoptions"]["backup_num"]) : -1, "snap_num" => isset($params["configoptions"]["snap_num"]) ? \intval($params["configoptions"]["snap_num"]) : -1]];
			}
		} elseif ($key == "setting") {
			$res = $this->curl("/node_isos", ["id" => $cloud["data"]["node_id"], "type" => "node"], 30, "GET");
			$iso2 = [];
			foreach ($res["data"] as $k => $v) {
				foreach ($v["info"] as $kk => $vv) {
					$iso2[] = ["id" => $vv["id"], "name" => $vv["name"]];
				}
			}
			if (count($iso2) == 0) {
				$iso2[] = ["id" => "", "name" => "无"];
			}
			$detail = $this->curl("/clouds/" . $product["dcimid"], [], 30, "GET");
			$host_type = $detail["data"]["type"] ?: "host";
			$res = ["template" => "setting.html", "vars" => ["iso" => $cloud["data"]["iso"], "bootorder" => $cloud["data"]["bootorder"], "iso2" => $iso2, "host_type" => $host_type]];
		} elseif ($key == "security_groups") {
			$detail = $this->curl("/clouds/" . $product["dcimid"], [], 30, "GET");
			if (!empty($detail["data"]["user_id"])) {
				$data = $this->curl("/security_groups", ["list_type" => "all", "user" => $detail["data"]["user_id"], "type" => $detail["data"]["type"]], 30, "GET");
				$list = $data["data"]["data"] ?: [];
				$host_type = $detail["data"]["type"] ?: "host";
			} else {
				$list = [];
				$host_type = "host";
			}
			$protocols = $this->curl("/security_group_rule_protocols", [], 15, "GET");
			$protocols = $protocols["data"];
			if (empty($protocols)) {
				$protocols = [["name" => "全部", "port" => "1-65535", "value" => "all"], ["name" => "全部TCP", "port" => "1-65535", "value" => "all_tcp"], ["name" => "全部UDP", "port" => "1-65535", "value" => "all_udp"], ["name" => "自定义TCP", "port" => "", "value" => "tcp"], ["name" => "自定义UDP", "port" => "", "value" => "udp"], ["name" => "ICMP", "port" => "1-65535", "value" => "icmp"], ["name" => "SSH (22)", "port" => "22", "value" => "ssh"], ["name" => "telnet (23)", "port" => "23", "value" => "telnet"], ["name" => "HTTP (80)", "port" => "80", "value" => "http"], ["name" => "HTTPS (443)", "port" => "443", "value" => "https"], ["name" => "MS SQL(1433)", "port" => "1433", "value" => "mssql"], ["name" => "Oracle (1521)", "port" => "1521", "value" => "oracle"], ["name" => "MySQL (3306)", "port" => "3306", "value" => "mysql"], ["name" => "RDP (3389)", "port" => "3389", "value" => "rdp"], ["name" => "PostgreSQL (5432)", "port" => "5432", "value" => "postgresql"], ["name" => "Redis (6379)", "port" => "6379", "value" => "redis"]];
			}
			$res = ["template" => "security_groups.html", "vars" => ["list" => $list, "used" => $detail["data"]["security"], "protocols" => $protocols, "host_type" => $host_type]];
		} elseif ($key == "nat_acl") {
			$data = $this->curl("/clouds/" . $product["dcimid"] . "/nat_acl", ["list_type" => "all"], 30, "GET");
			$res = ["template" => "nat_acl.html", "vars" => ["list" => $data["data"]["data"] ?: [], "nat_host_ip" => $data["data"]["nat_host_ip"]]];
		} elseif ($key == "nat_web") {
			$data = $this->curl("/clouds/" . $product["dcimid"] . "/nat_web", ["list_type" => "all"], 30, "GET");
			$res = ["template" => "nat_web.html", "vars" => ["list" => $data["data"]["data"] ?: [], "nat_host_ip" => $data["data"]["nat_host_ip"]]];
		}
		if (file_exists($this->dir . "/" . $res["template"])) {
			$view = new \think\View();
			$view->init("Think");
			foreach ($res["vars"] as $k => $v) {
				$view->assign($k, $v);
			}
			if (!empty($api_url)) {
				$view->assign("MODULE_CUSTOM_API", $api_url);
			} else {
				$view->assign("MODULE_CUSTOM_API", request()->domain() . request()->rootUrl() . "/provision/custom/" . $id);
			}
			$html = $view->fetch($this->dir . "/" . $res["template"]);
		} else {
			$html = "";
		}
		return $html;
	}
	public function getNatInfo($id)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			return [];
		}
		$this->setUrl($product["serverid"]);
		if ($this->error) {
			return [];
		}
		if (empty($product["dcimid"])) {
			return [];
		}
		$mod = new \app\common\model\HostModel();
		$params = $mod->getProvisionConfigOption($id);
		$result["nat_acl"] = "";
		$result["nat_web"] = "";
		if (isset($params["configoptions"]["nat_acl_limit"]) && $params["configoptions"]["nat_acl_limit"] >= 0) {
			$data = $this->curl("/clouds/" . $product["dcimid"] . "/nat_acl", ["per_page" => 1, "sort" => "asc"], 15, "GET");
			$result["nat_acl"] = $data["data"]["data"][0] ? $data["data"]["nat_host_ip"] . ":" . $data["data"]["data"][0]["ext_port"] : "";
		}
		if (isset($params["configoptions"]["nat_web_limit"]) && $params["configoptions"]["nat_web_limit"] >= 0) {
			$data = $this->curl("/clouds/" . $product["dcimid"] . "/nat_web", ["per_page" => 1, "sort" => "asc"], 15, "GET");
			$result["nat_web"] = $data["data"]["data"][0] ? $data["data"]["data"][0]["domain"] : "";
		}
		return $result;
	}
	public function supportReinstallRandomPort($id)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			return false;
		}
		$this->setUrl($product["serverid"]);
		if ($this->error) {
			return false;
		}
		if (empty($product["dcimid"])) {
			return false;
		}
		$res = $this->curl("/common_config", [], 10, "GET");
		if ($res["status"] == "success" && in_array("random_port", $res["data"]["auth_app"])) {
			return true;
		}
		return false;
	}
	public function moduleAllowFunction()
	{
		return ["createSnap", "deleteSnap", "restoreSnap", "createBackup", "deleteBackup", "restoreBackup", "mountIso", "unmountIso", "setBootOrder", "createSecurityGroup", "delSecurityGroup", "showSecurityRules", "createSecurityRule", "delSecurityRule", "linkSecurityGroup", "addNatAcl", "delNatAcl", "addNatWeb", "delNatWeb", "exitRescue", "remoteInfo"];
	}
	public function createSnap($id)
	{
		$post = input("post.");
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.api_type,b.zjmf_api_id")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		$mod = new \app\common\model\HostModel();
		$params = $mod->getProvisionParams($id);
		if (!is_numeric($params["configoptions"]["snap_num"]) || $params["configoptions"]["snap_num"] < 0) {
			$result["status"] = 400;
			$result["msg"] = "不支持创建快照";
			return $result;
		}
		if ($product["api_type"] == "zjmf_api") {
			$res = zjmfCurl($product["zjmf_api_id"], "/provision/custom/" . \intval($product["dcimid"]), $post);
		} else {
			$this->setUrl($product["serverid"]);
			if ($this->error) {
				$result["status"] = 400;
				$result["msg"] = "操作失败";
				return $result;
			}
			if (empty($product["dcimid"])) {
				$result["status"] = 400;
				$result["msg"] = "云主机ID错误";
				return $result;
			}
			if (empty($post["id"])) {
				$result["status"] = 400;
				$result["msg"] = "参数错误";
				return $result;
			}
			$res = $this->curl("/disks/" . $post["id"] . "/snapshots", ["type" => "snap", "name" => $post["name"]]);
		}
		if ($res["status"] == "success" || $res["status"] == 200) {
			$result["status"] = 200;
			$result["msg"] = "快照创建任务发起成功，请稍后查看";
			$description = sprintf("快照创建任务发起成功 - Host ID:%d", $id);
		} else {
			$result["status"] = 400;
			$result["msg"] = "快照创建失败";
			if ($this->is_admin) {
				$result["msg"] = $this->log_prefix_error . $result["msg"];
				$description = $this->log_prefix_error . sprintf("快照创建失败,原因:%s - Host ID:%d", $res["msg"], $id);
			} else {
				$description = sprintf("快照创建失败 - Host ID:%d", $id);
			}
		}
		active_log_final($description, $product["uid"], 2, $id);
		return $result;
	}
	public function deleteSnap($id)
	{
		$post = input("post.");
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.api_type,b.zjmf_api_id")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if ($product["api_type"] == "zjmf_api") {
			$res = zjmfCurl($product["zjmf_api_id"], "/provision/custom/" . \intval($product["dcimid"]), $post);
		} else {
			$this->setUrl($product["serverid"]);
			if ($this->error) {
				$result["status"] = 400;
				$result["msg"] = "操作失败";
				return $result;
			}
			if (empty($product["dcimid"])) {
				$result["status"] = 400;
				$result["msg"] = "云主机ID错误";
				return $result;
			}
			if (empty($post["id"])) {
				$result["status"] = 400;
				$result["msg"] = "参数错误";
				return $result;
			}
			$res = $this->curl("/snapshots/" . $post["id"], [], 30, "DELETE");
		}
		if ($res["status"] == "success" || $res["status"] == 200) {
			$result["status"] = 200;
			$result["msg"] = "快照删除任务发起成功，请稍后查看";
			$description = sprintf("快照删除任务发起成功 - Host ID:%d", $id);
		} else {
			$result["status"] = 400;
			$result["msg"] = "快照删除失败";
			if ($this->is_admin) {
				$result["msg"] = $this->log_prefix_error . $result["msg"];
				$description = $this->log_prefix_error . sprintf("快照删除失败,原因:%s - Host ID:%d", $res["msg"], $id);
			} else {
				$description = sprintf("快照删除失败 - Host ID:%d", $id);
			}
		}
		active_log_final($description, $product["uid"], 2, $id);
		return $result;
	}
	public function restoreSnap($id)
	{
		$post = input("post.");
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.api_type,b.zjmf_api_id")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if ($product["api_type"] == "zjmf_api") {
			$res = zjmfCurl($product["zjmf_api_id"], "/provision/custom/" . \intval($product["dcimid"]), $post);
		} else {
			$this->setUrl($product["serverid"]);
			if ($this->error) {
				$result["status"] = 400;
				$result["msg"] = "操作失败";
				return $result;
			}
			if (empty($product["dcimid"])) {
				$result["status"] = 400;
				$result["msg"] = "云主机ID错误";
				return $result;
			}
			if (empty($post["id"])) {
				$result["status"] = 400;
				$result["msg"] = "参数错误";
				return $result;
			}
			$res = $this->curl("/snapshots/" . $post["id"] . "/restore?hostid=" . $product["dcimid"]);
		}
		if ($res["status"] == "success" || $res["status"] == 200) {
			$result["status"] = 200;
			$result["msg"] = "快照恢复任务发起成功，请稍后查看";
			$description = sprintf("快照恢复任务发起成功 - Host ID:%d", $id);
		} else {
			$result["status"] = 400;
			$result["msg"] = "快照恢复失败";
			if ($this->is_admin) {
				$result["msg"] = $this->log_prefix_error . $result["msg"];
				$description = $this->log_prefix_error . sprintf("快照恢复失败,原因:%s - Host ID:%d", $res["msg"], $id);
			} else {
				$description = sprintf("快照恢复失败 - Host ID:%d", $id);
			}
		}
		active_log_final($description, $product["uid"], 2, $id);
		return $result;
	}
	public function createBackup($id)
	{
		$post = input("post.");
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.api_type,b.zjmf_api_id")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		$mod = new \app\common\model\HostModel();
		$params = $mod->getProvisionParams($id);
		if (!is_numeric($params["configoptions"]["backup_num"]) || $params["configoptions"]["backup_num"] < 0) {
			$result["status"] = 400;
			$result["msg"] = "不支持创建备份";
			return $result;
		}
		if ($product["api_type"] == "zjmf_api") {
			$res = zjmfCurl($product["zjmf_api_id"], "/provision/custom/" . \intval($product["dcimid"]), $post);
		} else {
			$this->setUrl($product["serverid"]);
			if ($this->error) {
				$result["status"] = 400;
				$result["msg"] = "操作失败";
				return $result;
			}
			if (empty($product["dcimid"])) {
				$result["status"] = 400;
				$result["msg"] = "云主机ID错误";
				return $result;
			}
			if (empty($post["id"])) {
				$result["status"] = 400;
				$result["msg"] = "参数错误";
				return $result;
			}
			$res = $this->curl("/disks/" . $post["id"] . "/snapshots", ["type" => "backup", "name" => $post["name"]]);
		}
		if ($res["status"] == "success" || $res["status"] == 200) {
			$result["status"] = 200;
			$result["msg"] = "备份创建任务发起成功，请稍后查看";
			$description = sprintf("备份创建任务发起成功 - Host ID:%d", $id);
		} else {
			$result["status"] = 400;
			$result["msg"] = "备份创建失败";
			if ($this->is_admin) {
				$result["msg"] = $this->log_prefix_error . $result["msg"];
				$description = $this->log_prefix_error . sprintf("备份创建失败,原因:%s - Host ID:%d", $res["msg"], $id);
			} else {
				$description = sprintf("备份创建失败 - Host ID:%d", $id);
			}
		}
		active_log_final($description, $product["uid"], 2, $id);
		return $result;
	}
	public function deleteBackup($id)
	{
		$post = input("post.");
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.api_type,b.zjmf_api_id")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if ($product["api_type"] == "zjmf_api") {
			$res = zjmfCurl($product["zjmf_api_id"], "/provision/custom/" . \intval($product["dcimid"]), $post);
		} else {
			$this->setUrl($product["serverid"]);
			if ($this->error) {
				$result["status"] = 400;
				$result["msg"] = "操作失败";
				return $result;
			}
			if (empty($product["dcimid"])) {
				$result["status"] = 400;
				$result["msg"] = "云主机ID错误";
				return $result;
			}
			if (empty($post["id"])) {
				$result["status"] = 400;
				$result["msg"] = "参数错误";
				return $result;
			}
			$res = $this->curl("/snapshots/" . $post["id"], [], 30, "DELETE");
		}
		if ($res["status"] == "success" || $res["status"] == 200) {
			$result["status"] = 200;
			$result["msg"] = "备份删除任务发起成功，请稍后查看";
			$description = sprintf("备份删除任务发起成功 - Host ID:%d", $id);
		} else {
			$result["status"] = 400;
			$result["msg"] = "备份删除失败";
			if ($this->is_admin) {
				$result["msg"] = $this->log_prefix_error . $result["msg"];
				$description = $this->log_prefix_error . sprintf("备份删除失败,原因:%s - Host ID:%d", $res["msg"], $id);
			} else {
				$description = sprintf("备份删除失败 - Host ID:%d", $id);
			}
		}
		active_log_final($description, $product["uid"], 2, $id);
		return $result;
	}
	public function restoreBackup($id)
	{
		$post = input("post.");
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.api_type,b.zjmf_api_id")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if ($product["api_type"] == "zjmf_api") {
			$res = zjmfCurl($product["zjmf_api_id"], "/provision/custom/" . \intval($product["dcimid"]), $post);
		} else {
			$this->setUrl($product["serverid"]);
			if ($this->error) {
				$result["status"] = 400;
				$result["msg"] = "操作失败";
				return $result;
			}
			if (empty($product["dcimid"])) {
				$result["status"] = 400;
				$result["msg"] = "云主机ID错误";
				return $result;
			}
			if (empty($post["id"])) {
				$result["status"] = 400;
				$result["msg"] = "参数错误";
				return $result;
			}
			$res = $this->curl("/snapshots/" . $post["id"] . "/restore?hostid=" . $product["dcimid"]);
		}
		if ($res["status"] == "success" || $res["status"] == 200) {
			$result["status"] = 200;
			$result["msg"] = "备份还原任务发起成功，请稍后查看";
			$description = sprintf("备份还原任务发起成功 - Host ID:%d", $id);
		} else {
			$result["status"] = 400;
			$result["msg"] = "备份还原失败";
			if ($this->is_admin) {
				$result["msg"] = $this->log_prefix_error . $result["msg"];
				$description = $this->log_prefix_error . sprintf("备份还原失败,原因:%s - Host ID:%d", $res["msg"], $id);
			} else {
				$description = sprintf("备份还原失败 - Host ID:%d", $id);
			}
		}
		active_log_final($description, $product["uid"], 2, $id);
		return $result;
	}
	public function mountIso($id)
	{
		$post = input("post.");
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.api_type,b.zjmf_api_id")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if ($product["api_type"] == "zjmf_api") {
			$res = zjmfCurl($product["zjmf_api_id"], "/provision/custom/" . \intval($product["dcimid"]), $post);
		} else {
			$this->setUrl($product["serverid"]);
			if ($this->error) {
				$result["status"] = 400;
				$result["msg"] = "操作失败";
				return $result;
			}
			if (empty($product["dcimid"])) {
				$result["status"] = 400;
				$result["msg"] = "云主机ID错误";
				return $result;
			}
			if (empty($post["id"])) {
				$result["status"] = 400;
				$result["msg"] = "参数错误";
				return $result;
			}
			$res = $this->curl("/clouds/" . $product["dcimid"] . "/iso", ["iso" => $post["id"]], 30, "POST");
		}
		if ($res["status"] == "success" || $res["status"] == 200) {
			$result["status"] = 200;
			$result["msg"] = "ISO挂载成功";
			$description = sprintf("ISO挂载成功 - Host ID:%d", $id);
		} else {
			$result["status"] = 400;
			$result["msg"] = "挂载失败,请关机后重试";
			if ($this->is_admin) {
				$result["msg"] = $this->log_prefix_error . $result["msg"];
				$description = $this->log_prefix_error . sprintf("ISO挂载失败,原因:%s - Host ID:%d", $res["msg"], $id);
			} else {
				$description = sprintf("ISO挂载失败 - Host ID:%d", $id);
			}
		}
		active_log_final($description, $product["uid"], 2, $id);
		return $result;
	}
	public function unmountIso($id)
	{
		$post = input("post.");
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.api_type,b.zjmf_api_id")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if ($product["api_type"] == "zjmf_api") {
			$res = zjmfCurl($product["zjmf_api_id"], "/provision/custom/" . \intval($product["dcimid"]), $post);
		} else {
			$this->setUrl($product["serverid"]);
			if ($this->error) {
				$result["status"] = 400;
				$result["msg"] = "操作失败";
				return $result;
			}
			if (empty($product["dcimid"])) {
				$result["status"] = 400;
				$result["msg"] = "云主机ID错误";
				return $result;
			}
			if (empty($post["id"])) {
				$result["status"] = 400;
				$result["msg"] = "参数错误";
				return $result;
			}
			$res = $this->curl("/clouds/" . $product["dcimid"] . "/iso", ["iso" => $post["id"]], 30, "DELETE");
		}
		if ($res["status"] == "success" || $res["status"] == 200) {
			$result["status"] = 200;
			$result["msg"] = "ISO卸载成功";
			$description = sprintf("ISO卸载成功 - Host ID:%d", $id);
		} else {
			$result["status"] = 400;
			$result["msg"] = "ISO卸载失败";
			if ($this->is_admin) {
				$result["msg"] = $this->log_prefix_error . $result["msg"];
				$description = $this->log_prefix_error . sprintf("ISO卸载失败,原因:%s - Host ID:%d", $res["msg"], $id);
			} else {
				$description = sprintf("ISO卸载失败 - Host ID:%d", $id);
			}
		}
		active_log_final($description, $product["uid"], 2, $id);
		return $result;
	}
	public function setBootOrder($id)
	{
		$post = input("post.");
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.api_type,b.zjmf_api_id")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if ($product["api_type"] == "zjmf_api") {
			$res = zjmfCurl($product["zjmf_api_id"], "/provision/custom/" . \intval($product["dcimid"]), $post);
		} else {
			$this->setUrl($product["serverid"]);
			if ($this->error) {
				$result["status"] = 400;
				$result["msg"] = "操作失败";
				return $result;
			}
			if (empty($product["dcimid"])) {
				$result["status"] = 400;
				$result["msg"] = "云主机ID错误";
				return $result;
			}
			if (empty($post["id"])) {
				$result["status"] = 400;
				$result["msg"] = "参数错误";
				return $result;
			}
			$res = $this->curl("/clouds/" . $product["dcimid"], ["bootorder" => $post["id"]], 30, "PUT");
		}
		if ($res["status"] == "success" || $res["status"] == 200) {
			$result["status"] = 200;
			$result["msg"] = "启动项设置成功";
			$description = sprintf("启动项设置成功 - Host ID:%d", $id);
		} else {
			$result["status"] = 400;
			$result["msg"] = "启动项设置失败";
			if ($this->is_admin) {
				$result["msg"] = $this->log_prefix_error . $result["msg"];
				$description = $this->log_prefix_error . sprintf("启动项设置失败,原因:%s - Host ID:%d", $res["msg"], $id);
			} else {
				$description = sprintf("启动项设置失败 - Host ID:%d", $id);
			}
		}
		active_log_final($description, $product["uid"], 2, $id);
		return $result;
	}
	public function createSecurityGroup($id)
	{
		$post = input("post.");
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.api_type,b.zjmf_api_id")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if ($product["api_type"] == "zjmf_api") {
			$res = zjmfCurl($product["zjmf_api_id"], "/provision/custom/" . \intval($product["dcimid"]), $post);
		} else {
			$this->setUrl($product["serverid"]);
			if ($this->error) {
				$result["status"] = 400;
				$result["msg"] = "操作失败";
				return $result;
			}
			if (empty($product["dcimid"])) {
				$result["status"] = 400;
				$result["msg"] = "云主机ID错误";
				return $result;
			}
			$detail = $this->curl("/clouds/" . $product["dcimid"], [], 30, "GET");
			if ($detail["status"] != "success") {
				$result["status"] = 400;
				$result["msg"] = $detail["msg"] ?: "创建失败";
				return $result;
			}
			$post_data["uid"] = $detail["data"]["user_id"];
			$post_data["name"] = $post["name"];
			$post_data["description"] = $post["description"];
			$post_data["type"] = $detail["data"]["type"] ?? "host";
			$res = $this->curl("/security_groups", $post_data, 30, "POST");
		}
		if ($res["status"] == "success" || $res["status"] == 200) {
			$result["status"] = 200;
			$result["msg"] = "创建成功";
			$description = sprintf("安全组创建成功 - Host ID:%d", $id);
		} else {
			$result["status"] = 400;
			$result["msg"] = "创建失败";
			if ($this->is_admin) {
				$result["msg"] = $this->log_prefix_error . $result["msg"];
				$description = $this->log_prefix_error . sprintf("安全组创建失败,原因:%s - Host ID:%d", $res["msg"], $id);
			} else {
				$description = sprintf("安全组创建失败 - Host ID:%d", $id);
			}
		}
		active_log_final($description, $product["uid"], 2, $id);
		return $result;
	}
	public function showSecurityRules($id)
	{
		$security_group_id = input("post.id");
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.api_type,b.zjmf_api_id")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if ($product["api_type"] == "zjmf_api") {
			$res = zjmfCurl($product["zjmf_api_id"], "/provision/custom/" . \intval($product["dcimid"]), input("post."));
		} else {
			$this->setUrl($product["serverid"]);
			if ($this->error) {
				$result["status"] = 400;
				$result["msg"] = "操作失败";
				return $result;
			}
			if (empty($product["dcimid"])) {
				$result["status"] = 400;
				$result["msg"] = "云主机ID错误";
				return $result;
			}
			$res = $this->curl("/security_groups/" . $security_group_id . "/rules", ["per_page" => 9999], 30, "GET");
		}
		if ($res["status"] == "success") {
			$result["status"] = 200;
			$result["list"] = $res["data"]["data"];
			foreach ($result["list"] as $k => $v) {
				if ($v["lock"] == 1) {
					unset($result["list"][$k]);
				}
			}
			$result["list"] = array_values($result["list"]);
		} elseif ($res["status"] == 200) {
			$result = $res;
		} else {
			$result["status"] = 400;
			$result["msg"] = "获取失败";
		}
		return $result;
	}
	public function createSecurityRule($id)
	{
		$post = input("post.");
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.api_type,b.zjmf_api_id")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if ($product["api_type"] == "zjmf_api") {
			$res = zjmfCurl($product["zjmf_api_id"], "/provision/custom/" . \intval($product["dcimid"]), $post);
		} else {
			$this->setUrl($product["serverid"]);
			if ($this->error) {
				$result["status"] = 400;
				$result["msg"] = "操作失败";
				return $result;
			}
			if (empty($product["dcimid"])) {
				$result["status"] = 400;
				$result["msg"] = "云主机ID错误";
				return $result;
			}
			$security_group_id = input("post.id");
			if (empty($security_group_id)) {
				$result["status"] = 400;
				$result["msg"] = "安全组ID错误";
				return $result;
			}
			$post_data = ["port" => $post["port"], "ip" => $post["ip"], "protocol" => $post["protocol"], "direction" => $post["direction"], "description" => $post["description"], "start_port" => $post["start_port"], "end_port" => $post["end_port"], "start_ip" => $post["start_ip"], "end_ip" => $post["end_ip"], "priority" => $post["priority"], "action" => $post["action"]];
			$detail = $this->curl("/clouds/" . $product["dcimid"], [], 30, "GET");
			if (!empty($detail["data"]["user_id"])) {
				$data = $this->curl("/security_groups", ["list_type" => "all", "user" => $detail["data"]["user_id"]], 30, "GET");
				$list = $data["data"]["data"] ?: [];
			} else {
				$list = [];
			}
			$arr = array_column($list, "id");
			if (!in_array($security_group_id, $arr)) {
				$result["status"] = 400;
				$result["msg"] = "安全组ID错误";
				return $result;
			}
			$res = $this->curl("/security_groups/" . $security_group_id . "/rules", $post_data, 30, "POST");
		}
		if ($res["status"] == "success" || $res["status"] == 200) {
			$result["status"] = 200;
			$result["msg"] = "创建成功";
			$description = sprintf("安全组规则创建成功 - Host ID:%d", $id);
		} else {
			$result["status"] = 400;
			$result["msg"] = $res["msg"] ?: "创建失败";
			if ($this->is_admin) {
				$result["msg"] = $this->log_prefix_error . $result["msg"];
				$description = $this->log_prefix_error . sprintf("安全组规则创建失败,原因:%s - Host ID:%d", $res["msg"], $id);
			} else {
				$description = sprintf("安全组规则创建失败 - Host ID:%d", $id);
			}
		}
		active_log_final($description, $product["uid"], 2, $id);
		return $result;
	}
	public function delSecurityGroup($id)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.api_type,b.zjmf_api_id")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if ($product["api_type"] == "zjmf_api") {
			$res = zjmfCurl($product["zjmf_api_id"], "/provision/custom/" . \intval($product["dcimid"]), input("post."));
		} else {
			$this->setUrl($product["serverid"]);
			if ($this->error) {
				$result["status"] = 400;
				$result["msg"] = "操作失败";
				return $result;
			}
			if (empty($product["dcimid"])) {
				$result["status"] = 400;
				$result["msg"] = "云主机ID错误";
				return $result;
			}
			$security_group_id = input("post.id");
			if (empty($security_group_id)) {
				$result["status"] = 400;
				$result["msg"] = "安全组ID错误";
				return $result;
			}
			$detail = $this->curl("/clouds/" . $product["dcimid"], [], 30, "GET");
			if (!empty($detail["data"]["user_id"])) {
				$data = $this->curl("/security_groups", ["list_type" => "all", "user" => $detail["data"]["user_id"]], 30, "GET");
				$list = $data["data"]["data"] ?: [];
			} else {
				$list = [];
			}
			$arr = array_column($list, "id");
			if (!in_array($security_group_id, $arr)) {
				$result["status"] = 400;
				$result["msg"] = "安全组ID错误";
				return $result;
			}
			$res = $this->curl("/security_groups/" . $security_group_id, [], 30, "DELETE");
		}
		if ($res["status"] == "success" || $res["status"] == 200) {
			$result["status"] = 200;
			$result["msg"] = "删除成功";
			$description = sprintf("安全组删除成功 - Host ID:%d", $id);
		} else {
			$result["status"] = 400;
			$result["msg"] = $res["msg"] ?: "创建失败";
			if ($this->is_admin) {
				$result["msg"] = $this->log_prefix_error . $result["msg"];
				$description = $this->log_prefix_error . sprintf("安全组删除失败,原因:%s - Host ID:%d", $res["msg"], $id);
			} else {
				$description = sprintf("安全组删除失败 - Host ID:%d", $id);
			}
		}
		active_log_final($description, $product["uid"], 2, $id);
		return $result;
	}
	public function delSecurityRule($id)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.api_type,b.zjmf_api_id")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if ($product["api_type"] == "zjmf_api") {
			$res = zjmfCurl($product["zjmf_api_id"], "/provision/custom/" . \intval($product["dcimid"]), input("post."));
		} else {
			$this->setUrl($product["serverid"]);
			if ($this->error) {
				$result["status"] = 400;
				$result["msg"] = "操作失败";
				return $result;
			}
			if (empty($product["dcimid"])) {
				$result["status"] = 400;
				$result["msg"] = "云主机ID错误";
				return $result;
			}
			$security_group_id = input("post.group");
			$rule_id = input("post.id");
			if (empty($security_group_id) || empty($rule_id)) {
				$result["status"] = 400;
				$result["msg"] = "参数错误";
				return $result;
			}
			$detail = $this->curl("/clouds/" . $product["dcimid"], [], 30, "GET");
			if (!empty($detail["data"]["user_id"])) {
				$data = $this->curl("/security_groups", ["list_type" => "all", "user" => $detail["data"]["user_id"]], 30, "GET");
				$list = $data["data"]["data"] ?: [];
			} else {
				$list = [];
			}
			$arr = array_column($list, "id");
			if (!in_array($security_group_id, $arr)) {
				$result["status"] = 400;
				$result["msg"] = "安全组ID错误";
				return $result;
			}
			$rules = $this->curl("/security_groups/" . $security_group_id . "/rules", ["per_page" => 9999], 30, "GET");
			$arr = array_column($rules["data"]["data"], "id");
			if (!in_array($rule_id, $arr)) {
				$result["status"] = 400;
				$result["msg"] = "安全组策略错误";
				return $result;
			}
			$res = $this->curl("/security_group_rules/" . $rule_id, [], 30, "DELETE");
		}
		if ($res["status"] == "success" || $res["status"] == 200) {
			$result["status"] = 200;
			$result["msg"] = "删除成功";
			$description = sprintf("安全组规则删除成功 - Host ID:%d", $id);
		} else {
			$result["status"] = 400;
			$result["msg"] = $res["msg"] ?: "创建失败";
			if ($this->is_admin) {
				$result["msg"] = $this->log_prefix_error . $result["msg"];
				$description = $this->log_prefix_error . sprintf("安全组规则删除失败,原因:%s - Host ID:%d", $res["msg"], $id);
			} else {
				$description = sprintf("安全组规则删除失败 - Host ID:%d", $id);
			}
		}
		active_log_final($description, $product["uid"], 2, $id);
		return $result;
	}
	public function linkSecurityGroup($id)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.api_type,b.zjmf_api_id")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if ($product["api_type"] == "zjmf_api") {
			$res = zjmfCurl($product["zjmf_api_id"], "/provision/custom/" . \intval($product["dcimid"]), input("post."));
		} else {
			$this->setUrl($product["serverid"]);
			if ($this->error) {
				$result["status"] = 400;
				$result["msg"] = "操作失败";
				return $result;
			}
			if (empty($product["dcimid"])) {
				$result["status"] = 400;
				$result["msg"] = "云主机ID错误";
				return $result;
			}
			$security_group_id = input("post.id");
			if (empty($security_group_id)) {
				$result["status"] = 400;
				$result["msg"] = "安全组ID错误";
				return $result;
			}
			$post_data["cloud"] = $product["dcimid"];
			$post_data["type"] = 1;
			$res = $this->curl("/security_groups/" . $security_group_id . "/links", $post_data, 30, "POST");
		}
		if ($res["status"] == "success" || $res["status"] == 200) {
			$result["status"] = 200;
			$result["msg"] = "应用成功";
			$description = sprintf("安全组应用成功 - Host ID:%d", $id);
		} else {
			$result["status"] = 400;
			$result["msg"] = $res["msg"] ?: "应用失败";
			if ($this->is_admin) {
				$result["msg"] = $this->log_prefix_error . $result["msg"];
				$description = $this->log_prefix_error . sprintf("安全组应用失败,原因:%s - Host ID:%d", $res["msg"], $id);
			} else {
				$description = sprintf("安全组应用失败 - Host ID:%d", $id);
			}
		}
		active_log_final($description, $product["uid"], 2, $id);
		return $result;
	}
	public function addNatAcl($id)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.api_type,b.zjmf_api_id")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if ($product["api_type"] == "zjmf_api") {
			$res = zjmfCurl($product["zjmf_api_id"], "/provision/custom/" . \intval($product["dcimid"]), input("post."));
		} else {
			$this->setUrl($product["serverid"]);
			if ($this->error) {
				$result["status"] = 400;
				$result["msg"] = "操作失败";
				return $result;
			}
			if (empty($product["dcimid"])) {
				$result["status"] = 400;
				$result["msg"] = "云主机ID错误";
				return $result;
			}
			$post_data = ["name" => input("post.name"), "ext_port" => input("post.ext_port", 0, "intval"), "int_port" => input("post.int_port", 0, "intval"), "protocol" => input("post.protocol", 0, "intval")];
			if (!input("post.protocol")) {
				$post_data["protocol"] = input("post.select-protocol", 0, "intval");
			}
			if (empty($post_data["ext_port"])) {
				unset($post_data["ext_port"]);
			}
			$res = $this->curl("/clouds/" . $product["dcimid"] . "/nat_acl", $post_data, 30, "POST");
		}
		if ($res["status"] == "success" || $res["status"] == 200) {
			$result["status"] = 200;
			$result["msg"] = "创建成功";
			$nat_acl = $this->curl("/clouds/" . $product["dcimid"] . "/nat_acl", ["per_page" => 1], 30, "GET");
			if ($nat_acl["status"] == "success") {
				$protocol = ["", "tcp", "udp", "tcp,udp"];
				$description = sprintf("NAT转发创建成功,名称:%s,转发IP及端口:%s,内部端口:%d,协议:%s - Host ID:%d", $post_data["name"], $nat_acl["data"]["nat_host_ip"] . ":" . $nat_acl["data"]["data"][0]["ext_port"], $nat_acl["data"]["data"][0]["int_port"], $protocol[$nat_acl["data"]["data"][0]["protocol"]], $id);
			} else {
				$description = sprintf("NAT转发创建成功,名称:%s - Host ID:%d", $post_data["name"], $id);
			}
		} else {
			$result["status"] = 400;
			$result["msg"] = $res["msg"] ?: "创建失败";
			if ($this->is_admin) {
				$result["msg"] = $this->log_prefix_error . $result["msg"];
				$description = $this->log_prefix_error . sprintf("NAT转发创建失败,原因:%s - Host ID:%d", $res["msg"], $id);
			} else {
				$description = sprintf("NAT转发创建失败 - Host ID:%d", $id);
			}
		}
		active_log_final($description, $product["uid"], 2, $id);
		return $result;
	}
	public function delNatAcl($id)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.api_type,b.zjmf_api_id")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if ($product["api_type"] == "zjmf_api") {
			$res = zjmfCurl($product["zjmf_api_id"], "/provision/custom/" . \intval($product["dcimid"]), input("post."));
		} else {
			$this->setUrl($product["serverid"]);
			if ($this->error) {
				$result["status"] = 400;
				$result["msg"] = "操作失败";
				return $result;
			}
			if (empty($product["dcimid"])) {
				$result["status"] = 400;
				$result["msg"] = "云主机ID错误";
				return $result;
			}
			$acl_id = input("post.id", 0, "intval");
			$res = $this->curl("/nat_acl/" . $acl_id . "?hostid=" . $params["dcimid"], [], 30, "DELETE");
		}
		if ($res["status"] == "success" || $res["status"] == 200) {
			$result["status"] = 200;
			$result["msg"] = "删除成功";
			$description = sprintf("NAT转发删除成功 - Host ID:%d", $id);
		} else {
			$result["status"] = 400;
			$result["msg"] = $res["msg"] ?: "删除失败";
			if ($this->is_admin) {
				$result["msg"] = $this->log_prefix_error . $result["msg"];
				$description = $this->log_prefix_error . sprintf("NAT转发删除失败,原因:%s - Host ID:%d", $res["msg"], $id);
			} else {
				$description = sprintf("NAT转发删除失败 - Host ID:%d", $id);
			}
		}
		active_log_final($description, $product["uid"], 2, $id);
		return $result;
	}
	public function addNatWeb($id)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.api_type,b.zjmf_api_id")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if ($product["api_type"] == "zjmf_api") {
			$res = zjmfCurl($product["zjmf_api_id"], "/provision/custom/" . \intval($product["dcimid"]), input("post."));
		} else {
			$this->setUrl($product["serverid"]);
			if ($this->error) {
				$result["status"] = 400;
				$result["msg"] = "操作失败";
				return $result;
			}
			if (empty($product["dcimid"])) {
				$result["status"] = 400;
				$result["msg"] = "云主机ID错误";
				return $result;
			}
			$post_data = ["domain" => input("post.domain"), "ext_port" => input("post.ext_port", 0, "intval"), "int_port" => input("post.int_port", 0, "intval")];
			$res = $this->curl("/clouds/" . $product["dcimid"] . "/nat_web", $post_data, 30, "POST");
		}
		if ($res["status"] == "success" || $res["status"] == 200) {
			$result["status"] = 200;
			$result["msg"] = "创建成功";
			$description = sprintf("共享建站创建成功,域名:%s,外部端口:%d,内部端口:%d - Host ID:%d", $post_data["domain"], $post_data["ext_port"], $post_data["int_port"], $id);
		} else {
			$result["status"] = 400;
			$result["msg"] = $res["msg"] ?: "创建失败";
			if ($this->is_admin) {
				$result["msg"] = $this->log_prefix_error . $result["msg"];
				$description = $this->log_prefix_error . sprintf("共享建站创建失败,原因:%s - Host ID:%d", $res["msg"], $id);
			} else {
				$description = sprintf("共享建站创建失败 - Host ID:%d", $id);
			}
		}
		active_log_final($description, $product["uid"], 2, $id);
		return $result;
	}
	public function delNatWeb($id)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.api_type,b.zjmf_api_id")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if ($product["api_type"] == "zjmf_api") {
			$res = zjmfCurl($product["zjmf_api_id"], "/provision/custom/" . \intval($product["dcimid"]), input("post."));
		} else {
			$this->setUrl($product["serverid"]);
			if ($this->error) {
				$result["status"] = 400;
				$result["msg"] = "操作失败";
				return $result;
			}
			if (empty($product["dcimid"])) {
				$result["status"] = 400;
				$result["msg"] = "云主机ID错误";
				return $result;
			}
			$web_id = input("post.id", 0, "intval");
			$res = $this->curl("/nat_web/" . $web_id . "?hostid=" . $params["dcimid"], [], 30, "DELETE");
		}
		if ($res["status"] == "success" || $res["status"] == 200) {
			$result["status"] = 200;
			$result["msg"] = "删除成功";
			$description = sprintf("共享建站删除成功 - Host ID:%d", $id);
		} else {
			$result["status"] = 400;
			$result["msg"] = $res["msg"] ?: "删除失败";
			if ($this->is_admin) {
				$result["msg"] = $this->log_prefix_error . $result["msg"];
				$description = $this->log_prefix_error . sprintf("共享建站删除失败,原因:%s - Host ID:%d", $res["msg"], $id);
			} else {
				$description = sprintf("共享建站删除失败 - Host ID:%d", $id);
			}
		}
		active_log_final($description, $product["uid"], 2, $id);
		return $result;
	}
	public function exitRescue($id)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.api_type,b.zjmf_api_id")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if ($product["api_type"] == "zjmf_api") {
			$res = zjmfCurl($product["zjmf_api_id"], "/provision/custom/" . \intval($product["dcimid"]), input("post."));
		} else {
			$this->setUrl($product["serverid"]);
			if ($this->error) {
				$result["status"] = 400;
				$result["msg"] = "操作失败";
				return $result;
			}
			if (empty($product["dcimid"])) {
				$result["status"] = 400;
				$result["msg"] = "云主机ID错误";
				return $result;
			}
			$res = $this->curl("/clouds/" . $product["dcimid"] . "/rescue", [], 30, "DELETE");
		}
		if ($res["status"] == "success" || $res["status"] == 200) {
			$result["status"] = 200;
			$result["msg"] = "退出救援系统发起成功";
			$description = sprintf("退出救援系统发起成功 - Host ID:%d", $id);
		} else {
			$result["status"] = 400;
			$result["msg"] = $res["msg"] ?: "退出失败";
			if ($this->is_admin) {
				$result["msg"] = $this->log_prefix_error . $result["msg"];
				$description = $this->log_prefix_error . sprintf("退出救援系统失败,原因:%s - Host ID:%d", $res["msg"], $id);
			} else {
				$description = sprintf("退出救援系统失败 - Host ID:%d", $id);
			}
		}
		active_log_final($description, $product["uid"], 2, $id);
		return $result;
	}
	public function remoteInfo($id)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.api_type,b.zjmf_api_id")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if ($product["api_type"] == "zjmf_api") {
			$res = zjmfCurl($product["zjmf_api_id"], "/provision/custom/" . \intval($product["dcimid"]), input("post."));
		} else {
			$this->setUrl($product["serverid"]);
			if ($this->error) {
				$result["status"] = 400;
				$result["msg"] = "操作失败";
				return $result;
			}
			if (empty($product["dcimid"])) {
				$result["status"] = 400;
				$result["msg"] = "云主机ID错误";
				return $result;
			}
			$res = $this->curl("/clouds/" . $product["dcimid"], [], 30, "GET");
		}
		if ($res["status"] == "success" || $res["status"] == 200) {
			$result["status"] = 200;
			$result["msg"] = "获取成功";
			$result["data"] = ["rescue" => \intval($res["data"]["rescue"])];
		} else {
			$result["status"] = 400;
			$result["msg"] = $res["msg"] ?: "获取失败";
		}
		return $result;
	}
	public function on($id)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		$this->setUrl($product["serverid"]);
		if ($this->error) {
			$result["status"] = 400;
			$result["msg"] = "操作失败";
			return $result;
		}
		if (empty($product["dcimid"])) {
			$result["status"] = 400;
			$result["msg"] = "云主机ID错误";
			return $result;
		}
		$res = $this->curl("/clouds/" . $product["dcimid"] . "/on");
		if ($res["status"] == "success") {
			$result["status"] = 200;
			$result["msg"] = "开机成功";
			$description = sprintf("开机成功 - Host ID:%d", $id);
		} else {
			$result["status"] = 400;
			$result["msg"] = "开机失败";
			if ($this->is_admin) {
				$result["msg"] = $this->log_prefix_error . $result["msg"];
				$description = $this->log_prefix_error . sprintf("开机失败,原因:%s - Host ID:%d", $res["msg"], $id);
			} else {
				$description = sprintf("开机失败 - Host ID:%d", $id);
			}
		}
		return $result;
	}
	public function off($id)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		$this->setUrl($product["serverid"]);
		if ($this->error) {
			$result["status"] = 400;
			$result["msg"] = "操作失败";
			return $result;
		}
		if (empty($product["dcimid"])) {
			$result["status"] = 400;
			$result["msg"] = "云主机ID错误";
			return $result;
		}
		$res = $this->curl("/clouds/" . $product["dcimid"] . "/off");
		if ($res["status"] == "success") {
			$result["status"] = 200;
			$result["msg"] = "关机成功";
			$description = sprintf("关机成功 - Host ID:%d", $id);
		} else {
			$result["status"] = 400;
			$result["msg"] = "关机失败";
			if ($this->is_admin) {
				$result["msg"] = $this->log_prefix_error . $result["msg"];
				$description = $this->log_prefix_error . sprintf("关机失败,原因:%s - Host ID:%d", $res["msg"], $id);
			} else {
				$description = sprintf("关机失败 - Host ID:%d", $id);
			}
		}
		return $result;
	}
	public function reboot($id)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		$this->setUrl($product["serverid"]);
		if ($this->error) {
			$result["status"] = 400;
			$result["msg"] = "操作失败";
			return $result;
		}
		if (empty($product["dcimid"])) {
			$result["status"] = 400;
			$result["msg"] = "云主机ID错误";
			return $result;
		}
		$res = $this->curl("/clouds/" . $product["dcimid"] . "/reboot");
		if ($res["status"] == "success") {
			$result["status"] = 200;
			$result["msg"] = "发起重启成功";
			$description = sprintf("发起重启成功 - Host ID:%d", $id);
		} else {
			$result["status"] = 400;
			$result["msg"] = "重启失败";
			if ($this->is_admin) {
				$result["msg"] = $this->log_prefix_error . $result["msg"];
				$description = $this->log_prefix_error . sprintf("重启失败,原因:%s - Host ID:%d", $res["msg"], $id);
			} else {
				$description = sprintf("重启失败 - Host ID:%d", $id);
			}
		}
		return $result;
	}
	public function hardOff($id)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		$this->setUrl($product["serverid"]);
		if ($this->error) {
			$result["status"] = 400;
			$result["msg"] = "操作失败";
			return $result;
		}
		if (empty($product["dcimid"])) {
			$result["status"] = 400;
			$result["msg"] = "云主机ID错误";
			return $result;
		}
		$res = $this->curl("/clouds/" . $product["dcimid"] . "/hardoff");
		if ($res["status"] == "success") {
			$result["status"] = 200;
			$result["msg"] = "发起硬关机成功";
			$description = sprintf("发起硬关机成功 - Host ID:%d", $id);
		} else {
			$result["status"] = 400;
			$result["msg"] = "硬关机失败";
			if ($this->is_admin) {
				$result["msg"] = $this->log_prefix_error . $result["msg"];
				$description = $this->log_prefix_error . sprintf("硬关机失败,原因:%s - Host ID:%d", $res["msg"], $id);
			} else {
				$description = sprintf("硬关机失败 - Host ID:%d", $id);
			}
		}
		return $result;
	}
	public function hardReboot($id)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		$this->setUrl($product["serverid"]);
		if ($this->error) {
			$result["status"] = 400;
			$result["msg"] = "操作失败";
			return $result;
		}
		if (empty($product["dcimid"])) {
			$result["status"] = 400;
			$result["msg"] = "云主机ID错误";
			return $result;
		}
		$res = $this->curl("/clouds/" . $product["dcimid"] . "/hard_reboot");
		if ($res["status"] == "success") {
			$result["status"] = 200;
			$result["msg"] = "发起硬重启成功";
			$description = sprintf("发起硬重启成功 - Host ID:%d", $id);
		} else {
			$result["status"] = 400;
			$result["msg"] = "硬重启失败";
			if ($this->is_admin) {
				$result["msg"] = $this->log_prefix_error . $result["msg"];
				$description = $this->log_prefix_error . sprintf("硬重启失败,原因:%s - Host ID:%d", $res["msg"], $id);
			} else {
				$description = sprintf("硬重启失败 - Host ID:%d", $id);
			}
		}
		return $result;
	}
	public function vnc($id)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.password")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		$this->setUrl($product["serverid"]);
		if ($this->error) {
			$result["status"] = 400;
			$result["msg"] = "操作失败";
			return $result;
		}
		if (empty($product["dcimid"])) {
			$result["status"] = 400;
			$result["msg"] = "云主机ID错误";
			return $result;
		}
		$res = $this->curl("/clouds/" . $product["dcimid"] . "/vnc");
		if ($res["status"] == "success") {
			$result["status"] = 200;
			$result["msg"] = $res["msg"];
			$link_url = "";
			if (!empty($res["data"]["vnc_url_http"]) && !empty($res["data"]["vnc_url_https"])) {
				if (request()->scheme() == "https") {
					$result["url"] = $res["data"]["vnc_url_https"];
				} else {
					$result["url"] = $res["data"]["vnc_url_http"];
				}
				$result["out"] = true;
			} else {
				if (strpos($res["data"]["vnc_url"], "wss://") === 0 || strpos($res["data"]["vnc_url"], "ws://") === 0) {
					$link_url = $res["data"]["vnc_url"];
				} else {
					if (strpos($this->url, "https://") !== false) {
						$link_url = str_replace("https://", "wss://", $this->url);
					} else {
						$link_url = str_replace("http://", "ws://", $this->url);
					}
					$link_url = rtrim($link_url, "/");
					if (substr_count($link_url, "/") > 2) {
						$link_url = substr($link_url, 0, strrpos($link_url, "/"));
					}
					$link_url .= "/cloud_ws1?token=" . $res["data"]["token"];
				}
				if ($this->is_admin) {
					$result["url"] = request()->domain() . "/" . config("database.admin_application") . "/dcim/novnc?url=" . urlencode(base64_encode($link_url)) . "&password=" . $res["data"]["vnc_pass"] . "&host_token=" . urlencode(aesPasswordEncode(cmf_decrypt($product["password"])));
				} else {
					$result["url"] = request()->domain() . "/dcim/novnc?url=" . urlencode(base64_encode($link_url)) . "&password=" . $res["data"]["vnc_pass"] . "&host_token=" . urlencode(aesPasswordEncode(cmf_decrypt($product["password"])));
				}
				$result["out"] = false;
			}
		} else {
			$result["status"] = 400;
			$result["msg"] = $res["msg"];
		}
		if ($this->is_admin && $result["status"] != 200) {
			$result["msg"] = $this->log_prefix_error . $result["msg"];
		}
		return $result;
	}
	public function reinstall($id, $os, $port = 0)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,a.reinstall_info")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		$this->setUrl($product["serverid"]);
		if ($this->error) {
			$result["status"] = 400;
			$result["msg"] = "操作失败";
			return $result;
		}
		if (empty($product["dcimid"])) {
			$result["status"] = 400;
			$result["msg"] = "云主机ID错误";
			return $result;
		}
		$dcim_server = \think\Db::name("dcim_servers")->where("serverid", $product["serverid"])->find();
		if (!$this->is_admin && $dcim_server["reinstall_times"] > 0) {
			$reinstall_info = json_decode($product["reinstall_info"], true);
			$num = $reinstall_info["num"] ?? 0;
			if (empty($reinstall_info) || strtotime("this week Monday") > strtotime($reinstall_info["date"])) {
				$num = 0;
			}
			if ($dcim_server["buy_times"] == 1) {
				$buy_times = get_buy_reinstall_times($product["uid"], $id);
			} else {
				$buy_times = 0;
			}
			if ($dcim_server["reinstall_times"] > 0 && $dcim_server["reinstall_times"] + $buy_times <= $num) {
				if ($dcim_server["buy_times"] > 0) {
					$result["status"] = 400;
					$result["msg"] = "可以购买重装次数";
					$result["price"] = $dcim_server["reinstall_price"];
				} else {
					$result["status"] = 400;
					$result["msg"] = "本周重装次数已达最大限额，请下周重试或联系技术支持";
				}
				return $result;
			}
		}
		$post_data["os"] = $os;
		if ($port > 0 && $port <= 65535) {
			$post_data["port"] = $port;
		}
		$post_data["format_data_disk"] = input("post.format_data_disk", 0, "intval");
		$system_disk_size_config_options = \think\Db::name("host_config_options")->field("a.id,a.qty,b.option_name,b.option_type,c.option_name sub_option,b.upgrade")->alias("a")->leftJoin("product_config_options b", "a.configid=b.id")->leftJoin("product_config_options_sub c", "a.configid=c.config_id and a.optionid=c.id")->where("a.relid", $id)->whereLike("b.option_name", "system_disk_size|%")->find();
		if ($system_disk_size_config_options["option_type"] == 1 || $system_disk_size_config_options["option_type"] == 13) {
			$sub_option = explode("|", $system_disk_size_config_options["sub_option"])[0];
			if (strpos($sub_option, ",") !== false) {
				if (strpos($sub_option, "lin:") !== false && strpos($sub_option, "win:") !== false) {
					$sub_option = explode(",", $sub_option);
					foreach ($sub_option as $v) {
						if (strpos($v, "lin:") !== false) {
							$lin_size = \intval(str_replace("lin:", "", $v));
						} elseif (strpos($v, "win:") !== false) {
							$win_size = \intval(str_replace("win:", "", $v));
						}
					}
					$post_data["system_disk_size"] = [$win_size, $lin_size];
				} else {
					$sub_option = explode(",", $sub_option);
					$post_data["system_disk_size"] = \intval($sub_option[0]);
				}
			} else {
				$post_data["system_disk_size"] = \intval($sub_option);
			}
		} else {
			if ($system_disk_size_config_options["option_type"] == 14) {
				$post_data["system_disk_size"] = $system_disk_size_config_options["qty"];
			}
		}
		$res = $this->curl("/clouds/" . $product["dcimid"] . "/reinstall", $post_data, 20, "PUT");
		if ($res["status"] == "success") {
			$result["status"] = 200;
			$result["msg"] = "重装发起成功";
			if (!empty($res["data"]["password"]) && !empty($res["data"]["user"])) {
				$update = [];
				$update["password"] = cmf_encrypt($res["data"]["password"]);
				$update["username"] = $res["data"]["user"];
				if (!empty($post_data["port"])) {
					$update["port"] = $port;
				}
				\think\Db::name("host")->where("id", $id)->update($update);
			}
			if (!$this->is_admin && $dcim_server["reinstall_times"] > 0) {
				$reinstall_info = json_decode($product["reinstall_info"], true);
				if (empty($reinstall_info)) {
					$d = ["num" => 1, "date" => date("Y-m-d")];
				} else {
					if (strtotime("this week Monday") > strtotime($reinstall_info["date"])) {
						$num = 1;
					} else {
						$num = $reinstall_info["num"] + 1;
					}
					$d = ["num" => $num, "date" => date("Y-m-d")];
				}
				\think\Db::name("host")->where("id", $id)->update(["reinstall_info" => json_encode($d)]);
			}
		} else {
			$result["status"] = 400;
			$result["msg"] = "重装失败";
			if ($this->is_admin) {
				$result["msg"] .= "原因:" . $res["msg"];
				$result["msg"] = $this->log_prefix_error . $result["msg"];
				$description = $this->log_prefix_error . sprintf("重装系统发起失败,原因:%s - Host ID:%d", $res["msg"], $id);
			} else {
				$description = sprintf("重装系统发起失败 - Host ID:%d", $id);
			}
		}
		return $result;
	}
	public function sync($id)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,a.productid")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		$this->setUrl($product["serverid"]);
		if ($this->error) {
			$result["status"] = 400;
			$result["msg"] = "操作失败";
			return $result;
		}
		if (empty($product["dcimid"])) {
			$result["status"] = 400;
			$result["msg"] = "云主机ID错误";
			return $result;
		}
		$res = $this->curl("/clouds/" . $product["dcimid"], [], 20, "GET");
		if ($res["status"] == "success") {
			$update["dedicatedip"] = $res["data"]["mainip"] ?: "";
			if ($res["data"]["client_show_ip_remark"] == 1) {
				$update["assignedips"] = [];
				foreach ($res["data"]["ip"] as $v) {
					$update["assignedips"][] = !empty($v["remark"]) ? $v["ipaddress"] . "(" . str_replace(",", "，", $v["remark"]) . ")" : $v["ipaddress"];
				}
			} else {
				$update["assignedips"] = array_column($res["data"]["ip"], "ipaddress") ?? [];
			}
			$update["assignedips"] = array_merge($update["assignedips"], array_column($res["data"]["ipv6"], "ipv6") ?? []);
			$update["assignedips"] = implode(",", array_filter($update["assignedips"], function ($x) use($res) {
				return $x != $res["data"]["mainip"];
			}));
			$update["username"] = $res["data"]["osuser"];
			$update["password"] = cmf_encrypt($res["data"]["rootpassword"]);
			if ($res["data"]["traffic_quota"] == 0) {
				$update["bwlimit"] = 0;
			} else {
				$update["bwlimit"] = $res["data"]["traffic_quota"] + $res["data"]["tmp_traffic"];
			}
			$update["domain"] = $res["data"]["hostname"];
			$configoptions = \think\Db::name("product_config_links")->alias("a")->leftJoin("product_config_options b", "a.gid=b.gid")->where("a.pid", $product["productid"])->where("b.option_type", 5)->value("b.id");
			if (!empty($configoptions)) {
				$sub = \think\Db::name("product_config_options_sub")->field("id,config_id,option_name")->whereLike("option_name", $res["data"]["system"] . "|%")->where("config_id", $configoptions)->find();
				if (!empty($sub)) {
					\think\Db::name("host_config_options")->where("relid", $id)->where("configid", $configoptions)->update(["optionid" => $sub["id"], "qty" => 0]);
					$os = explode("|", $sub["option_name"])[1];
					if (strpos($os, "^") !== false) {
						$os_arr = explode("^", $os);
						$update["os_url"] = $os_arr[0] ?: "";
						$update["os"] = $os_arr[1] ?: "";
					} else {
						$update["os"] = $os;
					}
				}
			}
			$update["port"] = \intval($res["data"]["port"]);
			\think\Db::name("host")->where("id", $id)->update($update);
			if (!empty($res["data"]["panel_pass"])) {
				$this->savePanelPass($id, $product["productid"], $res["data"]["panel_pass"]);
			}
			$result["status"] = 200;
			$result["msg"] = "同步成功";
		} else {
			$result["status"] = 400;
			$result["msg"] = $res["msg"];
			if ($this->is_admin) {
				$result["msg"] = $this->log_prefix_error . $result["msg"];
			}
		}
		return $result;
	}
	public function buyFlowPacket($params)
	{
		$post["traffic"] = \intval($params["capacity"]);
		$res = $this->curl("/clouds/" . $params["dcimid"] . "/temp_traffic", $post, 30, "PUT");
		if ($res["status"] == "success") {
			$description = sprintf("流量包购买成功，已成功附加到产品。- Invoice ID:%d - Host ID:%d", $params["invoiceid"], $params["hostid"]);
			$info = $this->curl("/net_info?host_id=" . $params["dcimid"], [], 30, "GET");
			if ($params["nextduedate"] == 0 || date("Ymd") < date("Ymd", $params["nextduedate"])) {
				if ($info["status"] == "success" && $info["data"]["info"]["30_day"]["float"] < 100) {
					$host = new Host();
					$host->is_admin = $this->is_admin;
					$result = $host->unsuspend($params["hostid"]);
					$logic_run_map = new RunMap();
					$model_host = new \app\common\model\HostModel();
					$data_i = [];
					$data_i["host_id"] = $params["hostid"];
					$data_i["active_type_param"] = [$params["hostid"], 0, "", 0];
					$is_zjmf = $model_host->isZjmfApi($data_i["host_id"]);
					if ($result["status"] == 200) {
						$data_i["description"] = " 购买流量包后 - 解除暂停 Host ID:{$data_i["host_id"]}的产品成功";
						if ($is_zjmf) {
							$logic_run_map->saveMap($data_i, 1, 400, 3);
						}
						if (!$is_zjmf) {
							$logic_run_map->saveMap($data_i, 1, 300, 3);
						}
					} else {
						$data_i["description"] = " 购买流量包后 - 解除暂停 Host ID:{$data_i["host_id"]}的产品失败：{$result["msg"]}";
						if ($is_zjmf) {
							$logic_run_map->saveMap($data_i, 0, 400, 3);
						}
						if (!$is_zjmf) {
							$logic_run_map->saveMap($data_i, 0, 300, 3);
						}
					}
				}
			}
			if ($info["status"] == "success") {
				if ($info["data"]["meta"]["traffic_quota"] != 0) {
					$info["data"]["meta"]["traffic_quota"] += $info["data"]["meta"]["tmp_traffic"];
				}
				$update["bwlimit"] = \intval($info["data"]["meta"]["traffic_quota"]);
				$usage = 0;
				if ($info["data"]["meta"]["traffic_type"] == 1) {
					$usage = round($info["data"]["info"]["30_day"]["accept"] / pow(1024, 3), 2);
				} elseif ($info["data"]["meta"]["traffic_type"] == 2) {
					$usage = round($info["data"]["info"]["30_day"]["send"] / pow(1024, 3), 2);
				} elseif ($info["data"]["meta"]["traffic_type"] == 3) {
					$usage = round($info["data"]["info"]["30_day"]["total"] / pow(1024, 3), 2);
				}
				$update["bwusage"] = $usage;
				$update["lastupdate"] = time();
				\think\Db::name("host")->where("id", $params["hostid"])->update($update);
			}
		} else {
			$description = sprintf("流量包购买成功，附加到产品失败，请手动添加临时流量。- Invoice ID:%d - Host ID:%d", $params["invoiceid"], $params["hostid"]);
		}
		$reshost = \think\Db::name("host")->field("uid")->where("id", $params["hostid"])->find();
		active_log_final($description, $reshost["uid"], 2, $params["hostid"]);
		return $res;
	}
	public function getCloudStatus($id)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		$this->setUrl($product["serverid"]);
		if (empty($product["serverid"]) || $this->error) {
			$result["status"] = 400;
			$result["msg"] = "不能执行该操作";
			return $result;
		}
		if (empty($product["dcimid"])) {
			$result["status"] = 400;
			$result["msg"] = "不能执行该操作";
			return $result;
		}
		$res = $this->curl("/clouds/" . $product["dcimid"] . "/status", [], 20, "GET");
		if ($res["status"] == "success") {
			$result["status"] = 200;
			if ($res["data"]["status"] == "on") {
				$result["data"]["status"] = "on";
				$result["data"]["des"] = "开机";
			} elseif ($res["data"]["status"] == "off") {
				$result["data"]["status"] = "off";
				$result["data"]["des"] = "关机";
			} elseif ($res["data"]["status"] == "suspend") {
				$result["data"]["status"] = "suspend";
				$result["data"]["des"] = "暂停";
			} elseif ($res["data"]["status"] == "wait_reboot") {
				$result["data"]["status"] = "waiting";
				$result["data"]["des"] = "等待重启";
			} elseif ($res["data"]["status"] == "task") {
				$result["data"]["status"] = "process";
				$result["data"]["des"] = $res["data"]["task_name"] . "中";
			} elseif ($res["data"]["status"] == "paused") {
				$result["data"]["status"] = "paused";
				$result["data"]["des"] = "挂起";
			} elseif ($res["data"]["status"] == "cold_migrate") {
				$result["data"]["status"] = "cold_migrate";
				$result["data"]["des"] = "冷迁移中";
			} elseif ($res["data"]["status"] == "hot_migrate") {
				$result["data"]["status"] = "hot_migrate";
				$result["data"]["des"] = "热迁移中";
			} else {
				$result["data"]["status"] = "unknown";
				$result["data"]["des"] = "未知";
			}
		} else {
			if ($this->is_admin) {
				$result["status"] = 400;
				$result["msg"] = $res["msg"];
				$result["msg"] = $this->log_prefix_error . $result["msg"];
			} else {
				$result["status"] = 200;
				$result["data"]["status"] = "unknown";
				$result["data"]["des"] = "未知";
			}
		}
		return $result;
	}
	public function suspend($id, $reason_type = "")
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if (empty($product["dcimid"])) {
			$result["status"] = 200;
			$result["msg"] = "暂停成功";
			return $result;
		}
		$this->setUrl($product["serverid"]);
		if ($this->error) {
			$result["status"] = 400;
			$result["msg"] = "操作失败";
			return $result;
		}
		if ($reason_type == "flow") {
			$post_data["type"] = "traffic";
		} elseif ($reason_type == "due") {
			$post_data["type"] = "due";
		} else {
			$post_data["type"] = "other";
		}
		$res = $this->curl("/clouds/" . $product["dcimid"] . "/suspend", $post_data);
		if ($res["status"] == "success") {
			$result["status"] = 200;
			$result["msg"] = "暂停成功";
		} else {
			$result["msg"] = $res["msg"];
			$res = $this->curl("/clouds/" . $product["dcimid"] . "/status", [], 20, "GET");
			if ($res["status"] == "success") {
				if ($res["data"]["status"] == "suspend") {
					$result["status"] = 200;
					$result["msg"] = "暂停成功";
				} else {
					if ($res["data"]["status"] == "task" && $res["data"]["task"] == "suspend") {
						$result["status"] = 200;
						$result["msg"] = "暂停成功";
					} else {
						$result["status"] = 400;
					}
				}
			} else {
				$result["status"] = 400;
			}
			if ($this->is_admin && $result["status"] != 200) {
				$result["msg"] = $this->log_prefix_error . $result["msg"];
			}
		}
		return $result;
	}
	public function unsuspend($id)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if (empty($product["dcimid"])) {
			$result["status"] = 200;
			$result["msg"] = "解除暂停成功";
			return $result;
		}
		$this->setUrl($product["serverid"]);
		if ($this->error) {
			$result["status"] = 400;
			$result["msg"] = "操作失败";
			return $result;
		}
		$post_data["id"] = $product["dcimid"];
		$post_data["hostid"] = $id;
		$res = $this->curl("/clouds/" . $product["dcimid"] . "/unsuspend");
		if ($res["status"] == "success") {
			$result["status"] = 200;
			$result["msg"] = "解除暂停成功";
		} else {
			$result["msg"] = $res["msg"];
			$res = $this->curl("/clouds/" . $product["dcimid"], [], 20, "GET");
			if ($res["status"] == "success") {
				if ($res["data"]["status"] != "suspend") {
					$result["status"] = 200;
					$result["msg"] = "解除暂停成功";
				} else {
					$result["status"] = 400;
				}
			} else {
				$result["status"] = 400;
			}
			if ($this->is_admin && $result["status"] != 200) {
				$result["msg"] = $this->log_prefix_error . $result["msg"];
			}
		}
		return $result;
	}
	public function terminate($id)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,a.productid")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if (empty($product["dcimid"])) {
			$result["status"] = 200;
			$result["msg"] = "删除成功";
			return $result;
		}
		$this->setUrl($product["serverid"]);
		if ($this->error) {
			$result["status"] = 400;
			$result["msg"] = "操作失败";
			return $result;
		}
		$res = $this->curl("/clouds/" . $product["dcimid"], [], 30, "DELETE");
		if ($res["status"] == "success" || $res["http_code"] == 404) {
			$this->savePanelPass($id, $product["productid"], "");
			$result["status"] = 200;
			$result["msg"] = "删除成功";
		} else {
			$result["status"] = 400;
			$result["msg"] = $res["msg"];
			if ($this->is_admin) {
				$result["msg"] = $this->log_prefix_error . $result["msg"];
			}
		}
		return $result;
	}
	public function moduleClientButton($id)
	{
		if (empty($id)) {
			return ["control" => [], "console" => []];
		}
		$button = ["control" => [["type" => "default", "func" => "on", "name" => "开机"], ["type" => "default", "func" => "off", "name" => "关机"], ["type" => "default", "func" => "reboot", "name" => "重启"], ["type" => "default", "func" => "hard_off", "name" => "硬关机"], ["type" => "default", "func" => "hard_reboot", "name" => "硬重启"], ["type" => "default", "func" => "reinstall", "name" => "重装系统"], ["type" => "default", "func" => "crack_pass", "name" => "重置密码"], ["type" => "default", "func" => "rescue_system", "name" => "救援系统"], ["type" => "custom", "func" => "exitRescue", "name" => "退出救援系统"]], "console" => [["type" => "default", "func" => "vnc", "name" => "VNC"]]];
		return $button;
	}
	public function execCustomButton($id, $func = "")
	{
		if (!in_array($func, ["resume", "exit_rescue"])) {
			$result["status"] = 400;
			$result["msg"] = lang("NO_SUPPORT_FUNCTION");
			return $result;
		}
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.domain,a.dcimid,a.uid")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if ($func == "resume") {
			$result["status"] = 400;
			$result["msg"] = lang("NO_SUPPORT_FUNCTION");
		} elseif ($func == "download_rdp") {
			if (empty($product["dcimid"])) {
				$result["status"] = 400;
				$result["msg"] = "下载失败";
				return $result;
			}
			$this->setUrl($product["serverid"]);
			if ($this->error) {
				$result["status"] = 400;
				$result["msg"] = "下载失败";
				return $result;
			}
			$res = $this->curl("/clouds/" . $product["dcimid"] . "/download_rdp");
			if ($res["status"] == "success") {
				$result["status"] = 200;
				$result["msg"] = "获取成功";
				$result["data"]["module_action"] = "download";
				$result["data"]["content"] = $res["origin"];
				$result["data"]["name"] = $product["domain"] ?: "remote.rdp";
			} else {
				$result["status"] = 400;
				$result["msg"] = $res["msg"];
			}
		} elseif ($func == "exit_rescue") {
			$result = $this->exitRescue($id);
		} else {
			$result["status"] = 400;
			$result["msg"] = lang("NO_SUPPORT_FUNCTION");
		}
		if ($this->is_admin && $result["status"] != 200) {
			$result["msg"] = $this->log_prefix_error . $result["msg"];
		}
		return $result;
	}
	public function upgrade($id, $old)
	{
		$mod = new \app\common\model\HostModel();
		$params = $mod->getProvisionParams($id);
		if (empty($params["dcimid"])) {
			$result["status"] = "error";
			$result["msg"] = "数据获取失败";
			return $result;
		}
		$this->setUrl($params["serverid"]);
		if (empty($params["configoptions_upgrade"])) {
			$result["status"] = "error";
			$result["msg"] = "没有可配置升级项";
			return $result;
		}
		$detail = $this->curl("/clouds/" . $params["dcimid"], [], 20, "GET");
		if ($detail["status"] != "success") {
			$description = sprintf("魔方云产品升降级失败,原因:%s - Host ID: %d", "远程数据获取失败", $id);
			active_log_final($description, $params["user_info"]["id"], 2, $id);
			$result["status"] = "error";
			$result["msg"] = $detail["msg"];
			return $result;
		}
		$detail = $detail["data"];
		$needOff = false;
		$waitOff = function () use($params, $detail) {
			$result = false;
			if ($detail["status"] == "on" || $detail["status"] == "wait_reboot") {
				$off = false;
				for ($times = 0; $times < 3; $times++) {
					if ($off) {
						break;
					}
					$this->curl("/clouds/" . $params["dcimid"] . "/off", [], 10, "POST");
					for ($i = 0; $i < 12; $i++) {
						$cloud_status = $this->curl("/clouds/" . $params["dcimid"] . "/status", [], 30, "GET");
						if ($cloud_status["data"]["status"] == "off") {
							$off = true;
							break;
						} else {
							if ($cloud_status["data"]["status"] == "task" && $cloud_status["data"]["task"] != "off") {
								break;
							}
						}
						sleep(5);
					}
				}
				if (!$off) {
					$this->curl("/clouds/" . $params["dcimid"] . "/hardoff", [], 10, "POST");
					for ($i = 0; $i < 18; $i++) {
						$cloud_status = $this->curl("/clouds/" . $params["dcimid"] . "/status", [], 30, "GET");
						if ($cloud_status["data"]["status"] == "off") {
							$off = true;
							break;
						}
						sleep(10);
					}
				}
				$result = true;
			} else {
				if ($detail["status"] == "paused") {
					$this->curl("/clouds/" . $params["dcimid"] . "/hardoff", [], 10, "POST");
					for ($i = 0; $i < 18; $i++) {
						$cloud_status = $this->curl("/clouds/" . $params["dcimid"] . "/status", [], 30, "GET");
						if ($cloud_status["data"]["status"] == "off") {
							break;
						}
						sleep(10);
					}
					$result = true;
				}
			}
			return $result;
		};
		$post_data = [];
		if (isset($params["configoptions_upgrade"]["cpu"]) && $params["configoptions_upgrade"]["cpu"] != $detail["cpu"]) {
			if (!$needOff) {
				$needOff = $waitOff();
			}
			$post_data["cpu"] = $params["configoptions"]["cpu"];
		}
		if (isset($params["configoptions_upgrade"]["memory"]) && ($params["configoptions_upgrade"]["memory"] / 1024 != $detail["memory"] || $params["configoptions_upgrade"]["memory"] != $old["configoptions"]["memory"])) {
			if (!$needOff) {
				$needOff = $waitOff();
			}
			$post_data["memory"] = \intval($params["configoptions"]["memory"]);
			$memory_config_options = \think\Db::name("host_config_options")->field("a.id,a.qty,b.option_name,b.option_type,c.option_name sub_option,b.upgrade")->alias("a")->leftJoin("product_config_options b", "a.configid=b.id")->leftJoin("product_config_options_sub c", "a.configid=c.config_id and a.optionid=c.id")->where("a.relid", $id)->whereLike("b.option_name", "memory|%")->find();
			if ($memory_config_options["option_type"] == 1 || $memory_config_options["option_type"] == 8) {
				$sub_option = explode("|", $memory_config_options["sub_option"])[0];
				if (strpos($sub_option, ",") !== false) {
					$sub_option = explode(",", $sub_option);
					$unit = strtolower($sub_option[1]);
					$post_data["memory"] = \intval($sub_option[0]);
					if ($unit == "g" || $unit == "gb") {
						$post_data["memory"] *= 1024;
					}
				}
			} else {
				if ($memory_config_options["option_type"] == 9 || $memory_config_options["option_type"] == 17) {
					$sub_option = explode("|", $memory_config_options["sub_option"])[0];
					$post_data["memory"] = $memory_config_options["qty"];
					if (strpos($sub_option, ",") !== false) {
						$sub_option = explode(",", $sub_option);
						$unit = strtolower($sub_option[1]);
						if ($unit == "g" || $unit == "gb") {
							$post_data["memory"] *= 1024;
						}
					}
				}
			}
		}
		if (isset($params["configoptions_upgrade"]["bw"])) {
			$post_data["in_bw"] = \intval($params["configoptions"]["bw"]);
			$post_data["out_bw"] = \intval($params["configoptions"]["bw"]);
			if (isset($params["configoptions"]["in_bw"])) {
				$post_data["in_bw"] = \intval($params["configoptions"]["in_bw"]);
			}
		}
		if (isset($params["configoptions_upgrade"]["in_bw"])) {
			$post_data["in_bw"] = \intval($params["configoptions"]["in_bw"]);
		}
		if (isset($params["configoptions_upgrade"]["flow_limit"])) {
			$post_data["traffic_quota"] = $params["configoptions"]["flow_limit"];
		}
		if (isset($params["configoptions_upgrade"]["backup_num"])) {
			$post_data["backup_num"] = $params["configoptions"]["backup_num"];
		}
		if (isset($params["configoptions_upgrade"]["snap_num"])) {
			$post_data["snap_num"] = $params["configoptions"]["snap_num"];
		}
		if (!empty($post_data)) {
			$res1 = $this->curl("/clouds/" . $params["dcimid"], $post_data, 5, "PUT");
			if ($res1["status"] == "success") {
			}
			$this->curl("/clouds/" . $params["dcimid"] . "/bw", $post_data, 5, "PUT");
		}
		if (isset($params["configoptions_upgrade"]["ip_num"]) && ($params["configoptions_upgrade"]["ip_num"] != $old["configoptions"]["ip_num"] || $params["configoptions_upgrade"]["ip_num"] != $detail["ip_num"])) {
			if (!$needOff && $detail["data"]["network_type"] == "normal") {
				$needOff = $waitOff();
			}
			$res3 = $this->curl("/clouds/" . $params["dcimid"] . "/ip", ["num" => $params["configoptions"]["ip_num"], "ip_group" => $params["configoptions"]["ip_group"]], 20, "PUT");
		}
		if (isset($params["configoptions_upgrade"]["data_disk_size"]) && $params["configoptions_upgrade"]["data_disk_size"] != $old["configoptions"]["data_disk_size"]) {
			$disk_id = 0;
			$store_id = 0;
			$data_disk_size_config_options = \think\Db::name("host_config_options")->field("a.id,a.qty,b.option_name,b.option_type,c.option_name sub_option,b.upgrade")->alias("a")->leftJoin("product_config_options b", "a.configid=b.id")->leftJoin("product_config_options_sub c", "a.configid=c.config_id and a.optionid=c.id")->where("a.relid", $id)->whereLike("b.option_name", "data_disk_size|%")->find();
			if ($data_disk_size_config_options["option_type"] == 1 || $data_disk_size_config_options["option_type"] == 13) {
				$sub_option = explode("|", $data_disk_size_config_options["sub_option"])[0];
				if (strpos($sub_option, ",") !== false) {
					$sub_option = explode(",", $sub_option);
					$store_id = \intval($sub_option[1]);
				}
			} else {
				if ($data_disk_size_config_options["option_type"] == 14) {
					$sub_option = explode("|", $data_disk_size_config_options["sub_option"])[0];
					if (!empty($sub_option)) {
						$store_id = \intval($sub_option);
					}
				}
			}
			if (strpos($params["configoptions_upgrade"]["data_disk_size"], ",") !== false) {
				$data_disk_size = explode(",", $params["configoptions_upgrade"]["data_disk_size"]);
				$data_disk_size = \intval($data_disk_size[0]);
			} else {
				$data_disk_size = \intval($params["configoptions_upgrade"]["data_disk_size"]);
			}
			foreach ($detail["disk"] as $v) {
				if (empty($store_id) && $v["type"] == "system") {
					$store_id = \intval($v["store_id"]);
				}
				if ($v["type"] == "data") {
					$disk_id = $v["id"];
					break;
				}
			}
			if (!empty($disk_id)) {
				if (!$needOff) {
					$needOff = $waitOff();
				}
				$this->curl("/disks/" . $disk_id, ["size" => $data_disk_size], 20, "PUT");
			} else {
				if (empty($store_id)) {
					$res = $this->curl("/clouds/" . $params["dcimid"] . "/stores", [], 20, "GET");
					$store_id = \intval($res["data"][0]["id"]);
				}
				if (!empty($store_id)) {
					$new_disk = $this->curl("/clouds/" . $params["dcimid"] . "/disks", ["size" => $data_disk_size, "store" => $store_id, "driver" => "virtio"], 20, "POST");
					$new_disk = $new_disk["data"]["diskid"];
				}
			}
		}
		if (isset($params["configoptions_upgrade"]["ipv6_num"]) && ($params["configoptions_upgrade"]["ipv6_num"] != $old["configoptions"]["ipv6_num"] || $params["configoptions_upgrade"]["ipv6_num"] != $detail["ipv6_num"])) {
			if (!$needOff) {
				$needOff = $waitOff();
			}
			$res_ipv6 = $this->curl("/clouds/" . $params["dcimid"] . "/ipv6", ["num" => $params["configoptions"]["ipv6_num"]], 20, "PUT");
		}
		if (isset($params["configoptions_upgrade"]["system_disk_io_limit"]) && $params["configoptions_upgrade"]["system_disk_io_limit"] != $old["configoptions"]["system_disk_io_limit"]) {
			if (!empty($params["configoptions"]["system_disk_io_limit"])) {
				if (!$needOff) {
					$needOff = $waitOff();
				}
				$arr = explode(",", $params["configoptions"]["system_disk_io_limit"]);
				$post_data_for_disk["read_bytes_sec"] = $arr[0] > 0 ? \intval($arr[0]) : 0;
				$post_data_for_disk["write_bytes_sec"] = $arr[1] > 0 ? \intval($arr[1]) : 0;
				$post_data_for_disk["read_iops_sec"] = $arr[2] > 0 ? \intval($arr[2]) : 0;
				$post_data_for_disk["write_iops_sec"] = $arr[3] > 0 ? \intval($arr[3]) : 0;
				foreach ($detail["disk"] as $v) {
					if ($v["type"] == "system") {
						$this->curl("/disks/" . $v["id"], $post_data_for_disk, 20, "PUT");
						break;
					}
				}
			}
		}
		if (isset($params["configoptions_upgrade"]["data_disk_io_limit"]) && $params["configoptions_upgrade"]["data_disk_io_limit"] != $old["configoptions"]["data_disk_io_limit"]) {
			if (!empty($params["configoptions"]["data_disk_io_limit"])) {
				if (!$needOff) {
					$needOff = $waitOff();
				}
				$arr = explode(",", $params["configoptions"]["data_disk_io_limit"]);
				$post_data_for_disk["read_bytes_sec"] = $arr[0] > 0 ? \intval($arr[0]) : 0;
				$post_data_for_disk["write_bytes_sec"] = $arr[1] > 0 ? \intval($arr[1]) : 0;
				$post_data_for_disk["read_iops_sec"] = $arr[2] > 0 ? \intval($arr[2]) : 0;
				$post_data_for_disk["write_iops_sec"] = $arr[3] > 0 ? \intval($arr[3]) : 0;
				foreach ($detail["disk"] as $v) {
					if ($v["type"] == "data") {
						$this->curl("/disks/" . $v["id"], $post_data_for_disk, 20, "PUT");
					}
				}
				if (!empty($new_disk)) {
					for ($i = 0; $i < 3; $i++) {
						$res = $this->curl("/disks/" . $new_disk, $post_data_for_disk, 20, "PUT");
						if ($res["status"] == "success") {
							break;
						}
						sleep(10);
					}
				}
			}
		}
		$detail = $this->curl("/clouds/" . $params["dcimid"], [], 20, "GET");
		if ($detail["status"] == "success") {
			$update["dedicatedip"] = $detail["data"]["mainip"] ?: "";
			if ($detail["data"]["client_show_ip_remark"] == 1) {
				$update["assignedips"] = [];
				foreach ($detail["data"]["ip"] as $v) {
					$update["assignedips"][] = !empty($v["remark"]) ? $v["ipaddress"] . "(" . str_replace(",", "，", $v["remark"]) . ")" : $v["ipaddress"];
				}
			} else {
				$update["assignedips"] = array_column($detail["data"]["ip"], "ipaddress") ?? [];
			}
			$update["assignedips"] = array_merge($update["assignedips"], array_column($detail["data"]["ipv6"], "ipv6") ?? []);
			$update["assignedips"] = implode(",", array_filter($update["assignedips"], function ($x) use($detail) {
				return $x != $detail["data"]["mainip"];
			}));
			if ($detail["data"]["traffic_quota"] == 0) {
				$update["bwlimit"] = 0;
			} else {
				$update["bwlimit"] = $detail["data"]["traffic_quota"] + $detail["data"]["tmp_traffic"];
			}
			\think\Db::name("host")->where("id", $id)->update($update);
			if (!empty($detail["data"]["panel_pass"])) {
				$this->savePanelPass($id, $params["productid"], $detail["data"]["panel_pass"]);
			}
		}
		if ($needOff) {
			$this->curl("/clouds/" . $params["dcimid"] . "/on", [], 10, "POST");
		}
		$uid = \think\Db::name("host")->where("id", $id)->value("uid");
		$description = "魔方云产品升降级成功 - Host ID:" . $id;
		if ($res_ipv6["status"] === "error") {
			$description .= ",IPv6升降级失败:" . $res_ipv6["msg"];
		}
		active_log_final($description, $uid, 2, $id);
		$result["status"] = "success";
		return $result;
	}
	public function chart($id, $host_id = 0)
	{
		if (empty($id)) {
			return [];
		}
		$mod = new \app\common\model\HostModel();
		$params = $mod->getProvisionParams($host_id);
		if ($params["configoptions"]["type"] == "hyperv") {
			return [];
		}
		return [["title" => "CPU使用量", "type" => "cpu"], ["title" => "硬盘IO", "type" => "disk"], ["title" => "内存用量", "type" => "memory"], ["title" => "网卡", "type" => "flow"]];
	}
	public function getChartData($id, $get)
	{
		$mod = new \app\common\model\HostModel();
		$params = $mod->getProvisionParams($id);
		if (empty($params["dcimid"])) {
			$result["status"] = "error";
			$result["msg"] = "数据获取失败";
			return $result;
		}
		$this->setUrl($params["serverid"]);
		$cloud = $this->curl("/clouds/" . $params["dcimid"], [], 15, "GET");
		if ($cloud["status"] == "error") {
			$result["status"] = "error";
			if ($this->is_admin) {
				$result["msg"] = $cloud["msg"] ?: "数据获取失败";
			} else {
				$result["msg"] = "数据获取失败";
			}
			return $result;
		}
		if ($get["type"] == "cpu") {
			$query = ["node_id" => $cloud["data"]["node_id"], "kvm" => $cloud["data"]["kvmid"], "type" => "kvm_info", "st" => $get["start"], "et" => $get["end"]];
			$res = $this->curl("/statistics", $query, 30, "GET");
			if ($res["status"] == "success") {
				$result["status"] = "success";
				$result["data"] = [];
				$result["data"]["unit"] = "%";
				$result["data"]["chart_type"] = "area";
				$result["data"]["label"] = ["CPU使用率(%)"];
				$result["data"]["list"] = [];
				foreach ($res["data"] as $v) {
					$result["data"]["list"][0][] = ["time" => date("Y-m-d H:i:s", strtotime($v[0])), "value" => round($v[1], 2)];
				}
			} else {
				$result["status"] = "error";
				$result["msg"] = "数据获取失败";
			}
		} elseif ($get["type"] == "disk") {
			$query = ["node_id" => $cloud["data"]["node_id"], "kvm" => $cloud["data"]["kvmid"], "dev_name" => $get["select"] ?: "vda", "type" => "disk_io", "st" => $get["start"], "et" => $get["end"]];
			$res = $this->curl("/statistics", $query, 30, "GET");
			if ($res["status"] == "success") {
				$result["status"] = "success";
				$result["data"] = [];
				$max = 0;
				foreach ($res["data"] as $v) {
					if ($max < $v[1]) {
						$max = $v[1];
					}
					if ($max < $v[2]) {
						$max = $v[2];
					}
				}
				$unit = ["B/s", "KB/s", "MB/s", "GB/s", "TB/s"];
				$i = 0;
				for ($i = 0; $i < count($unit); $i++) {
					if ($max < 1024) {
						break;
					}
					$max /= 1024;
				}
				$result["data"]["unit"] = $unit[$i];
				$result["data"]["chart_type"] = "line";
				$result["data"]["label"] = ["读取速度({$unit[$i]})", "写入速度({$unit[$i]})"];
				$result["data"]["list"] = [];
				$pow = pow(1024, $i);
				foreach ($res["data"] as $v) {
					$date = date("Y-m-d H:i:s", strtotime($v[0]));
					$result["data"]["list"][0][] = ["time" => $date, "value" => round($v[1] / $pow, 2)];
					$result["data"]["list"][1][] = ["time" => $date, "value" => round($v[2] / $pow, 2)];
				}
			} else {
				$result["status"] = "error";
				$result["msg"] = "数据获取失败";
			}
		} elseif ($get["type"] == "memory") {
			$query = ["node_id" => $cloud["data"]["node_id"], "kvm" => $cloud["data"]["kvmid"], "type" => "kvm_info", "st" => $get["start"], "et" => $get["end"]];
			$res = $this->curl("/statistics", $query, 30, "GET");
			if ($res["status"] == "success") {
				$result["status"] = "success";
				$result["data"] = [];
				$max = 0;
				foreach ($res["data"] as $v) {
					if ($max < $v[2]) {
						$max = $v[2];
					}
				}
				$unit = ["B", "KB", "MB", "GB", "TB"];
				$i = 0;
				for ($i = 0; $i < count($unit); $i++) {
					if ($max < 1024) {
						break;
					}
					$max /= 1024;
				}
				$result["data"]["unit"] = $unit[$i];
				$result["data"]["chart_type"] = "bar";
				$result["data"]["label"] = ["总量({$unit[$i]})", "已用({$unit[$i]})"];
				$result["data"]["list"] = [];
				$pow = pow(1024, $i);
				foreach ($res["data"] as $v) {
					$date = date("Y-m-d H:i:s", strtotime($v[0]));
					$result["data"]["list"][0][] = ["time" => $date, "value" => round($v[2] / $pow, 2)];
					$result["data"]["list"][1][] = ["time" => $date, "value" => round($v[3] / $pow, 2)];
				}
			} else {
				$result["status"] = "error";
				$result["msg"] = "数据获取失败";
			}
		} elseif ($get["type"] == "flow") {
			$query = ["node_id" => $cloud["data"]["node_id"], "kvm" => $cloud["data"]["kvmid"], "type" => "net_adapter", "kvm_ifname" => $cloud["data"]["kvmid"] . ".0", "st" => $get["start"], "et" => $get["end"]];
			$res = $this->curl("/statistics", $query, 30, "GET");
			if ($res["status"] == "success") {
				$result["status"] = "success";
				$result["data"] = [];
				$max = 0;
				foreach ($res["data"] as $v) {
					if ($max < $v[1]) {
						$max = $v[1];
					}
					if ($max < $v[2]) {
						$max = $v[2];
					}
				}
				$unit = ["bps", "Kbps", "Mbps", "Gbps", "Tbps"];
				$i = 0;
				for ($i = 0; $i < count($unit); $i++) {
					if ($max < 1024) {
						break;
					}
					$max /= 1024;
				}
				$result["data"]["unit"] = $unit[$i];
				$result["data"]["chart_type"] = "line";
				$result["data"]["label"] = ["进({$unit[$i]})", "出({$unit[$i]})"];
				$result["data"]["list"] = [];
				$pow = pow(1024, $i);
				foreach ($res["data"] as $v) {
					$date = date("Y-m-d H:i:s", strtotime($v[0]));
					$result["data"]["list"][0][] = ["time" => $date, "value" => round($v[1] / $pow, 2)];
					$result["data"]["list"][1][] = ["time" => $date, "value" => round($v[2] / $pow, 2)];
				}
			} else {
				$result["status"] = "error";
				$result["msg"] = "数据获取失败";
			}
		}
		return $result;
	}
	public function CrackPassword($id, $new_pass)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if (empty($product["dcimid"])) {
			$result["status"] = 200;
			$result["msg"] = "重置成功";
			return $result;
		}
		$this->setUrl($product["serverid"]);
		if ($this->error) {
			$result["status"] = 400;
			$result["msg"] = "操作失败";
			return $result;
		}
		$res = $this->curl("/clouds/" . $product["dcimid"] . "/password", ["password" => $new_pass], 30, "PUT");
		if ($res["status"] == "success") {
			$result["status"] = 200;
			$result["msg"] = "发起重置密码成功";
		} else {
			$result["status"] = 400;
			$result["msg"] = $res["msg"];
			if ($this->is_admin) {
				$result["msg"] = $this->log_prefix_error . $result["msg"];
			}
		}
		return $result;
	}
	public function moduleAdminButton($id)
	{
		$button = [["type" => "default", "func" => "create", "name" => "开通"], ["type" => "default", "func" => "suspend", "name" => "暂停"], ["type" => "default", "func" => "unsuspend", "name" => "解除暂停"], ["type" => "default", "func" => "terminate", "name" => "删除"], ["type" => "default", "func" => "on", "name" => "开机"], ["type" => "default", "func" => "off", "name" => "关机"], ["type" => "default", "func" => "reboot", "name" => "重启"], ["type" => "default", "func" => "hard_off", "name" => "硬关机"], ["type" => "default", "func" => "hard_reboot", "name" => "硬重启"], ["type" => "default", "func" => "reinstall", "name" => "重装系统"], ["type" => "default", "func" => "crack_pass", "name" => "重置密码"], ["type" => "default", "func" => "rescue_system", "name" => "救援系统"], ["type" => "custom", "func" => "exit_rescue", "name" => "退出救援系统"], ["type" => "default", "func" => "vnc", "name" => "VNC"], ["type" => "default", "func" => "sync", "name" => "拉取信息"]];
		if (!empty($id)) {
			array_shift($button);
		} else {
			$button = [array_shift($button)];
		}
		return $button;
	}
	public function managePanel($id)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		$this->setUrl($product["serverid"]);
		if ($this->error) {
			$result["status"] = 400;
			$result["msg"] = "操作失败";
			return $result;
		}
		if (empty($product["dcimid"])) {
			$result["status"] = 400;
			$result["msg"] = "云主机ID错误";
			return $result;
		}
		$result["status"] = 200;
		$result["data"]["url"] = $this->url . "/#/cloudsHome?id=" . $product["dcimid"];
		return $result;
	}
	public function createAccount($hostid)
	{
		$mod = new \app\common\model\HostModel();
		$params = $mod->getProvisionParams($hostid);
		if ($params["type"] == "dcimcloud") {
			if ($params["dcimid"] > 0) {
				$result["status"] = 400;
				$result["msg"] = "该产品已存在，不能重复开通";
				return $result;
			}
			$this->setUrl($params["serverid"]);
			$username = $params["user_info"]["email"] ?: $params["user_info"]["phonenumber"];
			$username = $this->user_prefix . $username;
			$user_data = ["username" => $username, "email" => $params["user_info"]["email"] ?: "", "status" => 1, "real_name" => $params["user_info"]["username"] ?: "", "password" => randStr(8)];
			if ($this->account_type == "agent") {
				$user_data["rid"] = \intval($params["configoptions"]["resource_package"]);
				if (empty($user_data["rid"])) {
					$result["status"] = 400;
					$result["msg"] = "资源包ID错误,创建用户需要配置资源包";
					if ($this->is_admin) {
						$result["msg"] = $this->log_prefix_error . $result["msg"];
					}
					return $result;
				}
			}
			$this->curl("/user", $user_data);
			$res = $this->curl("/user/check", ["username" => $username]);
			if (empty($res["data"]["id"])) {
				$result["status"] = 400;
				$result["msg"] = $res["msg"] ?: "魔方云用户创建失败";
				if ($this->is_admin) {
					$result["msg"] = $this->log_prefix_error . $result["msg"];
				}
				return $result;
			}
			$post_data["area"] = $params["configoptions"]["area"];
			$post_data["node"] = $params["configoptions"]["node"];
			$post_data["os"] = $params["configoptions"]["os"];
			$post_data["cpu"] = $params["configoptions"]["cpu"];
			$post_data["memory"] = \intval($params["configoptions"]["memory"]);
			$post_data["node_group"] = $params["configoptions"]["node_group"];
			$post_data["ip_group"] = $params["configoptions"]["ip_group"];
			$post_data["node_priority"] = $params["configoptions"]["node_priority"];
			$post_data["nat_acl_limit"] = isset($params["configoptions"]["nat_acl_limit"]) ? $params["configoptions"]["nat_acl_limit"] : -1;
			$post_data["nat_web_limit"] = isset($params["configoptions"]["nat_web_limit"]) ? $params["configoptions"]["nat_web_limit"] : -1;
			if (isset($params["configoptions"]["advanced_cpu"])) {
				$post_data["advanced_cpu"] = intval($params["configoptions"]["advanced_cpu"]);
			}
			if (isset($params["configoptions"]["advanced_bw"])) {
				$post_data["advanced_bw"] = intval($params["configoptions"]["advanced_bw"]);
			}
			if ($params["configoptions"]["system_disk_size"] > 0) {
				$post_data["system_disk_size"] = $params["configoptions"]["system_disk_size"];
			}
			if ($params["configoptions"]["data_disk_size"] > 0) {
				$post_data["other_data_disk"] = [["size" => $params["configoptions"]["data_disk_size"]]];
			}
			$post_data["network_type"] = $params["configoptions"]["network_type"];
			if (!empty($params["configoptions"]["system_disk_io_limit"])) {
				$arr = explode(",", $params["configoptions"]["system_disk_io_limit"]);
				$post_data["system_read_bytes_sec"] = $arr[0] > 0 ? \intval($arr[0]) : 0;
				$post_data["system_write_bytes_sec"] = $arr[1] > 0 ? \intval($arr[1]) : 0;
				$post_data["system_read_iops_sec"] = $arr[2] > 0 ? \intval($arr[2]) : 0;
				$post_data["system_write_iops_sec"] = $arr[3] > 0 ? \intval($arr[3]) : 0;
			}
			if (!empty($params["configoptions"]["data_disk_io_limit"])) {
				$arr = explode(",", $params["configoptions"]["data_disk_io_limit"]);
				$post_data["data_read_bytes_sec"] = $arr[0] > 0 ? \intval($arr[0]) : 0;
				$post_data["data_write_bytes_sec"] = $arr[1] > 0 ? \intval($arr[1]) : 0;
				$post_data["data_read_iops_sec"] = $arr[2] > 0 ? \intval($arr[2]) : 0;
				$post_data["data_write_iops_sec"] = $arr[3] > 0 ? \intval($arr[3]) : 0;
			}
			if ($post_data["network_type"] == "vpc") {
				$vpc_data = ["user" => $res["data"]["id"], "sort" => "asc", "per_page" => 1];
				if (!empty($post_data["node"])) {
					$vpc_data["node"] = $post_data["node"];
				} elseif (!empty($post_data["node_group"])) {
					$vpc_data["node_group"] = $post_data["node_group"];
				} else {
					$vpc_data["area"] = $post_data["area"];
				}
				$vpc_network = $this->curl("/vpc_networks", $vpc_data, 30, "GET");
				if (!empty($vpc_network["data"]["data"][0]["id"])) {
					$post_data["vpc"] = $vpc_network["data"]["data"][0]["id"];
				} else {
					$post_data["vpc_name"] = "VPC-" . randStr(8);
				}
			}
			$flow_way = isset($params["configoptions"]["flow_way"]) ? $params["configoptions"]["flow_way"] : "all";
			$traffic_type_arr = ["in" => 1, "out" => 2, "all" => 3];
			$post_data["traffic_type"] = $traffic_type_arr[$flow_way] ?: 3;
			$post_data["in_bw"] = \intval($params["configoptions"]["bw"]);
			$post_data["out_bw"] = \intval($params["configoptions"]["bw"]);
			if (isset($params["configoptions"]["in_bw"])) {
				$post_data["in_bw"] = \intval($params["configoptions"]["in_bw"]);
			}
			$post_data["ip_num"] = $params["configoptions"]["ip_num"];
			$post_data["traffic_quota"] = $params["configoptions"]["flow_limit"];
			$post_data["backup_num"] = isset($params["configoptions"]["backup_num"]) ? $params["configoptions"]["backup_num"] : -1;
			$post_data["snap_num"] = isset($params["configoptions"]["snap_num"]) ? $params["configoptions"]["snap_num"] : -1;
			$post_data["client"] = $res["data"]["id"];
			$post_data["hostname"] = $params["domain"];
			$post_data["rootpass"] = $params["password"];
			if (isset($params["configoptions"]["link_clone"])) {
				$post_data["link_clone"] = \intval($params["configoptions"]["link_clone"]);
			}
			if ($params["configoptions"]["traffic_bill_type"] == "last_30days") {
				$post_data["reset_flow_day"] = date("j");
			}
			$system_disk_size_config_options = \think\Db::name("host_config_options")->field("a.id,a.qty,b.option_name,b.option_type,c.option_name sub_option,b.upgrade")->alias("a")->leftJoin("product_config_options b", "a.configid=b.id")->leftJoin("product_config_options_sub c", "a.configid=c.config_id and a.optionid=c.id")->where("a.relid", $hostid)->whereLike("b.option_name", "system_disk_size|%")->find();
			if ($system_disk_size_config_options["option_type"] == 1 || $system_disk_size_config_options["option_type"] == 13) {
				$sub_option = explode("|", $system_disk_size_config_options["sub_option"])[0];
				if (strpos($sub_option, ",") !== false) {
					if (strpos($sub_option, "lin:") !== false && strpos($sub_option, "win:") !== false) {
						$sub_option = explode(",", $sub_option);
						foreach ($sub_option as $v) {
							if (strpos($v, "lin:") !== false) {
								$lin_size = \intval(str_replace("lin:", "", $v));
							} elseif (strpos($v, "win:") !== false) {
								$win_size = \intval(str_replace("win:", "", $v));
							} else {
								$post_data["store"] = \intval($v);
							}
						}
						if (stripos($params["os"], "win") !== false) {
							$post_data["system_disk_size"] = $win_size;
						} else {
							$post_data["system_disk_size"] = $lin_size;
						}
					} else {
						$sub_option = explode(",", $sub_option);
						$post_data["system_disk_size"] = \intval($sub_option[0]);
						$post_data["store"] = \intval($sub_option[1]);
					}
				}
			} else {
				if ($system_disk_size_config_options["option_type"] == 14) {
					$sub_option = explode("|", $system_disk_size_config_options["sub_option"])[0];
					$post_data["store"] = \intval($sub_option);
				}
			}
			$data_disk_size_config_options = \think\Db::name("host_config_options")->field("a.id,a.qty,b.option_name,b.option_type,c.option_name sub_option,b.upgrade")->alias("a")->leftJoin("product_config_options b", "a.configid=b.id")->leftJoin("product_config_options_sub c", "a.configid=c.config_id and a.optionid=c.id")->where("a.relid", $hostid)->whereLike("b.option_name", "data_disk_size|%")->find();
			if ($data_disk_size_config_options["option_type"] == 1 || $data_disk_size_config_options["option_type"] == 13) {
				$sub_option = explode("|", $data_disk_size_config_options["sub_option"])[0];
				if (strpos($sub_option, ",") !== false) {
					$sub_option = explode(",", $sub_option);
					$post_data["other_data_disk"] = [["size" => \intval($sub_option[0]), "store" => \intval($sub_option[1])]];
				} else {
					$post_data["other_data_disk"] = [["size" => \intval($sub_option)]];
				}
			} else {
				if ($data_disk_size_config_options["option_type"] == 14) {
					if (strpos($data_disk_size_config_options["sub_option"], "|") !== false) {
						$sub_option = explode("|", $data_disk_size_config_options["sub_option"])[0];
						if (!empty($sub_option) && !empty($params["configoptions"]["data_disk_size"])) {
							$post_data["other_data_disk"] = [["size" => \intval($params["configoptions"]["data_disk_size"]), "store" => \intval($sub_option)]];
						}
					} else {
						if (!empty($sub_option) && !empty($params["configoptions"]["data_disk_size"])) {
							$post_data["other_data_disk"] = [["size" => \intval($params["configoptions"]["data_disk_size"])]];
						}
					}
				}
			}
			if (empty($post_data["other_data_disk"][0]["store"])) {
				unset($post_data["other_data_disk"][0]["store"]);
			}
			$memory_config_options = \think\Db::name("host_config_options")->field("a.id,a.qty,b.option_name,b.option_type,c.option_name sub_option,b.upgrade")->alias("a")->leftJoin("product_config_options b", "a.configid=b.id")->leftJoin("product_config_options_sub c", "a.configid=c.config_id and a.optionid=c.id")->where("a.relid", $hostid)->whereLike("b.option_name", "memory|%")->find();
			if ($memory_config_options["option_type"] == 1 || $memory_config_options["option_type"] == 8) {
				$sub_option = explode("|", $memory_config_options["sub_option"])[0];
				if (strpos($sub_option, ",") !== false) {
					$sub_option = explode(",", $sub_option);
					$unit = strtolower($sub_option[1]);
					$post_data["memory"] = \intval($sub_option[0]);
					if ($unit == "g" || $unit == "gb") {
						$post_data["memory"] *= 1024;
					}
				}
			} else {
				if ($memory_config_options["option_type"] == 9 || $memory_config_options["option_type"] == 17) {
					$sub_option = explode("|", $memory_config_options["sub_option"])[0];
					$post_data["memory"] = $memory_config_options["qty"];
					if (strpos($sub_option, ",") !== false) {
						$sub_option = explode(",", $sub_option);
						$unit = strtolower($sub_option[1]);
						if ($unit == "g" || $unit == "gb") {
							$post_data["memory"] *= 1024;
						}
					}
				}
			}
			if (isset($params["configoptions"]["IP_MACBond"])) {
				$post_data["bind_mac"] = \intval($params["configoptions"]["IP_MACBond"]);
			}
			if (is_numeric($params["configoptions"]["cpu_limit"]) && $params["configoptions"]["cpu_limit"] >= 0 && $params["configoptions"]["cpu_limit"] <= 100) {
				$post_data["cpu_limit"] = \intval($params["configoptions"]["cpu_limit"]);
			}
			if (is_numeric($params["customfields"]["port"]) || is_numeric($params["customfields"]["端口"]) || $params["customfields"]["port"] === "auto" || $params["customfields"]["端口"] === "auto") {
				$post_data["port"] = $params["customfields"]["port"] ?: $params["customfields"]["端口"];
			}
			if (is_numeric($params["configoptions"]["port"]) || $params["configoptions"]["port"] === "auto") {
				$post_data["port"] = $params["configoptions"]["port"];
			}
			if (isset($params["configoptions"]["cpu_model"])) {
				$post_data["cpu_model"] = \intval($params["configoptions"]["cpu_model"]);
			}
			if ($this->account_type == "agent") {
				if (!empty($params["configoptions"]["resource_package"])) {
					$post_data["rid"] = \intval($params["configoptions"]["resource_package"]);
				}
			}
			$post_data["ipv6_num"] = isset($params["configoptions"]["ipv6_num"]) ? \intval($params["configoptions"]["ipv6_num"]) : 0;
			$post_data["type"] = $params["configoptions"]["type"];
			if (isset($params["configoptions"]["niccard"]) && in_array($params["configoptions"]["niccard"], [1, 2, 3])) {
				$post_data["niccard"] = $params["configoptions"]["niccard"];
			}
			$res = $this->curl("/clouds", $post_data);
			if ($res["status"] == "success") {
				$cloudid = $res["data"]["id"];
				$detail = $this->curl("/clouds/" . $cloudid, [], 20, "GET");
				$update["dcimid"] = $cloudid;
				$update["dedicatedip"] = $detail["data"]["mainip"] ?: "";
				if ($detail["data"]["client_show_ip_remark"] == 1) {
					$update["assignedips"] = [];
					foreach ($detail["data"]["ip"] as $v) {
						$update["assignedips"][] = !empty($v["remark"]) ? $v["ipaddress"] . "(" . str_replace(",", "，", $v["remark"]) . ")" : $v["ipaddress"];
					}
				} else {
					$update["assignedips"] = array_column($detail["data"]["ip"], "ipaddress") ?? [];
				}
				$update["assignedips"] = array_merge($update["assignedips"], array_column($detail["data"]["ipv6"], "ipv6") ?? []);
				$update["assignedips"] = implode(",", array_filter($update["assignedips"], function ($x) use($detail) {
					return $x != $detail["data"]["mainip"];
				}));
				$update["domainstatus"] = "Active";
				$update["domain"] = $detail["data"]["hostname"];
				$update["username"] = $detail["data"]["osuser"];
				$update["password"] = cmf_encrypt($detail["data"]["rootpassword"]);
				$update["bwlimit"] = $detail["data"]["traffic_quota"];
				if (!empty($post_data["port"]) && !empty($detail["data"]["port"])) {
					$update["port"] = \intval($detail["data"]["port"]);
				}
				$reshost = \think\Db::name("host")->field("uid")->where("id", $hostid)->find();
				\think\Db::name("host")->where("id", $hostid)->update($update);
				$msg = "开通成功";
				if (!empty($detail["data"]["panel_pass"])) {
					$this->savePanelPass($hostid, $params["productid"], $detail["data"]["panel_pass"]);
				}
				$description = sprintf("开通成功 - Host ID:%d", $hostid);
				$result["status"] = 200;
				$result["msg"] = $msg;
			} elseif ($res["status"] == "error") {
				$result["status"] = 400;
				$result["msg"] = $res["msg"];
				$description = sprintf("开通失败,原因:%s - Host ID:%d", $res["msg"], $hostid);
			} else {
				$result["status"] = 400;
				$result["msg"] = "开通失败";
				$description = "开通失败,原因:未收到接口响应值";
			}
			if ($this->is_admin) {
				if ($result["status"] != 200) {
					$result["msg"] = $this->log_prefix_error . $result["msg"];
					$description = $this->log_prefix_error . $description;
				}
				active_log_final($description, $reshost["uid"], 2, $hostid);
			} else {
				active_log_final($description, $reshost["uid"], 2, $hostid);
			}
		} else {
			$result["status"] = 400;
			$result["msg"] = "不支持的类型";
		}
		return $result;
	}
	public function getTrafficUsage($id, $start, $end)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if (empty($product["dcimid"])) {
			$result["status"] = 400;
			$result["msg"] = "云主机ID错误";
			return $result;
		}
		$this->setUrl($product["serverid"]);
		if ($this->error) {
			$result["status"] = 400;
			$result["msg"] = "操作失败";
			return $result;
		}
		$get = ["type" => 2, "start_time" => strtotime($start . " 00:00:00") . "000", "end_time" => strtotime($end . " 23:59:59") . "000", "unit" => "GB"];
		$res = $this->curl("/clouds/" . $product["dcimid"] . "/flow_data", $get, 30, "GET");
		if ($res["status"] == "success") {
			$data = [];
			foreach ($res["data"]["data"] as $v) {
				$data[] = ["time" => $v["time"], "in" => $v["in"], "out" => $v["out"]];
			}
			$result["status"] = 200;
			$result["data"] = $data;
		} else {
			$result["status"] = 400;
			$result["msg"] = $res["msg"];
		}
		return $result;
	}
	public function savePanelPass($hostid, $pid, $password)
	{
		$is_dcim = \think\Db::name("products")->where("id", $pid)->where("type", "dcimcloud")->value("id");
		if (empty($is_dcim)) {
			return false;
		}
		$customid = \think\Db::name("customfields")->where("type", "product")->where("relid", $pid)->where("fieldname='面板管理密码' OR fieldname='panel_passwd'")->value("id");
		if (empty($customid) && !empty($password)) {
			$customfields = ["type" => "product", "relid" => $pid, "fieldname" => "面板管理密码", "fieldtype" => "password", "adminonly" => 1, "create_time" => time()];
			$customid = \think\Db::name("customfields")->insertGetId($customfields);
		}
		if (!empty($customid)) {
			$exist = \think\Db::name("customfieldsvalues")->where("fieldid", $customid)->where("relid", $hostid)->find();
			if (empty($exist)) {
				$data = ["fieldid" => $customid, "relid" => $hostid, "value" => $password, "create_time" => time()];
				\think\Db::name("customfieldsvalues")->insert($data);
			} else {
				\think\Db::name("customfieldsvalues")->where("id", $exist["id"])->update(["value" => $password]);
			}
		}
		return true;
	}
	public function resetFlow($hostid, $dcimid, $nextduedate = 0, $domainstatus)
	{
		$res = $this->curl("/clouds/" . $dcimid . "/reset_traffic");
		if ($res["status"] == "success") {
			$description = sprintf("流量清零成功 - Host ID:%d", $hostid);
			$update = [];
			if (isset($res["data"]["traffic_quota"])) {
				$update["bwlimit"] = \intval($res["data"]["traffic_quota"]);
			}
			$update["bwusage"] = 0;
			$update["lastupdate"] = time();
			\think\Db::name("host")->where("id", $hostid)->update($update);
			$suspendreason = \think\Db::name("host")->where("id", $hostid)->value("suspendreason");
			$suspendreason_type = explode("-", $suspendreason)[0];
			if ($domainstatus == "Suspended" && ($nextduedate == 0 || date("Ymd") < date("Ymd", $nextduedate)) && $suspendreason_type == "flow") {
				$host = new Host();
				$host->is_admin = $this->is_admin;
				$result = $host->unsuspend($hostid);
				$logic_run_map = new RunMap();
				$model_host = new \app\common\model\HostModel();
				$data_i = [];
				$data_i["host_id"] = $hostid;
				$data_i["active_type_param"] = [$hostid, 0, "", 0];
				$is_zjmf = $model_host->isZjmfApi($data_i["host_id"]);
				if ($result["status"] == 200) {
					$data_i["description"] = " 同步流量后 - 解除暂停 Host ID:{$data_i["host_id"]}的产品成功";
					if ($is_zjmf) {
						$logic_run_map->saveMap($data_i, 1, 400, 3);
					}
					if (!$is_zjmf) {
						$logic_run_map->saveMap($data_i, 1, 100, 3);
					}
				} else {
					$data_i["description"] = " 同步流量后 - 解除暂停 Host ID:{$data_i["host_id"]}的产品失败：{$result["msg"]}";
					if ($is_zjmf) {
						$logic_run_map->saveMap($data_i, 0, 400, 3);
					}
					if (!$is_zjmf) {
						$logic_run_map->saveMap($data_i, 0, 100, 3);
					}
				}
			}
			$res_data["status"] = 200;
			$res_data["msg"] = "流量重置成功";
		} else {
			if ($res["http_code"] == 404) {
				$dcimcloud->curl("/clouds/" . $dcimid, ["tmp_traffic" => 0], 2, "PUT");
				$description = sprintf("流量清零成功 - Host ID:%d", $hostid);
				$res_data["status"] = 200;
				$res_data["msg"] = "流量重置成功";
			} else {
				$description = sprintf("流量清零失败,原因:%s - Host ID:%d", $res["msg"], $hostid);
				$res_data["status"] = 400;
				$res_data["msg"] = $res["msg"];
			}
		}
		$reshost = \think\Db::name("host")->field("uid")->where("id", $hostid)->find();
		active_log_final($description, $reshost["uid"], 2, $hostid);
		return $res_data;
	}
	public function updateResetFlowDay($hostid)
	{
		$traffic_bill_type = (new \app\common\model\HostModel())->getConfigOption($hostid, "traffic_bill_type");
		if (empty($traffic_bill_type)) {
			return false;
		}
		$dcimid = $this->setUrlByHost($hostid);
		if (empty($dcimid)) {
			return false;
		}
		$traffic_bill_type = $traffic_bill_type["sub_option_arr"][0];
		$res = $this->curl("/clouds/" . $dcimid, [], 20, "GET");
		$detail = $res["data"];
		if ($res["status"] == "success" && isset($detail["reset_flow_day"])) {
			if ($traffic_bill_type == "last_30days") {
				$reset_flow_day = date("j", strtotime($detail["create_time"]));
			} else {
				$reset_flow_day = 1;
			}
			if ($reset_flow_day != $detail["reset_flow_day"]) {
				$this->curl("/clouds/" . $dcimid, ["reset_flow_day" => $reset_flow_day], 5, "PUT");
			}
		}
		return true;
	}
	public function rescue($id, $system, $temp_pass)
	{
		if (!in_array($system, [1, 2])) {
			$result["status"] = 400;
			$result["msg"] = "救援系统类型错误";
			return $result;
		}
		if (empty($temp_pass)) {
			$result["status"] = 400;
			$result["msg"] = "请输入临时密码";
			return $result;
		}
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if (empty($product["dcimid"])) {
			$result["status"] = 200;
			$result["msg"] = "救援成功";
			return $result;
		}
		$this->setUrl($product["serverid"]);
		if ($this->error) {
			$result["status"] = 400;
			$result["msg"] = "操作失败";
			return $result;
		}
		$res = $this->curl("/clouds/" . $product["dcimid"] . "/rescue", ["type" => $system, "temp_pass" => $temp_pass], 30, "POST");
		if ($res["status"] == "success") {
			$result["status"] = 200;
			$result["msg"] = "发起救援系统成功";
		} else {
			$result["status"] = 400;
			if ($this->is_admin) {
				$result["msg"] = $this->log_prefix_error . $res["msg"];
			} else {
				$result["msg"] = "发起救援系统失败";
			}
		}
		return $result;
	}
	public function curl($action = "", $data = [], $timeout = 30, $request = "POST", $relogin = true)
	{
		$access_token = $this->login();
		if (!$access_token) {
			return ["status" => "error", "msg" => $this->link_error_msg];
		}
		$header = ["access-token: " . $access_token];
		$res = $this->basecurl($action, $data, $timeout, $request, $header);
		if ($relogin && $res["status"] == "error" && $res["http_code"] == 401) {
			$access_token = $this->login(true);
			if (!$access_token) {
				return ["status" => "error", "msg" => $this->link_error_msg];
			}
			$header = ["access-token: " . $access_token];
			$res = $this->basecurl($action, $data, $timeout, $request, $header);
		}
		return $res;
	}
	public function login($force = false)
	{
		$key = "dcim_cloud_token_" . $this->serverid;
		$access_token = \think\facade\Cache::get($key);
		if (empty($access_token) || $force) {
			$post_data["username"] = $this->username;
			$post_data["password"] = $this->password;
			if ($this->account_type == "agent") {
				$res = $this->basecurl("/token", $post_data);
			} else {
				$res = $this->basecurl("/login?a=a", $post_data);
			}
			if ($res["status"] == "success") {
				if ($this->account_type == "agent") {
					$access_token = $res["data"]["token"];
				} else {
					$access_token = $res["origin"];
				}
				\think\facade\Cache::set($key, $access_token, 21600);
			} else {
				\think\facade\Cache::delete($key);
				if ($res["http_code"] == 404) {
					$this->link_error_msg = "无法连接魔方云管理系统，请检查魔方云访问地址是否填写正确";
				} else {
					$this->link_error_msg = $res["msg"];
				}
				return false;
			}
		}
		return trim($access_token, "\"");
	}
	public function basecurl($action = "", $data = [], $timeout = 30, $request = "POST", $header = [])
	{
		if ($this->account_type == "agent") {
			$url = $this->url . "/index.php?path=" . ltrim($action, "/");
		} else {
			$url = $this->url . "/v1" . $action;
		}
		$curl = curl_init();
		if ($request == "GET") {
			$s = "";
			if (!empty($data)) {
				foreach ($data as $k => $v) {
					$s .= $k . "=" . urlencode($v) . "&";
				}
			}
			if ($s) {
				$s = "?" . trim($s, "&");
			}
			curl_setopt($curl, CURLOPT_URL, $url . $s);
		} else {
			curl_setopt($curl, CURLOPT_URL, $url);
		}
		curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($curl, CURLOPT_USERAGENT, "WHMCS");
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		if (strtoupper($request) == "GET") {
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_HTTPGET, 1);
		}
		if (strtoupper($request) == "POST") {
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_POST, 1);
			if (is_array($data)) {
				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
			} else {
				curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
			}
		}
		if (strtoupper($request) == "PUT" || strtoupper($request) == "DELETE") {
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($request));
			if (is_array($data)) {
				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
			} else {
				curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
			}
		}
		if (!empty($header)) {
			curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		}
		$content = curl_exec($curl);
		$error = curl_error($curl);
		if (!empty($error)) {
			$this->curl_error = $error;
			return ["status" => "error", "msg" => "CURL ERROR:" . $error];
		}
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		$res = json_decode($content, true);
		if (isset($res["error"])) {
			if ($http_code == 401) {
				$key = "dcim_cloud_token_" . $this->serverid;
				\think\facade\Cache::delete($key);
			}
			return ["status" => "error", "msg" => $res["error"], "http_code" => $http_code];
		} else {
			if ($http_code >= 200 && $http_code < 300) {
				return ["status" => "success", "data" => $res, "origin" => $content];
			} else {
				return ["status" => "error", "msg" => "请求失败,HTTP状态码:" . $http_code, "http_code" => $http_code];
			}
		}
	}
}