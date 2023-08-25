<?php

namespace app\common\logic;

class UpperReaches
{
	public $is_admin = false;
	public function getOs()
	{
		$res = $this->curl("/image?per_page=9999&sort=asc", [], 10, "GET");
		$result = [];
		if ($res["status"] == "success") {
			foreach ($res["data"]["data"] as $v) {
				if ($v["status"] == 1) {
					$result[] = ["name" => $v["id"] . "|" . $v["group_name"] . "^" . $v["name"]];
				}
			}
		}
		return $result;
	}
	public function on($id)
	{
		$re = \think\Db::name("upper_reaches_res")->where("hid", $id)->find();
		if (empty($re)) {
			$result["status"] = 400;
			$result["msg"] = "没有此资源配置";
			return $result;
		}
		if ($re["control_mode"] == "ipmi") {
			$res = $this->ipmiOn($re);
		} elseif ($re["control_mode"] == "dcim_client") {
			$res = $this->dcimClientOn($re);
		} else {
			$result["status"] = 400;
			$result["msg"] = "资源配置控制方式有误";
			return $result;
		}
		if ($res["status"] == 200) {
			$result["status"] = 200;
			$result["msg"] = "开机成功";
			$description = sprintf("开机成功 - Host ID:%d", $id);
		} else {
			$result["status"] = 400;
			$result["msg"] = "开机失败";
			if ($this->is_admin) {
				$description = sprintf("开机失败,原因:%s - Host ID:%d", $res["msg"], $id);
			} else {
				$description = sprintf("开机失败 - Host ID:%d", $id);
			}
		}
		return $result;
	}
	public function off($id)
	{
		$re = \think\Db::name("upper_reaches_res")->where("hid", $id)->find();
		if (empty($re)) {
			$result["status"] = 400;
			$result["msg"] = "没有此资源配置";
			return $result;
		}
		if ($re["control_mode"] == "ipmi") {
			$res = $this->ipmiOff($re);
		} elseif ($re["control_mode"] == "dcim_client") {
			$res = $this->dcimClientOff($re);
		} else {
			$result["status"] = 400;
			$result["msg"] = "资源配置控制方式有误";
			return $result;
		}
		if ($res["status"] == 200) {
			$result["status"] = 200;
			$result["msg"] = "关机成功";
			$description = sprintf("关机成功 - Host ID:%d", $id);
		} else {
			$result["status"] = 400;
			$result["msg"] = "关机失败";
			if ($this->is_admin) {
				$description = sprintf("关机失败,原因:%s - Host ID:%d", $res["msg"], $id);
			} else {
				$description = sprintf("关机失败 - Host ID:%d", $id);
			}
		}
		return $result;
	}
	public function reboot($id)
	{
		$re = \think\Db::name("upper_reaches_res")->where("hid", $id)->find();
		if (empty($re)) {
			$result["status"] = 400;
			$result["msg"] = "没有此资源配置";
			return $result;
		}
		if ($re["control_mode"] == "ipmi") {
			$res = $this->ipmiReboot($re);
		} elseif ($re["control_mode"] == "dcim_client") {
			$res = $this->dcimClientReboot($re);
		} else {
			$result["status"] = 400;
			$result["msg"] = "资源配置控制方式有误";
			return $result;
		}
		if ($res["status"] == 200) {
			$result["status"] = 200;
			$result["msg"] = "发起重启成功";
			$description = sprintf("发起重启成功 - Host ID:%d", $id);
		} else {
			$result["status"] = 400;
			$result["msg"] = "重启失败";
			if ($this->is_admin) {
				$description = sprintf("重启失败,原因:%s - Host ID:%d", $res["msg"], $id);
			} else {
				$description = sprintf("重启失败 - Host ID:%d", $id);
			}
		}
		return $result;
	}
	public function vnc($id)
	{
		$re = \think\Db::name("upper_reaches_res")->where("hid", $id)->find();
		if (empty($re)) {
			$result["status"] = 400;
			$result["msg"] = "没有此资源配置";
			return $result;
		}
		if ($re["control_mode"] == "ipmi") {
			$res = $this->ipmiVnc($re);
		} elseif ($re["control_mode"] == "dcim_client") {
			$res = $this->dcimClientVnc($re);
		} else {
			$result["status"] = 400;
			$result["msg"] = "资源配置控制方式有误";
			return $result;
		}
		if ($res["status"] == 200) {
			$result["status"] = 200;
			$result["msg"] = $res["msg"];
			if ($re["control_mode"] == "ipmi") {
				$result["url"] = $res["vnc_url"];
			} elseif ($re["control_mode"] == "dcim_client") {
				if ($this->is_admin) {
					$result["url"] = request()->domain() . "/" . config("database.admin_application") . "/dcim/novnc?url=" . $res["data"]["url"] . "&password=" . $res["data"]["password"];
				} else {
					$result["url"] = request()->domain() . "/dcim/novnc?url=" . $res["data"]["url"] . "&password=" . $res["data"]["password"];
				}
			}
		} else {
			$result["status"] = 400;
			$result["msg"] = $res["msg"];
		}
		return $result;
	}
	public function reinstall($id, $params = [])
	{
		$re = \think\Db::name("upper_reaches_res")->where("hid", $id)->find();
		if (empty($re)) {
			$result["status"] = 400;
			$result["msg"] = "没有此资源配置";
			return $result;
		}
		if ($re["control_mode"] == "dcim_client") {
			$res = $this->dcimClientReinstall($re, $params);
		} else {
			$result["status"] = 400;
			$result["msg"] = "资源配置控制方式有误";
			return $result;
		}
		if ($res["status"] == 200) {
			$result["status"] = 200;
			$result["msg"] = "重装发起成功";
		} else {
			$result["status"] = 400;
			$result["msg"] = "重装失败";
			if ($this->is_admin) {
				$result["msg"] .= "原因:" . $res["msg"];
				$description = sprintf("重装系统发起失败,原因:%s - Host ID:%d", $res["msg"], $id);
			} else {
				$description = sprintf("重装系统发起失败 - Host ID:%d", $id);
			}
		}
		return $result;
	}
	public function getStatus($id)
	{
		$re = \think\Db::name("upper_reaches_res")->where("hid", $id)->find();
		if (empty($re)) {
			$result["status"] = 400;
			$result["msg"] = "没有此资源配置";
			return $result;
		}
		if ($re["control_mode"] == "ipmi") {
			$res = $this->ipmiStatus($re);
		} elseif ($re["control_mode"] == "dcim_client") {
			$res = $this->dcimClientStatus($re);
		} else {
			$result["status"] = 400;
			$result["msg"] = "资源配置控制方式有误";
			return $result;
		}
		if ($res["status"] == 200) {
			$result["status"] = 200;
			if ($res["power_status"] == "on") {
				$result["data"]["status"] = "on";
				$result["data"]["des"] = "开机";
			} elseif ($res["power_status"] == "off") {
				$result["data"]["status"] = "off";
				$result["data"]["des"] = "关机";
			} else {
				$result["data"]["status"] = "unknown";
				$result["data"]["des"] = "未知";
			}
		} else {
			if ($this->is_admin) {
				$result["status"] = 400;
				$result["msg"] = $res["msg"];
			} else {
				$result["status"] = 200;
				$result["data"]["status"] = "unknown";
				$result["data"]["des"] = "未知";
			}
		}
		return $result;
	}
	public function moduleClientButton($id)
	{
		if (empty($id)) {
			return ["control" => [], "console" => []];
		}
		$upper_reaches_res = \think\Db::name("upper_reaches_res")->where("hid", $id)->find();
		if ($upper_reaches_res["control_mode"] == "dcim_client") {
			$button = ["control" => [["type" => "default", "func" => "on", "name" => "开机"], ["type" => "default", "func" => "off", "name" => "关机"], ["type" => "default", "func" => "reboot", "name" => "重启"], ["type" => "default", "func" => "reinstall", "name" => "重装系统"], ["type" => "default", "func" => "crack_pass", "name" => "重置密码"]], "console" => [["type" => "default", "func" => "vnc", "name" => "VNC"]]];
		} elseif ($upper_reaches_res["control_mode"] == "ipmi") {
			$button = ["control" => [["type" => "default", "func" => "on", "name" => "开机"], ["type" => "default", "func" => "off", "name" => "关机"], ["type" => "default", "func" => "reboot", "name" => "重启"]], "console" => [["type" => "default", "func" => "vnc", "name" => "VNC"]]];
		} else {
			$button = ["control" => [], "console" => []];
		}
		return $button;
	}
	public function CrackPassword($id, $params = [])
	{
		$re = \think\Db::name("upper_reaches_res")->where("hid", $id)->find();
		if (empty($re)) {
			$result["status"] = 400;
			$result["msg"] = "没有此资源配置";
			return $result;
		}
		if ($re["control_mode"] == "dcim_client") {
			$res = $this->dcimClientCrackPass($re, $params);
		} else {
			$result["status"] = 400;
			$result["msg"] = "资源配置控制方式有误";
			return $result;
		}
		if ($res["status"] == 200) {
			$result["status"] = 200;
			$result["msg"] = "发起重置密码成功";
		} else {
			$result["status"] = 400;
			$result["msg"] = $res["msg"];
		}
		return $result;
	}
	public function moduleAdminButton($id)
	{
		if (empty($id)) {
			return [];
		}
		$upper_reaches_res = \think\Db::name("upper_reaches_res")->where("hid", $id)->find();
		if ($upper_reaches_res["control_mode"] == "dcim_client") {
			$button = [["type" => "default", "func" => "on", "name" => "开机"], ["type" => "default", "func" => "off", "name" => "关机"], ["type" => "default", "func" => "reboot", "name" => "重启"], ["type" => "default", "func" => "reinstall", "name" => "重装系统"], ["type" => "default", "func" => "crack_pass", "name" => "重置密码"], ["type" => "default", "func" => "vnc", "name" => "VNC"]];
		} elseif ($upper_reaches_res["control_mode"] == "ipmi") {
			$button = [["type" => "default", "func" => "on", "name" => "开机"], ["type" => "default", "func" => "off", "name" => "关机"], ["type" => "default", "func" => "reboot", "name" => "重启"], ["type" => "default", "func" => "vnc", "name" => "VNC"]];
		} else {
			$button = [];
		}
		return $button;
	}
	public function modulePowerStatus($id)
	{
		if (empty($id)) {
			return false;
		}
		$upper_reaches_res = \think\Db::name("upper_reaches_res")->where("hid", $id)->find();
		if ($upper_reaches_res["control_mode"] == "dcim_client") {
			return true;
		} elseif ($upper_reaches_res["control_mode"] == "ipmi") {
			return true;
		} else {
			return false;
		}
	}
	public function ipmiStatus($re)
	{
		$data = ["version" => $re["ipmi_version"], "ipmi_ip" => $re["ipmi"], "ipmi_user" => $re["root"], "ipmi_pwd" => $re["pwd"]];
		$res = $this->ipmiRequest("ipmi/status", $data, 10);
		if ($res["code"] == 200) {
			$result = ["status" => 200, "msg" => "电源状态获取成功", "power_status" => $res["power"]];
			\think\Db::name("upper_reaches_res")->where("id", $re["id"])->update(["power_status" => $res["power"]]);
		} else {
			$result = ["status" => 400, "msg" => $res["msg"] ?? lang("ERROR MESSAGE"), "power_status" => "error"];
			\think\Db::name("upper_reaches_res")->where("id", $re["id"])->update(["power_status" => "error"]);
		}
		return $result;
	}
	public function ipmiOn($re)
	{
		$data = ["version" => $re["ipmi_version"], "ipmi_ip" => $re["ipmi"], "ipmi_user" => $re["root"], "ipmi_pwd" => $re["pwd"]];
		$res = $this->ipmiRequest("ipmi/on", $data);
		if ($res["code"] == 200) {
			$result = ["status" => 200, "msg" => "开机成功", "power_status" => $res["power"]];
			\think\Db::name("upper_reaches_res")->where("id", $re["id"])->update(["power_status" => $res["power"]]);
		} else {
			$result = ["status" => 400, "msg" => $res["msg"] ?? lang("ERROR MESSAGE")];
		}
		return $result;
	}
	public function ipmiOff($re)
	{
		$data = ["version" => $re["ipmi_version"], "ipmi_ip" => $re["ipmi"], "ipmi_user" => $re["root"], "ipmi_pwd" => $re["pwd"]];
		$res = $this->ipmiRequest("ipmi/off", $data);
		if ($res["code"] == 200) {
			$result = ["status" => 200, "msg" => "关机成功", "power_status" => $res["power"]];
			\think\Db::name("upper_reaches_res")->where("id", $re["id"])->update(["power_status" => $res["power"]]);
		} else {
			$result = ["status" => 400, "msg" => $res["msg"] ?? lang("ERROR MESSAGE")];
		}
		return $result;
	}
	public function ipmiReboot($re)
	{
		$data = ["version" => $re["ipmi_version"], "ipmi_ip" => $re["ipmi"], "ipmi_user" => $re["root"], "ipmi_pwd" => $re["pwd"]];
		$res = $this->ipmiRequest("ipmi/reboot", $data);
		if ($res["code"] == 200) {
			$result = ["status" => 200, "msg" => "重启成功", "power_status" => $res["power"]];
			\think\Db::name("upper_reaches_res")->where("id", $re["id"])->update(["power_status" => $res["power"]]);
		} else {
			$result = ["status" => 400, "msg" => $res["msg"] ?? lang("ERROR MESSAGE")];
		}
		return $result;
	}
	public function ipmiVnc($re)
	{
		$data = ["ipmi_ip" => $re["ipmi"], "ipmi_user" => $re["root"], "ipmi_pwd" => $re["pwd"]];
		$res = $this->ipmiRequest("vnc", $data);
		if ($res["code"] == 200) {
			$result = ["status" => 200, "msg" => "VNC开启成功", "vnc_url" => $res["vnc_url"]];
		} else {
			$result = ["status" => 400, "msg" => $res["msg"] ?? lang("ERROR MESSAGE")];
		}
		return $result;
	}
	private function ipmiRequest($app = "", $data = [], $timeout = 30, $method = "POST")
	{
		$result = commonCurl("http://public.api.idcsmart.com/v1/" . $app, $data, $timeout, $method);
		return $result;
	}
	public function dcimClientStatus($re)
	{
		$url = $re["dcim_client_url"] . "/index.php?a=api&id=" . $re["dcim_client_id"];
		$data = ["func" => "refreshPower", "api_user" => $re["root"], "api_pass" => $re["pwd"]];
		$res = $this->dcimClientRequest($url, $data, 10);
		if ($res["status"] == "success") {
			$result = ["status" => 200, "msg" => "电源状态获取成功", "power_status" => $res["msg"]];
			\think\Db::name("upper_reaches_res")->where("id", $re["id"])->update(["power_status" => $res["msg"]]);
		} else {
			$result = ["status" => 400, "msg" => $res["msg"] ?? lang("ERROR MESSAGE"), "power_status" => "error"];
			\think\Db::name("upper_reaches_res")->where("id", $re["id"])->update(["power_status" => "error"]);
		}
		return $result;
	}
	public function dcimClientOn($re)
	{
		$url = $re["dcim_client_url"] . "/index.php?a=api&id=" . $re["dcim_client_id"];
		$data = ["func" => "on", "api_user" => $re["root"], "api_pass" => $re["pwd"]];
		$res = $this->dcimClientRequest($url, $data, 30);
		if ($res["status"] == "success") {
			$result = ["status" => 200, "msg" => "开机成功", "power_status" => $res["power"]];
			\think\Db::name("upper_reaches_res")->where("id", $re["id"])->update(["power_status" => $res["power"]]);
		} else {
			$result = ["status" => 400, "msg" => $res["msg"] ?? lang("ERROR MESSAGE")];
		}
		return $result;
	}
	public function dcimClientOff($re)
	{
		$url = $re["dcim_client_url"] . "/index.php?a=api&id=" . $re["dcim_client_id"];
		$data = ["func" => "off", "api_user" => $re["root"], "api_pass" => $re["pwd"]];
		$res = $this->dcimClientRequest($url, $data, 30);
		if ($res["status"] == "success") {
			$result = ["status" => 200, "msg" => "关机成功", "power_status" => $res["power"]];
			\think\Db::name("upper_reaches_res")->where("id", $re["id"])->update(["power_status" => $res["power"]]);
		} else {
			$result = ["status" => 400, "msg" => $res["msg"] ?? lang("ERROR MESSAGE")];
		}
		return $result;
	}
	public function dcimClientReboot($re)
	{
		$url = $re["dcim_client_url"] . "/index.php?a=api&id=" . $re["dcim_client_id"];
		$data = ["func" => "reboot", "api_user" => $re["root"], "api_pass" => $re["pwd"]];
		$res = $this->dcimClientRequest($url, $data, 30);
		if ($res["status"] == "success") {
			$result = ["status" => 200, "msg" => "重启成功", "power_status" => $res["power"]];
			\think\Db::name("upper_reaches_res")->where("id", $re["id"])->update(["power_status" => $res["power"]]);
		} else {
			$result = ["status" => 400, "msg" => $res["msg"] ?? lang("ERROR MESSAGE")];
		}
		return $result;
	}
	public function dcimClientVnc($re)
	{
		$url = $re["dcim_client_url"] . "/index.php?a=api&id=" . $re["dcim_client_id"];
		$data = ["func" => "novnc", "api_user" => $re["root"], "api_pass" => $re["pwd"]];
		$res = $this->dcimClientRequest($url, $data, 30);
		if ($res["status"] == "success") {
			$result["status"] = 200;
			$result["msg"] = "VNC开启成功";
			$url = "";
			if ($res["data"]["ssl"] == true) {
				$url = "wss://" . $res["data"]["host"];
			} else {
				$url = "ws://" . $res["data"]["host"];
			}
			$url .= "/websockify_" . $res["data"]["house"] . "?token=" . $res["data"]["token"];
			$result["data"]["password"] = $res["data"]["pass"];
			$result["data"]["url"] = urlencode(base64_encode($url));
		} else {
			$result = ["status" => 400, "msg" => $res["msg"] ?? lang("ERROR MESSAGE")];
		}
		return $result;
	}
	public function dcimClientReinstall($re, $params = [])
	{
		$url = $re["dcim_client_url"] . "/index.php?a=api";
		$data = ["func" => "reloadSystem", "api_user" => $re["root"], "api_pass" => $re["pwd"], "id" => $re["dcim_client_id"], "password" => $params["password"], "action" => 0, "bootloader" => "bios", "mos" => $params["os"], "mcon" => 0, "port" => $params["port"], "part_type" => $params["part_type"], "xml" => "", "disk" => 0, "check_disk_size" => 0];
		$res = $this->dcimClientRequest($url, $data, 30);
		if ($res["status"] == "success") {
			$result = ["status" => 200, "msg" => "重装发起成功"];
			if (!empty($re["hid"])) {
				$description = sprintf("发起重装系统成功 - Host ID:%d", $re["hid"]);
			} else {
				$description = sprintf("资源配置#%d发起重装系统成功", $re["id"]);
			}
		} else {
			$result = ["status" => 400, "msg" => $res["msg"] ?? lang("ERROR MESSAGE")];
			if (!empty($re["hid"])) {
				if ($this->is_admin) {
					$description = sprintf("发起重置密码失败,原因:%s - Host ID:%d", $res["msg"], $re["hid"]);
				} else {
					$description = sprintf("发起重置密码失败 - Host ID:%d", $re["hid"]);
				}
			} else {
				$description = sprintf("资源配置#%d发起重置密码失败,原因:%s", $re["id"], $res["msg"]);
			}
		}
		if (!empty($re["hid"])) {
			$product = \think\Db::name("host")->field("uid")->where("id", $re["hid"])->find();
			active_log_final("模块命令:" . $description, $product["uid"], 2, $re["hid"]);
		} else {
			active_log_final($description, 0, 2, $re["hid"]);
		}
		return $result;
	}
	public function dcimClientCrackPass($re, $params = [])
	{
		$url = $re["dcim_client_url"] . "/index.php?a=api&id=" . $re["dcim_client_id"];
		$data = ["func" => "crackpwd", "api_user" => $re["root"], "api_pass" => $re["pwd"], "password" => $params["password"], "other_user" => $params["other_user"], "user" => $params["user"]];
		$res = $this->dcimClientRequest($url, $data, 30);
		if ($res["status"] == "success") {
			$result = ["status" => 200, "msg" => "破解密码发起成功"];
			if (!empty($re["hid"])) {
				$description = sprintf("发起重置密码成功 - Host ID:%d", $re["hid"]);
			} else {
				$description = sprintf("资源配置#%d发起重置密码成功", $re["id"]);
			}
		} else {
			$result = ["status" => 400, "msg" => $res["msg"] ?? lang("ERROR MESSAGE")];
			if (!empty($re["hid"])) {
				if ($this->is_admin) {
					$description = sprintf("发起重置密码失败,原因:%s - Host ID:%d", $res["msg"], $re["hid"]);
				} else {
					$description = sprintf("发起重置密码失败 - Host ID:%d", $re["hid"]);
				}
			} else {
				$description = sprintf("资源配置#%d发起重置密码失败,原因:%s", $re["id"], $res["msg"]);
			}
		}
		if (!empty($re["hid"])) {
			$product = \think\Db::name("host")->field("uid")->where("id", $re["hid"])->find();
			active_log_final("模块命令:" . $description, $product["uid"], 2, $re["hid"]);
		} else {
			active_log_final($description, 0, 2, $re["hid"]);
		}
		return $result;
	}
	public function dcimClientCancelReinstall($re)
	{
		$url = $re["dcim_client_url"] . "/index.php?a=api";
		$data = ["func" => "cancelReinstall", "api_user" => $re["root"], "api_pass" => $re["pwd"], "id" => $re["dcim_client_id"]];
		$res = $this->dcimClientRequest($url, $data, 30);
		$task_type = $res["task_type"];
		$des = ["重装系统", "救援系统", "重置密码", "获取硬件信息"];
		if ($res["status"] == "success") {
			$result["status"] = 200;
			$result["msg"] = "取消成功";
			$result["task_type"] = $res["task_type"];
			if (!empty($re["hid"])) {
				$description = sprintf("取消%s成功 - Host ID:%d", $des[$task_type], $re["hid"]);
			} else {
				$description = sprintf("资源配置#%d取消%s成功", $re["id"], $des[$task_type]);
			}
		} else {
			$result["status"] = 400;
			$result["msg"] = $res["msg"];
			if (!empty($re["hid"])) {
				if ($this->is_admin) {
					$description = sprintf("取消%s失败,原因:%s - Host ID:%d", $des[$task_type], $res["msg"], $re["hid"]);
				} else {
					$description = sprintf("取消%s失败 - Host ID:%d", $des[$task_type], $re["hid"]);
				}
			} else {
				$description = sprintf("资源配置#%d取消%s失败,原因:%s", $re["id"], $des[$task_type], $res["msg"]);
			}
		}
		if (!empty($re["hid"])) {
			$product = \think\Db::name("host")->field("uid")->where("id", $re["hid"])->find();
			active_log_final("模块命令:" . $description, $product["uid"], 2, $re["hid"]);
		} else {
			active_log_final($description, 0, 2, $re["hid"]);
		}
		return $result;
	}
	public function dcimClientReinstallStatus($re)
	{
		$url = $re["dcim_client_url"] . "/index.php?a=api&id=" . $re["dcim_client_id"];
		$data = ["func" => "getReinstallList", "api_user" => $re["root"], "api_pass" => $re["pwd"]];
		$res = $this->dcimClientRequest($url, $data, 30);
		if ($res["status"] == "success") {
			if (empty($res["data"])) {
				$result["status"] = 200;
				$result["data"] = [];
			} else {
				$res["data"] = $res["data"][0];
				if ($res["data"]["windows_finish"]) {
					$result["status"] = 200;
					$result["data"] = [];
				} else {
					if ($res["data"]["task_type"] == 3) {
						$info = "获取硬件信息";
						$cancel = "取消获取硬件信息";
						$span = "";
					} elseif ($res["data"]["task_type"] == 2) {
						$info = "重置密码";
						$cancel = "取消重置密码";
						$span = "";
					} elseif ($res["data"]["task_type"] == 1) {
						$info = "救援系统";
						$cancel = "取消救援系统";
						$span = "";
					} else {
						$info = "重装系统";
						$cancel = "取消重装";
						$span = $res["data"]["osname"];
					}
					$result["status"] = 200;
					$result["msg"] = "获取成功";
					$result["data"]["disk_check"] = json_decode($res["data"]["disk_check"], true) ?: [];
					$result["data"]["crackPwd"] = $res["crackPwd"] ?? [];
					$result["data"]["error_type"] = $res["data"]["error_type"];
					$result["data"]["error_msg"] = $res["data"]["error_msg"];
					$result["data"]["disk_info"] = json_decode($res["data"]["disk_info"], true) ?: [];
					$result["data"]["progress"] = $res["data"]["progress"];
					$result["data"]["windows_finish"] = false;
					$result["data"]["hostid"] = $re["hid"];
					$result["data"]["task_type"] = $res["data"]["task_type"];
					$result["data"]["reinstall_msg"] = $span;
					$result["data"]["step"] = $res["data"]["description"];
					$result["data"]["info"] = $info;
					$result["data"]["cancel"] = $cancel;
				}
			}
			if (!empty($res["sales"]["osusername"])) {
				\think\Db::name("host")->where("id", $re["hid"])->update(["username" => $res["sales"]["osusername"]]);
				\think\Db::name("upper_reaches_res")->where("id", $re["id"])->update(["username" => $res["sales"]["osusername"]]);
			}
			if (!empty($res["sales"]["ospassword"])) {
				\think\Db::name("host")->where("id", $re["hid"])->update(["password" => cmf_encrypt($res["sales"]["ospassword"])]);
				\think\Db::name("upper_reaches_res")->where("id", $re["id"])->update(["password" => $res["sales"]["ospassword"]]);
			}
		} else {
			$result["status"] = 400;
			$result["msg"] = $res["msg"] ?? lang("ERROR MESSAGE");
		}
		return $result;
	}
	public function dcimClientGetOs($re)
	{
		$url = $re["dcim_client_url"] . "/index.php?a=api&id=" . $re["dcim_client_id"];
		$data = ["func" => "getOs", "api_user" => $re["root"], "api_pass" => $re["pwd"]];
		$res = $this->dcimClientRequest($url, $data, 30);
		if ($res["status"] == "success") {
			$os = $res["mirrorGroup"];
			foreach ($os as $k => &$v) {
				$v["os"] = [];
				foreach ($res["mos"][$res["house"]] as $key => $value) {
					if ($v["id"] == $value["group_id"]) {
						$v["os"][] = $value;
					}
				}
			}
			$result["status"] = 200;
			$result["os"] = $os;
		} else {
			$result["status"] = 400;
			$result["msg"] = $res["msg"];
		}
		return $result;
	}
	private function dcimClientRequest($url = "", $data = [], $timeout = 30, $method = "POST")
	{
		$result = commonCurl($url, $data, $timeout, $method);
		return $result;
	}
}