<?php

namespace app\common\logic;

class Dcim
{
	public $url = "";
	public $username = "";
	public $password = "";
	public $error = false;
	public $link_error_msg = "";
	public $curl_error = "";
	public $is_admin = false;
	private $dir = CMF_ROOT . "public/vendor/dcim";
	public $user_prefix = "";
	public $log_prefix_error = "DCIM模块错误:";
	public function __construct($serverid = 0)
	{
		if (!empty($serverid)) {
			$this->setUrl($serverid);
		}
	}
	public function init($server_info = [])
	{
		if (empty($server_info)) {
			$this->error = true;
		} else {
			$protocol = $server_info["secure"] == 1 ? "https://" : "http://";
			$url = $protocol . $server_info["hostname"];
			if (!empty($server_info["port"])) {
				$url .= ":" . $server_info["port"];
			}
			$this->url = $url;
			$this->username = $server_info["username"];
			$this->password = aesPasswordDecode($server_info["password"]);
			$this->error = false;
			$this->formatAccesshash($server_info["accesshash"]);
		}
		return $this;
	}
	public function setUrl($serverid)
	{
		if (!empty($serverid)) {
			$server_info = \think\Db::name("servers")->field("id,hostname,username,password,secure,port,accesshash")->where("id", $serverid)->where("server_type", "dcim")->find();
			if (empty($server_info)) {
				$this->error = true;
			} else {
				$protocol = $server_info["secure"] == 1 ? "https://" : "http://";
				$url = $protocol . $server_info["hostname"];
				if (!empty($server_info["port"])) {
					$url .= ":" . $server_info["port"];
				}
				$this->url = $url;
				$this->username = $server_info["username"];
				$this->password = aesPasswordDecode($server_info["password"]);
				$this->error = false;
				$this->formatAccesshash($server_info["accesshash"]);
			}
		} else {
			$this->error = true;
		}
	}
	public function setUrlByHost($hostid)
	{
		if (!empty($hostid)) {
			$server_info = \think\Db::name("host")->alias("a")->field("b.id,b.hostname,b.username,b.password,b.secure,b.port,b.accesshash")->leftJoin("servers b", "a.serverid=b.id")->where("a.id", $hostid)->where("b.server_type", "dcim")->find();
			if (empty($server_info)) {
				$this->error = true;
			} else {
				$protocol = $server_info["secure"] == 1 ? "https://" : "http://";
				$url = $protocol . $server_info["hostname"];
				if (!empty($server_info["port"])) {
					$url .= ":" . $server_info["port"];
				}
				$this->url = $url;
				$this->username = $server_info["username"];
				$this->password = aesPasswordDecode($server_info["password"]);
				$this->error = false;
				$this->formatAccesshash($server_info["accesshash"]);
			}
		} else {
			$this->error = true;
		}
	}
	public function formatAccesshash($accesshash)
	{
		$accesshash = trim($accesshash);
		if (!empty($accesshash)) {
			$accesshash = explode(":", trim($accesshash));
			unset($accesshash[0]);
			$this->user_prefix = trim(implode("", $accesshash));
		}
		return $this->user_prefix;
	}
	public function testLink()
	{
		$result = $this->post("getHouse", [], 5);
		if ($result["status"] == "error") {
			$this->link_error_msg = $result["msg"];
			return false;
		} else {
			return true;
		}
	}
	public function getIpsGroup()
	{
		$data = $this->post("getIpsGroup", [], 10);
		if ($data["status"] == "error") {
			return [];
		} else {
			return $data;
		}
	}
	public function getSaleGroup()
	{
		$data = $this->post("getSaleGroup", [], 10);
		if ($data["status"] == "error") {
			return [];
		} else {
			return $data;
		}
	}
	public function getArea()
	{
		$data = $this->post("getArea", [], 10);
		if ($data["status"] == "error") {
			return [];
		} else {
			return $data;
		}
	}
	public function getFormatOs()
	{
		$res = $this->post("getAllMirrorOs", [], 20);
		$data = [];
		if ($res["status"] == "success") {
			$svg = [1 => "Windows", 2 => "CentOS", 3 => "Ubuntu", 4 => "Debian", 5 => "ESXi", 6 => "XenServer", 7 => "FreeBSD", 8 => "Fedora", 9 => "其他"];
			$data = [];
			$os_group = array_column($res["data"]["group"], "svg", "id");
			$os_name = array_column($res["data"]["group"], "name", "id");
			foreach ($res["data"]["os"] as $v) {
				$data[] = $v["id"] . "|" . ($svg[$os_group[$v["group_id"]]] ?? $os_name[$v["group_id"]]) . "^" . $v["name"];
			}
		}
		return $data;
	}
	public function on($id, $log = true)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.config_option1,b.api_type,b.zjmf_api_id,b.id as productid")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcim")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if ($product["api_type"] == "whmcs") {
			$dcimid = \think\Db::name("customfieldsvalues")->alias("a")->leftJoin("customfields b", "a.fieldid=b.id")->where("a.relid", $id)->where("b.type", "product")->where("b.relid", $product["productid"])->where("b.fieldname", "hostid")->value("value");
			$product["dcimid"] = $dcimid;
		}
		if (empty($product["dcimid"])) {
			$result["status"] = 400;
			$result["msg"] = "服务器ID错误";
			return $result;
		}
		if ($product["api_type"] == "zjmf_api") {
			$post_data["id"] = $product["dcimid"];
			$post_data["is_api"] = 1;
			$res = zjmfCurl($product["zjmf_api_id"], "/dcim/on", $post_data);
		} elseif ($product["api_type"] == "whmcs") {
			$res = whmcsCurlPost($id, "on");
		} else {
			$this->setUrl($product["serverid"]);
			if ($this->error) {
				$result["status"] = 400;
				if ($this->is_admin) {
					$result["msg"] = "该产品未选择接口";
				} else {
					$result["msg"] = "开机失败";
				}
				return $result;
			}
			if ($product["config_option1"] == "rent") {
				$post_data["id"] = $product["dcimid"];
				$post_data["hostid"] = $id;
				$res = $this->post("ipmiOn", $post_data);
			} elseif ($product["config_option1"] == "bms") {
				$res = $this->bmsCurl("/bms/source/" . $product["dcimid"] . "/boot");
			} else {
				$result["status"] = 400;
				$result["msg"] = "产品为机柜租用，不支持该操作";
				return $result;
			}
		}
		if ($res["status"] == "success" || $res["status"] == 200 || $res["code"] == 200) {
			$result["status"] = 200;
			$result["msg"] = "开机成功";
			$description = sprintf("开机成功 - Host ID:%d", $id);
		} else {
			$result["status"] = 400;
			if ($this->is_admin) {
				$result["msg"] = $res["msg"] ?: "开机失败";
				$result["msg"] = $this->log_prefix_error . $result["msg"];
				$description = $this->log_prefix_error . sprintf("开机失败,原因:%s - Host ID:%d", $res["msg"], $id);
			} else {
				$result["msg"] = "开机失败";
				$description = sprintf("开机失败 - Host ID:%d", $id);
			}
		}
		if ($log) {
			active_log_final(sprintf("模块命令:" . $description, $product["uid"]), $product["uid"], 2, $id);
		}
		return $result;
	}
	public function off($id, $log = true)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.config_option1,b.api_type,b.zjmf_api_id,b.id as productid")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcim")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if ($product["api_type"] == "whmcs") {
			$dcimid = \think\Db::name("customfieldsvalues")->alias("a")->leftJoin("customfields b", "a.fieldid=b.id")->where("a.relid", $id)->where("b.type", "product")->where("b.relid", $product["productid"])->where("b.fieldname", "hostid")->value("value");
			$product["dcimid"] = $dcimid;
		}
		if (empty($product["dcimid"])) {
			$result["status"] = 400;
			$result["msg"] = "服务器ID错误";
			return $result;
		}
		if ($product["api_type"] == "zjmf_api") {
			$post_data["id"] = $product["dcimid"];
			$post_data["is_api"] = 1;
			$res = zjmfCurl($product["zjmf_api_id"], "/dcim/off", $post_data);
		} elseif ($product["api_type"] == "whmcs") {
			$res = whmcsCurlPost($id, "off");
		} else {
			$this->setUrl($product["serverid"]);
			if ($this->error) {
				$result["status"] = 400;
				if ($this->is_admin) {
					$result["msg"] = "该产品未选择接口";
				} else {
					$result["msg"] = "关机失败";
				}
				return $result;
			}
			if ($product["config_option1"] == "rent") {
				$post_data["id"] = $product["dcimid"];
				$post_data["hostid"] = $id;
				$res = $this->post("ipmiOff", $post_data);
			} elseif ($product["config_option1"] == "bms") {
				$res = $this->bmsCurl("/bms/source/" . $product["dcimid"] . "/shutdown");
			} else {
				$result["status"] = 400;
				$result["msg"] = "产品为机柜租用，不支持该操作";
				return $result;
			}
		}
		if ($res["status"] == "success" || $res["status"] == 200 || $res["code"] == 200) {
			$result["status"] = 200;
			$result["msg"] = "关机成功";
			$description = sprintf("关机成功 - Host ID:%d", $id);
		} else {
			$result["status"] = 400;
			if ($this->is_admin) {
				$result["msg"] = $res["msg"] ?: "关机失败";
				$result["msg"] = $this->log_prefix_error . $result["msg"];
				$description = $this->log_prefix_error . sprintf("关机失败,原因:%s - Host ID:%d", $res["msg"], $id);
			} else {
				$result["msg"] = "关机失败";
				$description = sprintf("关机失败 - Host ID:%d", $id);
			}
		}
		if ($log) {
			active_log_final(sprintf("模块命令:" . $description, $product["uid"]), $product["uid"], 2, $id);
		}
		return $result;
	}
	public function reboot($id, $log = true)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.config_option1,b.api_type,b.zjmf_api_id,b.id as productid")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcim")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if ($product["api_type"] == "whmcs") {
			$dcimid = \think\Db::name("customfieldsvalues")->alias("a")->leftJoin("customfields b", "a.fieldid=b.id")->where("a.relid", $id)->where("b.type", "product")->where("b.relid", $product["productid"])->where("b.fieldname", "hostid")->value("value");
			$product["dcimid"] = $dcimid;
		}
		if (empty($product["dcimid"])) {
			$result["status"] = 400;
			$result["msg"] = "服务器ID错误";
			return $result;
		}
		if ($product["api_type"] == "zjmf_api") {
			$post_data["id"] = $product["dcimid"];
			$post_data["is_api"] = 1;
			$res = zjmfCurl($product["zjmf_api_id"], "/dcim/reboot", $post_data);
		} elseif ($product["api_type"] == "whmcs") {
			$res = whmcsCurlPost($id, "reboot");
		} else {
			$this->setUrl($product["serverid"]);
			if ($this->error) {
				$result["status"] = 400;
				if ($this->is_admin) {
					$result["msg"] = "该产品未选择接口";
				} else {
					$result["msg"] = "重启失败";
				}
				return $result;
			}
			if ($product["config_option1"] == "rent") {
				$post_data["id"] = $product["dcimid"];
				$post_data["hostid"] = $id;
				$res = $this->post("ipmiReboot", $post_data);
			} elseif ($product["config_option1"] == "bms") {
				$res = $this->bmsCurl("/bms/source/" . $product["dcimid"] . "/reboot");
			} else {
				$result["status"] = 400;
				$result["msg"] = "产品为机柜租用，不支持该操作";
				return $result;
			}
		}
		if ($res["status"] == "success" || $res["status"] == 200 || $res["code"] == 200) {
			$result["status"] = 200;
			$result["msg"] = "重启成功";
			$description = sprintf("重启成功 - Host ID:%d", $id);
		} else {
			$result["status"] = 400;
			if ($this->is_admin) {
				$result["msg"] = $res["msg"] ?: "重启失败";
				$result["msg"] = $this->log_prefix_error . $result["msg"];
				$description = $this->log_prefix_error . sprintf("重启失败,原因:%s - Host ID:%d", $res["msg"], $id);
			} else {
				$result["msg"] = "重启失败";
				$description = sprintf("重启失败 - Host ID:%d", $id);
			}
		}
		if ($log) {
			active_log_final(sprintf("模块命令:" . $description, $product["uid"]), $product["uid"], 2, $id);
		}
		return $result;
	}
	public function traffic($id, $params = [])
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.config_option1,b.api_type,b.zjmf_api_id,b.id productid")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcim")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if ($product["api_type"] == "whmcs") {
			$dcimid = \think\Db::name("customfieldsvalues")->alias("a")->leftJoin("customfields b", "a.fieldid=b.id")->where("a.relid", $id)->where("b.type", "product")->where("b.relid", $product["productid"])->where("b.fieldname", "hostid")->value("value");
			$product["dcimid"] = $dcimid;
		}
		if (empty($product["dcimid"])) {
			$result["status"] = 400;
			$result["msg"] = "服务器ID错误";
			return $result;
		}
		if ($product["api_type"] == "zjmf_api") {
			$params["id"] = $product["dcimid"];
			$params["is_api"] = 1;
			$result = zjmfCurl($product["zjmf_api_id"], "/dcim/traffic", $params);
		} elseif ($product["api_type"] == "whmcs") {
			$whmcs_post = ["switch_id" => $params["switch_id"], "port_name" => $params["port_name"], "start_time" => $params["start_time"], "end_time" => $params["end_time"]];
			$res = whmcsCurlPost($id, "traffic", $whmcs_post);
			if ($res["status"] == "success") {
				$result["status"] = 200;
				$result["data"]["unit"] = $res["y_unit"];
				$result["data"]["traffic"] = [];
				foreach ($res["in"] as $k => $v) {
					$result["data"]["traffic"][] = ["time" => $k, "value" => round($v, 2), "type" => "in"];
				}
				foreach ($res["out"] as $k => $v) {
					$result["data"]["traffic"][] = ["time" => $k, "value" => round($v, 2), "type" => "out"];
				}
			} else {
				if ($res["nosupport"] == "nosupport") {
					$result["status"] = 400;
					$result["msg"] = "不支持流量图";
					$result["data"]["support"] = false;
				} else {
					$result["status"] = 400;
					$result["msg"] = "获取失败";
					$result["data"]["support"] = true;
				}
			}
		} else {
			$this->setUrl($product["serverid"]);
			if ($this->error) {
				$result["status"] = 400;
				if ($this->is_admin) {
					$result["msg"] = "该产品未选择接口";
				} else {
					$result["msg"] = "流量图获取失败";
				}
				return $result;
			}
			if ($product["config_option1"] == "rent") {
				$post_data["id"] = $product["dcimid"];
				$post_data["hostid"] = $id;
				$post_data["reverse"] = 1;
				$post_data["start_time"] = $params["start_time"];
				$post_data["end_time"] = $params["end_time"];
				$post_data["port_name"] = $params["port_name"];
				$post_data["switch_id"] = $params["switch_id"];
				$post_data["type"] = $params["type"] ?? "";
				if ($post_data["type"] == "server") {
					$post_data["switch_id"] = $product["dcimid"];
				}
				$res = $this->post("traffic", $post_data);
				if ($res["status"] == "success") {
					$result["status"] = 200;
					$result["data"]["unit"] = $res["y_unit"];
					$result["data"]["traffic"] = [];
					foreach ($res["in"] as $k => $v) {
						$result["data"]["traffic"][] = ["time" => $k, "value" => round($v, 2), "type" => "in"];
					}
					foreach ($res["out"] as $k => $v) {
						$result["data"]["traffic"][] = ["time" => $k, "value" => round($v, 2), "type" => "out"];
					}
				} else {
					if ($res["nosupport"] == "nosupport") {
						$result["status"] = 400;
						$result["msg"] = "不支持流量图";
						$result["data"]["support"] = false;
					} else {
						$result["status"] = 400;
						$result["msg"] = "获取失败";
						$result["data"]["support"] = true;
					}
				}
			} else {
				$result["status"] = 400;
				$result["msg"] = "产品为机柜租用，不支持该操作";
			}
		}
		return $result;
	}
	public function kvm($id)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.config_option1,b.api_type,b.zjmf_api_id")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcim")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if (empty($product["dcimid"])) {
			$result["status"] = 400;
			$result["msg"] = "服务器ID错误";
			return $result;
		}
		if ($product["api_type"] == "zjmf_api") {
			$post_data["id"] = $product["dcimid"];
			$post_data["is_api"] = 1;
			$res = zjmfCurl($product["zjmf_api_id"], "/dcim/kvm", $post_data);
		} else {
			$this->setUrl($product["serverid"]);
			if ($this->error) {
				$result["status"] = 400;
				if ($this->is_admin) {
					$result["msg"] = "该产品未选择接口";
				} else {
					$result["msg"] = "获取kvm失败";
				}
				return $result;
			}
			if ($product["config_option1"] == "rent") {
				$post_data["id"] = $product["dcimid"];
				$post_data["hostid"] = $id;
				$res = $this->post("ipmiKvm", $post_data);
			} else {
				$result["status"] = 400;
				$result["msg"] = "产品为机柜租用，不支持该操作";
				return $result;
			}
		}
		if ($res["status"] == "success") {
			$data = $this->curl_get($res["url"]);
			$name = array_pop(explode("/", $res["url"]));
			$save_path = UPLOAD_PATH . "common/default/" . $name;
			file_put_contents($save_path, $data);
			$result["status"] = 200;
			$result["name"] = str_replace(".jnlp", "", $name);
			$result["token"] = aesPasswordEncode(time() . "|zjmf");
			$result["data"]["content"] = $data;
			$description = sprintf("获取kvm成功 - Host ID:%d", $id);
		} elseif ($res["status"] == 200) {
			$name = trim($res["name"], "./");
			$save_path = UPLOAD_PATH . "common/default/" . $name . ".jnlp";
			file_put_contents($save_path, $res["data"]["content"]);
			$result["status"] = 200;
			$result["name"] = $name;
			$result["token"] = aesPasswordEncode(time() . "|zjmf");
			$result["data"]["content"] = $res["data"]["content"];
			$description = sprintf("获取kvm成功 - Host ID:%d", $id);
		} else {
			$result["status"] = 400;
			$result["msg"] = "下载java失败";
			if ($this->is_admin) {
				$result["msg"] = $this->log_prefix_error . $result["msg"];
				$description = $this->log_prefix_error . sprintf("获取kvm失败,原因:%s - Host ID:%d", $res["msg"], $id);
			} else {
				$description = sprintf("获取kvm失败 - Host ID:%d", $id);
			}
		}
		active_log_final(sprintf("模块命令:" . $description, $product["uid"]), $product["uid"], 2, $id);
		return $result;
	}
	public function ikvm($id)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.config_option1,b.api_type,b.zjmf_api_id")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcim")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if (empty($product["dcimid"])) {
			$result["status"] = 400;
			$result["msg"] = "服务器ID错误";
			return $result;
		}
		if ($product["api_type"] == "zjmf_api") {
			$post_data["id"] = $product["dcimid"];
			$post_data["is_api"] = 1;
			$res = zjmfCurl($product["zjmf_api_id"], "/dcim/ikvm", $post_data);
		} else {
			$this->setUrl($product["serverid"]);
			if ($this->error) {
				$result["status"] = 400;
				if ($this->is_admin) {
					$result["msg"] = "该产品未选择接口";
				} else {
					$result["msg"] = "获取ikvm失败";
				}
				return $result;
			}
			if ($product["config_option1"] == "rent") {
				$post_data["id"] = $product["dcimid"];
				$post_data["hostid"] = $id;
				$res = $this->post("ipmiIkvm", $post_data);
			} else {
				$result["status"] = 400;
				$result["msg"] = "产品为机柜租用，不支持该操作";
				return $result;
			}
		}
		if ($res["status"] == "success") {
			$data = $this->curl_get($res["url"]);
			$name = array_pop(explode("/", $res["url"]));
			$save_path = UPLOAD_PATH . "common/default/" . $name;
			file_put_contents($save_path, $data);
			$result["status"] = 200;
			$result["name"] = str_replace(".jnlp", "", $name);
			$result["data"]["content"] = $data;
			$result["token"] = aesPasswordEncode(time() . "|zjmf");
			$description = sprintf("获取ikvm成功 - Host ID:%d", $id);
		} elseif ($res["status"] == 200) {
			$name = trim($res["name"], "./");
			$save_path = UPLOAD_PATH . "common/default/" . $name . ".jnlp";
			file_put_contents($save_path, $res["data"]["content"]);
			$result["status"] = 200;
			$result["name"] = $name;
			$result["data"]["content"] = $res["data"]["content"];
			$result["token"] = aesPasswordEncode(time() . "|zjmf");
			$description = sprintf("获取ikvm成功 - Host ID:%d", $id);
		} else {
			$result["status"] = 400;
			$result["msg"] = "下载java失败";
			if ($this->is_admin) {
				$result["msg"] = $this->log_prefix_error . $result["msg"];
				$description = $this->log_prefix_error . sprintf("获取ikvm失败,原因:%s - Host ID:%d", $res["msg"], $id);
			} else {
				$description = sprintf("获取ikvm失败 - Host ID:%d", $id);
			}
		}
		active_log_final(sprintf("模块命令:" . $description, $product["uid"]), $product["uid"], 2, $id);
		return $result;
	}
	public function bmc($id)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.config_option1,b.api_type,b.zjmf_api_id,b.id as productid")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcim")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if ($product["api_type"] == "whmcs") {
			$dcimid = \think\Db::name("customfieldsvalues")->alias("a")->leftJoin("customfields b", "a.fieldid=b.id")->where("a.relid", $id)->where("b.type", "product")->where("b.relid", $product["productid"])->where("b.fieldname", "hostid")->value("value");
			$product["dcimid"] = $dcimid;
		}
		if (empty($product["dcimid"])) {
			$result["status"] = 400;
			$result["msg"] = "服务器ID错误";
			return $result;
		}
		if ($product["api_type"] == "zjmf_api") {
			$post_data["id"] = $product["dcimid"];
			$post_data["is_api"] = 1;
			$res = zjmfCurl($product["zjmf_api_id"], "/dcim/bmc", $post_data);
		} elseif ($product["api_type"] == "whmcs") {
			$res = whmcsCurlPost($id, "bmc");
		} else {
			$this->setUrl($product["serverid"]);
			if ($this->error) {
				$result["status"] = 400;
				if ($this->is_admin) {
					$result["msg"] = "该产品未选择接口";
				} else {
					$result["msg"] = "重置BMC失败";
				}
				return $result;
			}
			if ($product["config_option1"] == "rent") {
				$post_data["id"] = $product["dcimid"];
				$post_data["hostid"] = $id;
				$res = $this->post("ipmiMcresetcold", $post_data);
			} else {
				$result["status"] = 400;
				$result["msg"] = "产品为机柜租用，不支持该操作";
				return $result;
			}
		}
		if ($res["status"] == "success" || $res["status"] == 200) {
			$result["status"] = 200;
			$result["msg"] = "重置成功";
			$description = sprintf("重置BMC成功 - Host ID:%d", $id);
		} else {
			$result["status"] = 400;
			if ($this->is_admin) {
				$result["msg"] = $res["msg"] ?: "重置BMC失败";
				$result["msg"] = $this->log_prefix_error . $result["msg"];
				$description = $this->log_prefix_error . sprintf("重置BMC失败,原因:%s - Host ID:%d", $res["msg"], $id);
			} else {
				$result["msg"] = "重置BMC失败";
				$description = sprintf("重置BMC失败 - Host ID:%d", $id);
			}
		}
		active_log_final(sprintf("模块命令:" . $description, $product["uid"]), $product["uid"], 2, $id);
		return $result;
	}
	public function novnc($id, $restart = false, $is_common = false)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.password,b.config_option1,b.api_type,b.zjmf_api_id,b.id as productid")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcim")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if ($product["api_type"] == "whmcs") {
			$dcimid = \think\Db::name("customfieldsvalues")->alias("a")->leftJoin("customfields b", "a.fieldid=b.id")->where("a.relid", $id)->where("b.type", "product")->where("b.relid", $product["productid"])->where("b.fieldname", "hostid")->value("value");
			$product["dcimid"] = $dcimid;
		}
		if (empty($product["dcimid"])) {
			$result["status"] = 400;
			$result["msg"] = "服务器ID错误";
			return $result;
		}
		if ($product["api_type"] == "zjmf_api") {
			$post_data["id"] = $product["dcimid"];
			$post_data["is_api"] = 1;
			$result = zjmfCurl($product["zjmf_api_id"], "/dcim/novnc", $post_data);
		} elseif ($product["api_type"] == "whmcs") {
			$result = whmcsCurlPost($id, "vnc", [], 120);
			if ($result["status"] == 200) {
				$url = $result["host"];
				if ($result["ssl"] == "on") {
					$link_url = "wss://" . $url;
				} else {
					$link_url = "ws://" . $url;
				}
				$link_url .= "/websockify_" . $result["house"] . "?token=" . $result["token"];
				$result["password"] = $result["vnc_pass"];
				if ($this->is_admin) {
					$result["url"] = request()->domain() . "/" . config("database.admin_application") . "/dcim/novnc?url=" . urlencode(base64_encode($link_url)) . "&password=" . $result["vnc_pass"] . "&host_token=" . urlencode(aesPasswordEncode(cmf_decrypt($product["password"])));
				} else {
					$result["url"] = request()->domain() . "/dcim/novnc?url=" . urlencode(base64_encode($link_url)) . "&password=" . $result["vnc_pass"] . "&host_token=" . urlencode(aesPasswordEncode(cmf_decrypt($product["password"])));
				}
				$result["data"] = ["password" => $result["vnc_pass"], "url" => $result["url"]];
			} else {
				$result["status"] = 400;
				$result["msg"] = $result["msg"] ?: "vnc启动失败";
			}
		} else {
			$this->setUrl($product["serverid"]);
			if ($this->error) {
				$result["status"] = 400;
				if ($this->is_admin) {
					$result["msg"] = "该产品未选择接口";
				} else {
					$result["msg"] = "vnc启动失败";
				}
				return $result;
			}
			if ($product["config_option1"] == "rent") {
				$post_data["id"] = $product["dcimid"];
				$post_data["hostid"] = $id;
				if ($restart) {
					$res = $this->post("ipmiVncRestart", $post_data);
				} else {
					$res = $this->post("ipmiVnc", $post_data);
				}
				if ($res["status"] == "success") {
					$result["status"] = 200;
					$result["msg"] = $res["msg"];
					if ($is_common) {
						$link_url = "";
						if (strpos($this->url, "https://") !== false) {
							$link_url = str_replace("https://", "wss://", $this->url);
						} else {
							$link_url = str_replace("http://", "ws://", $this->url);
						}
						$link_url .= "/websockify_" . $res["house_id"] . "?token=" . $res["token"];
						if ($this->is_admin) {
							$result["url"] = request()->domain() . "/" . config("database.admin_application") . "/dcim/novnc?url=" . urlencode(base64_encode($link_url)) . "&password=" . $res["pass"] . "&host_token=" . urlencode(aesPasswordEncode(cmf_decrypt($product["password"]))) . "&id=" . $id . "&type=dcim";
						} else {
							$result["url"] = request()->domain() . "/dcim/novnc?url=" . urlencode(base64_encode($link_url)) . "&password=" . $res["pass"] . "&host_token=" . urlencode(aesPasswordEncode(cmf_decrypt($product["password"]))) . "&id=" . $id . "&type=dcim";
						}
					} else {
						$url = "";
						if (strpos($this->url, "https://") !== false) {
							$url = str_replace("https://", "wss://", $this->url);
						} else {
							$url = str_replace("http://", "ws://", $this->url);
						}
						$url .= "/websockify_" . $res["house_id"] . "?token=" . $res["token"];
						$result["data"]["password"] = $res["pass"];
						$result["data"]["url"] = urlencode(base64_encode($url));
					}
				} else {
					$result["status"] = 400;
					if ($this->is_admin) {
						$result["msg"] = $res["msg"] ?: "novnc启动失败";
					} else {
						$result["msg"] = "novnc启动失败";
					}
				}
			} elseif ($product["config_option1"] == "bms") {
				$res = $this->bmsCurl("/bms/source/" . $product["dcimid"] . "/vnc");
				if ($res["code"] == 200) {
					if (strpos($res["url"], "wss://") === 0 || strpos($res["url"], "ws://") === 0) {
						$link_url = $res["url"];
					} else {
						if (strpos($this->url, "https://") !== false) {
							$link_url = str_replace("https://", "wss://", $this->url);
						} else {
							$link_url = str_replace("http://", "ws://", $this->url);
						}
						$arr = explode("?", $res["url"]);
						array_shift($arr);
						$arr = implode("&", $arr);
						$arr = explode("&", $arr);
						foreach ($arr as $v) {
							if (strpos($v, "path=") === 0) {
								$link_url .= "/" . str_replace("path=", "", $v);
							}
							if (strpos($v, "token=") === 0) {
								$res["token"] = $v;
							}
							if (strpos($v, "password=") === 0) {
								$res["vnc_pass"] = str_replace("password=", "", $v);
							}
						}
						$link_url = rtrim($link_url, "/");
						$link_url .= "?" . $res["token"];
					}
					$result["status"] = 200;
					if ($this->is_admin) {
						$result["url"] = request()->domain() . "/" . config("database.admin_application") . "/dcim/novnc?url=" . urlencode(base64_encode($link_url)) . "&password=" . $res["vnc_pass"] . "&host_token=" . urlencode(aesPasswordEncode(cmf_decrypt($product["password"])));
					} else {
						$result["url"] = request()->domain() . "/dcim/novnc?url=" . urlencode(base64_encode($link_url)) . "&password=" . $res["vnc_pass"] . "&host_token=" . urlencode(aesPasswordEncode(cmf_decrypt($product["password"])));
					}
				} else {
					$result["status"] = 400;
					$result["msg"] = $res["msg"] ?: "vnc启动失败";
				}
			} else {
				$result["status"] = 400;
				$result["msg"] = "产品为机柜租用，不支持该操作";
			}
		}
		if ($this->is_admin && $result["status"] != 200) {
			$result["msg"] = $this->log_prefix_error . $result["msg"];
		}
		return $result;
	}
	public function rescue($id, $system = 1)
	{
		if (!in_array($system, [1, 2])) {
			$result["status"] = 400;
			$result["msg"] = "操作系统错误";
			return $result;
		}
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.config_option1,b.api_type,b.zjmf_api_id")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcim")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if (empty($product["dcimid"])) {
			$result["status"] = 400;
			$result["msg"] = "服务器ID错误";
			return $result;
		}
		if ($product["api_type"] == "zjmf_api") {
			$post_data["id"] = $product["dcimid"];
			$post_data["system"] = $system;
			$post_data["is_api"] = 1;
			$res = zjmfCurl($product["zjmf_api_id"], "/dcim/rescue", $post_data);
		} else {
			$this->setUrl($product["serverid"]);
			if ($this->error) {
				$result["status"] = 400;
				if ($this->is_admin) {
					$result["msg"] = "该产品未选择接口";
				} else {
					$result["msg"] = "救援系统失败";
				}
				return $result;
			}
			if ($product["config_option1"] == "rent") {
				$post_data["id"] = $product["dcimid"];
				$post_data["hostid"] = $id;
				$post_data["system"] = $system;
				$res = $this->post("ipmiRescueSystem", $post_data);
			} else {
				$result["status"] = 400;
				$result["msg"] = "产品为机柜租用，不支持该操作";
				return $result;
			}
		}
		if ($res["status"] == "success" || $res["status"] == 200) {
			$result["status"] = 200;
			$result["msg"] = "发起救援系统成功";
			$description = sprintf("发起救援系统成功 - Host ID:%d", $id);
			if (!$this->is_admin) {
				\think\facade\Cache::set("show_last_act_message_" . $id, $product["show_last_act_message"]);
				\think\Db::name("host")->where("id", $id)->update(["show_last_act_message" => 1]);
			}
		} else {
			$result["status"] = 400;
			if ($this->is_admin) {
				$result["msg"] = $res["msg"] ?: "救援系统发起失败";
				$result["msg"] = $this->log_prefix_error . $result["msg"];
				$description = $this->log_prefix_error . sprintf("发起救援系统失败,原因:%s - Host ID:%d", $res["msg"], $id);
			} else {
				$result["msg"] = "救援系统发起失败";
				$description = sprintf("发起救援系统失败 - Host ID:%d", $id);
			}
		}
		active_log_final(sprintf("模块命令:" . $description, $product["uid"]), $product["uid"], 2, $id);
		return $result;
	}
	public function crackPass($id, $params = [])
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.show_last_act_message,a.uid,b.config_option1,b.api_type,b.zjmf_api_id,b.password,a.productid")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcim")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if ($product["api_type"] == "whmcs") {
			$dcimid = \think\Db::name("customfieldsvalues")->alias("a")->leftJoin("customfields b", "a.fieldid=b.id")->where("a.relid", $id)->where("b.type", "product")->where("b.relid", $product["productid"])->where("b.fieldname", "hostid")->value("value");
			$product["dcimid"] = $dcimid;
		}
		if (empty($product["dcimid"])) {
			$result["status"] = 400;
			$result["msg"] = "服务器ID错误";
			return $result;
		}
		if ($product["api_type"] == "zjmf_api") {
			$post_data["id"] = $product["dcimid"];
			$post_data["password"] = $params["crack_password"];
			$post_data["other_user"] = $params["other_user"];
			$post_data["user"] = $params["user"];
			$post_data["action"] = $params["action"];
			$post_data["is_api"] = 1;
			$res = zjmfCurl($product["zjmf_api_id"], "/dcim/crack_pass", $post_data);
		} elseif ($product["api_type"] == "whmcs") {
			$res = whmcsCurlPost($id, "repassword", ["password" => $params["crack_password"]]);
			if ($res["status"] == 200) {
				$host_logic = new Host();
				$host_logic->sync($id);
			}
		} else {
			$this->setUrl($product["serverid"]);
			if ($this->error) {
				$result["status"] = 400;
				if ($this->is_admin) {
					$result["msg"] = "该产品未选择接口";
				} else {
					$result["msg"] = "重置密码失败";
				}
				return $result;
			}
			if ($product["config_option1"] == "rent") {
				$post_data["id"] = $product["dcimid"];
				$post_data["hostid"] = $id;
				$post_data = array_merge($post_data, $params);
				$res = $this->post("ipmiCrackPwd", $post_data);
			} else {
				$result["status"] = 400;
				$result["msg"] = "产品为机柜租用，不支持该操作";
				return $result;
			}
		}
		if ($res["status"] == "success") {
			$result["status"] = 200;
			$result["msg"] = "发起成功";
			$description = sprintf("发起重置密码成功 - Host ID:%d", $id);
			if (!$this->is_admin) {
				\think\facade\Cache::set("show_last_act_message_" . $id, $product["show_last_act_message"]);
				\think\Db::name("host")->where("id", $id)->update(["show_last_act_message" => 1]);
			}
		} elseif ($res["status"] == 200) {
			$result["status"] = 200;
			$result["msg"] = "发起成功";
			$description = sprintf("发起重置密码成功 - Host ID:%d", $id);
		} else {
			$result["status"] = 400;
			if ($this->is_admin) {
				$result["msg"] = $res["msg"] ?: "发起重置密码失败";
				$result["msg"] = $this->log_prefix_error . $result["msg"];
				$description = $this->log_prefix_error . sprintf("发起重置密码失败,原因:%s - Host ID:%d", $res["msg"], $id);
			} else {
				$result["msg"] = "发起重置密码失败";
				$description = sprintf("发起重置密码失败 - Host ID:%d", $id);
			}
		}
		active_log_final(sprintf("模块命令:" . $description, $product["uid"]), $product["uid"], 2, $id);
		return $result;
	}
	public function reinstall($id, $params = [])
	{
		if (empty($params["rootpass"])) {
			$result["status"] = 400;
			$result["msg"] = "密码不能为空";
			return $result;
		}
		$product = \think\Db::name("host")->alias("a")->field("a.uid,a.serverid,a.productid,a.dcimid,a.reinstall_info,a.show_last_act_message,a.os os_name,b.config_option1,b.api_type,b.zjmf_api_id,b.upstream_price_type,b.upstream_price_value,c.reinstall_times,c.os")->leftJoin("products b", "a.productid=b.id")->leftJoin("dcim_servers c", "a.serverid=c.serverid")->where("b.type", "dcim")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if ($product["api_type"] == "whmcs") {
			$dcimid = \think\Db::name("customfieldsvalues")->alias("a")->leftJoin("customfields b", "a.fieldid=b.id")->where("a.relid", $id)->where("b.type", "product")->where("b.relid", $product["productid"])->where("b.fieldname", "hostid")->value("value");
			$product["dcimid"] = $dcimid;
		}
		if (empty($product["dcimid"])) {
			$result["status"] = 400;
			$result["msg"] = "服务器ID错误";
			return $result;
		}
		$Shop = new Shop(0);
		$check_pass = $Shop->checkHostPassword($params["rootpass"], $product["productid"]);
		if ($check_pass["status"] != 200) {
			return $check_pass;
		}
		$r = \think\Db::name("product_config_options_sub")->alias("a")->field("a.*")->leftJoin("product_config_options b", "a.config_id=b.id")->leftJoin("product_config_links c", "b.gid=c.gid")->where("c.pid", $product["productid"])->where("a.id", $params["mos"])->find();
		if (empty($r) && $product["api_type"] != "whmcs") {
			$result["status"] = 406;
			$result["msg"] = "操作系统错误";
			return $result;
		}
		$arr = explode("|", $r["option_name"]);
		if ($product["api_type"] != "whmcs") {
			$params["mos"] = $arr[0];
		}
		if (strpos($arr[1], "^") !== false) {
			$os_arr = explode("^", $arr[1]);
			$os_name = $os_arr[1];
			$os_group = $os_arr[0];
		} else {
			$os_name = $arr[1];
			$os_group = "";
		}
		if ($product["api_type"] == "zjmf_api") {
			$post_data = input("post.");
			$post_data["id"] = $product["dcimid"];
			$post_data["os"] = $r["upstream_id"];
			$post_data["is_api"] = 1;
			$result = zjmfCurl($product["zjmf_api_id"], "/dcim/reinstall", $post_data);
			if ($result["status"] == 400 && isset($result["price"]) && $product["upstream_price_type"] == "percent") {
				$result["price"] = round($result["price"] * $product["upstream_price_value"] / 100, 2);
			}
			if ($result["status"] == 200) {
				$description = sprintf("重装系统为" . $os_name . "发起成功 - Host ID:%d", $id);
				$old = \think\Db::name("host_config_options")->where("relid", $id)->where("configid", $r["config_id"])->find();
				if (!empty($old)) {
					\think\Db::name("host_config_options")->where("id", $old["id"])->update(["optionid" => $r["id"]]);
				} else {
					$data = ["relid" => $id, "configid" => $r["config_id"], "optionid" => $r["id"], "qty" => 0];
					\think\Db::name("host_config_options")->insert($data);
				}
				\think\Db::name("host")->where("id", $id)->update(["os" => $os_name, "os_url" => $os_group]);
				if ($product["os_name"] != $os_name) {
					pushHostInfo($id);
				}
			} else {
				if ($result["status"] == 400 && !$result["confirm"] && !isset($result["price"])) {
					if ($this->is_admin) {
						$result["msg"] = $this->log_prefix_error . $result["msg"];
						$description = $this->log_prefix_error . sprintf("重装系统发起失败,原因:%s - Host ID:%d", $result["msg"], $id);
					} else {
						$result["msg"] = "重装系统发起失败";
						$description = sprintf("重装系统发起失败 - Host ID:%d", $id);
					}
				}
			}
		} elseif ($product["api_type"] == "whmcs") {
			$result = whmcsCurlPost($id, "reinstall", ["os_id" => $params["mos"], "dcim" => ["password" => $params["rootpass"], "port" => $params["port"], "part_type" => $params["part_type"]]], 120);
			if ($result["status"] == 200) {
				$host_logic = new Host();
				$host_logic->sync($id);
			}
		} else {
			$this->setUrl($product["serverid"]);
			if ($this->error) {
				$result["status"] = 400;
				if ($this->is_admin) {
					$result["msg"] = "该产品未选择接口";
				} else {
					$result["msg"] = "重装系统失败";
				}
				return $result;
			}
			if ($product["config_option1"] == "rent") {
				$post_data["id"] = $product["dcimid"];
				$post_data["hostid"] = $id;
				$post_data = array_merge($post_data, $params);
				$res = $this->post("reinstallSystem", $post_data);
				if ($res["status"] == "success") {
					$result["status"] = 200;
					$result["msg"] = "重装发起成功";
					$description = sprintf("重装系统为" . $os_name . "发起成功 - Host ID:%d", $id);
					if (!$this->is_admin && $product["reinstall_times"] > 0) {
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
						\think\facade\Cache::set("show_last_act_message_" . $id, $product["show_last_act_message"]);
						\think\Db::name("host")->where("id", $id)->update(["reinstall_info" => json_encode($d), "show_last_act_message" => 1]);
					}
					$old = \think\Db::name("host_config_options")->where("relid", $id)->where("configid", $r["config_id"])->find();
					if (!empty($old)) {
						\think\Db::name("host_config_options")->where("id", $old["id"])->update(["optionid" => $r["id"]]);
					} else {
						$data = ["relid" => $id, "configid" => $r["config_id"], "optionid" => $r["id"], "qty" => 0];
						\think\Db::name("host_config_options")->insert($data);
					}
					\think\Db::name("host")->where("id", $id)->update(["os" => $os_name, "os_url" => $os_group]);
					if ($host["os_name"] != $os_name) {
						pushHostInfo($id);
					}
				} elseif ($res["status"] == "confirm") {
					$result["status"] = 400;
					$result["msg"] = $res["msg"];
					$result["confirm"] = true;
				} else {
					$result["status"] = 400;
					$result["confirm"] = false;
					if ($this->is_admin) {
						$result["msg"] = $res["msg"];
						$result["msg"] = $this->log_prefix_error . $result["msg"];
						$description = $this->log_prefix_error . sprintf("重装系统发起失败,原因:%s - Host ID:%d", $res["msg"], $id);
					} else {
						$result["msg"] = "重装系统发起失败";
						$description = sprintf("重装系统发起失败 - Host ID:%d", $id);
					}
				}
			} else {
				$result["status"] = 400;
				$result["msg"] = "产品为机柜租用，不支持该操作";
			}
		}
		if (!empty($description)) {
			active_log_final("模块命令:" . $description, $product["uid"], 2, $id);
		}
		return $result;
	}
	public function getMirrorOsConfig($id)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.reinstall_info,b.config_option1,a.uid,c.reinstall_times")->leftJoin("products b", "a.productid=b.id")->leftJoin("dcim_servers c", "a.serverid=c.serverid")->where("b.type", "dcim")->where("a.id", $id)->find();
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
			$result["msg"] = "服务器ID错误";
			return $result;
		}
		if ($product["config_option1"] == "rent") {
			$post_data["id"] = $product["dcimid"];
			$res = $this->post("getMirrorOsConfig", $post_data);
			$result["status"] = "success";
			$result["data"] = $res["config"];
		} else {
			$result["status"] = 400;
			$result["msg"] = "产品为机柜租用，不支持该操作";
		}
		return $result;
	}
	public function unsuspendReload($id, $disk_part)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.reinstall_info,b.config_option1,a.uid,c.reinstall_times")->leftJoin("products b", "a.productid=b.id")->leftJoin("dcim_servers c", "a.serverid=c.serverid")->where("b.type", "dcim")->where("a.id", $id)->find();
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
			$result["msg"] = "服务器ID错误";
			return $result;
		}
		if ($product["config_option1"] == "rent") {
			$post_data["id"] = $product["dcimid"];
			$post_data["hostid"] = $id;
			$post_data["disk_part"] = $disk_part;
			$post_data = array_merge($post_data, $params);
			$res = $this->post("unsuspendReloadByServer", $post_data);
			if ($res["status"] == "success") {
				$result["status"] = 200;
				$result["msg"] = "操作成功";
				$description = sprintf("重装解除暂停成功 - Host ID:%d", $id);
			} else {
				$result["status"] = 400;
				$result["msg"] = $res["msg"];
				if ($this->is_admin) {
					$result["msg"] = $this->log_prefix_error . $result["msg"];
					$description = $this->log_prefix_error . sprintf("重装解除暂停失败,原因:%s - Host ID:%d", $res["msg"], $id);
				} else {
					$description = sprintf("重装解除暂停失败 - Host ID:%d", $id);
				}
			}
			active_log_final($description, $product["uid"], 2, $id);
		} else {
			$result["status"] = 400;
			$result["msg"] = "产品为机柜租用，不支持该操作";
		}
		return $result;
	}
	public function cancelReinstall($id)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.reinstall_info,a.uid,b.config_option1,b.api_type,b.zjmf_api_id,c.reinstall_times")->leftJoin("products b", "a.productid=b.id")->leftJoin("dcim_servers c", "a.serverid=c.serverid")->where("b.type", "dcim")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if (empty($product["dcimid"])) {
			$result["status"] = 400;
			$result["msg"] = "服务器ID错误";
			return $result;
		}
		if ($product["api_type"] == "zjmf_api") {
			$post_data["id"] = $product["dcimid"];
			$post_data["is_api"] = 1;
			$res = zjmfCurl($product["zjmf_api_id"], "/dcim/cancel_task", $post_data);
		} else {
			$this->setUrl($product["serverid"]);
			if ($this->error) {
				$result["status"] = 400;
				if ($this->is_admin) {
					$result["msg"] = "该产品未选择接口";
				} else {
					$result["msg"] = "取消失败";
				}
				return $result;
			}
			if ($product["config_option1"] == "rent") {
				$post_data["id"] = $product["dcimid"];
				$res = $this->post("cancellReinstallByServer", $post_data);
			} else {
				$result["status"] = 400;
				$result["msg"] = "产品为机柜租用，不支持该操作";
				return $result;
			}
		}
		$task_type = $res["task_type"];
		$des = ["重装系统", "救援系统", "重置密码", "获取硬件信息"];
		$description = sprintf("取消%s成功 - Host ID:%d", $des[$task_type], $id);
		if ($res["status"] == "success") {
			$result["status"] = 200;
			$result["msg"] = "取消成功";
			$result["task_type"] = $res["task_type"];
			if (!$this->is_admin && $product["reinstall_times"] > 0) {
				$reinstall_info = json_decode($product["reinstall_info"], true);
				$reinstall_info["num"] -= 1;
				if ($reinstall_info["num"] < 0) {
					$reinstall_info["num"] = 0;
				}
				$show_last_act_message = \think\facade\Cache::get("show_last_act_message_" . $id);
				$update["reinstall_info"] = json_encode($reinstall_info);
				if (is_numeric($show_last_act_message)) {
					$update["show_last_act_message"] = $show_last_act_message;
				}
				\think\Db::name("host")->where("id", $id)->update($update);
			}
		} elseif ($res["status"] == 200) {
			$result["status"] = 200;
			$result["msg"] = "取消成功";
			$result["task_type"] = $res["task_type"];
		} else {
			$result["status"] = 400;
			$result["msg"] = $res["msg"];
			if ($this->is_admin) {
				$result["msg"] = $this->log_prefix_error . $result["msg"];
				$description = $this->log_prefix_error . sprintf("取消%s失败,原因:%s - Host ID:%d", $des[$task_type], $res["msg"], $id);
			} else {
				$description = sprintf("取消%s失败 - Host ID:%d", $des[$task_type], $id);
			}
		}
		active_log_final($description, $product["uid"], 2, $id);
		return $result;
	}
	public function reinstallStatus($id)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.show_last_act_message,a.domainstatus,a.uid,b.config_option1,b.api_type,b.zjmf_api_id")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcim")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if (empty($product["dcimid"])) {
			$result["status"] = 400;
			$result["msg"] = "服务器ID错误";
			return $result;
		}
		if ($product["api_type"] == "zjmf_api") {
			$post_data["id"] = $product["dcimid"];
			$post_data["is_api"] = 1;
			$result = zjmfCurl($product["zjmf_api_id"], "/dcim/resintall_status", $post_data, 30, "GET");
		} else {
			$this->setUrl($product["serverid"]);
			if ($this->error) {
				$result["status"] = 400;
				if ($this->is_admin) {
					$result["msg"] = "该产品未选择接口";
				} else {
					$result["msg"] = "获取状态失败";
				}
				return $result;
			}
			if ($product["config_option1"] == "rent") {
				$post_data["id"] = $product["dcimid"];
				$post_data["hostid"] = $id;
				$res = $this->post("getReinstallStatus", $post_data);
				if ($res["status"] == "success") {
					if (!empty($res["data"])) {
						if ($res["data"]["windows_finish"]) {
							$result["status"] = 200;
							$result["data"]["last_result"] = ["act" => "重装系统", "status" => 1, "msg" => "请稍候尝试远程桌面连接"];
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
								$part_info = ["全盘格式化", "第一分区格式化"];
								$span = $res["data"]["name"] . ",安装磁盘：disk" . $res["data"]["disk"] . ",分区类型:" . $part_info[$res["data"]["part_type"]];
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
							$result["data"]["hostid"] = $id;
							$result["data"]["task_type"] = $res["data"]["task_type"];
							$result["data"]["reinstall_msg"] = $span;
							$result["data"]["step"] = $res["data"]["step"];
						}
					} else {
						$result["status"] = 200;
						$result["data"] = [];
						if (isset($res["last_result"]) && $product["show_last_act_message"] == 1 && ($res["last_result"]["type"] == 1 || $res["last_result"]["type"] == 2)) {
							if ($res["last_result"]["task_type"] == 3) {
								$info = "获取硬件信息";
							} elseif ($res["last_result"]["task_type"] == 2) {
								$info = "重置密码";
							} elseif ($res["last_result"]["task_type"] == 1) {
								$info = "救援系统";
							} else {
								$info = "重装系统";
							}
							$msg = "";
							if ($res["last_result"]["type"] == 1) {
								$msg = "成功";
							} else {
								$msg = "失败";
							}
							$result["data"]["last_result"] = ["act" => $info, "status" => $res["last_result"]["type"], "msg" => $msg];
						}
					}
				} else {
					$result["status"] = 400;
					$result["msg"] = $res["msg"];
				}
			} else {
				$result["status"] = 400;
				$result["msg"] = "产品为机柜租用，不支持该操作";
			}
		}
		return $result;
	}
	public function detail($id)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.config_option1,b.api_type,b.zjmf_api_id,b.id productid")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcim")->where("a.id", $id)->find();
		if ($product["api_type"] == "whmcs") {
			$dcimid = \think\Db::name("customfieldsvalues")->alias("a")->leftJoin("customfields b", "a.fieldid=b.id")->where("a.relid", $id)->where("b.type", "product")->where("b.relid", $product["productid"])->where("b.fieldname", "hostid")->value("value");
			$product["dcimid"] = $dcimid;
		}
		if (empty($product) || empty($product["dcimid"])) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if ($product["api_type"] == "zjmf_api") {
			$post_data["id"] = $product["dcimid"];
			$post_data["is_api"] = 1;
			$result = zjmfCurl($product["zjmf_api_id"], "/dcim/detail", $post_data, 30, "GET");
		} elseif ($product["api_type"] == "whmcs") {
			$up_id = \think\Db::name("customfieldsvalues")->alias("a")->leftJoin("customfields b", "a.fieldid=b.id")->where("a.relid", $id)->where("b.type", "product")->where("b.relid", $product["productid"])->where("b.fieldname", "hostid")->value("value");
			$api = \think\Db::name("zjmf_finance_api")->where("id", $product["zjmf_api_id"])->find();
			$url_data = ["apiname" => $api["username"], "apikey" => aesPasswordDecode($api["password"])];
			$url = $api["hostname"] . "/" . "modules/addons/idcsmart_api/api.php?action=/v1/hosts/{$up_id}";
			$res = commonCurl($url, $url_data);
			if ($res["status"] == 200) {
				$data["switch"] = [];
				foreach ($res["data"]["switch"] as $v) {
					$data["switch"][] = ["switch_id" => $v["id"], "name" => $v["switch_num_name"]];
				}
			} else {
				$data = [];
			}
			$result = ["status" => 200, "msg" => "请求成功", "data" => $data];
		} else {
			$this->setUrl($product["serverid"]);
			if ($this->error) {
				$result["status"] = 400;
				if ($this->is_admin) {
					$result["msg"] = "该产品未选择接口";
				} else {
					$result["msg"] = "获取失败";
				}
				return $result;
			}
			if ($product["config_option1"] == "rent") {
				$post_data["id"] = $product["dcimid"];
				$post_data["hostid"] = $id;
				$res = $this->post("serverDetailed", $post_data, 10);
				if ($res["status"] == "success") {
					$result["status"] = 200;
					$result["data"]["switch"] = [];
					foreach ($res["switch"] as $v) {
						$result["data"]["switch"][] = ["switch_id" => $v["id"], "name" => $v["switch_num_name"]];
					}
				} else {
					$result["status"] = 400;
					$result["msg"] = "获取失败";
				}
			} else {
				$result["status"] = 400;
				$result["msg"] = "产品为机柜租用，不支持该操作";
			}
		}
		return $result;
	}
	public function sales($page, $limit, $group = "", $status = 1, $search = "")
	{
		if ($this->error) {
			return ["status" => "error", "msg" => "该产品未选择接口"];
		}
		$system_token = configuration("system_token") ?: "";
		$postfields["search"] = "highgrade";
		$postfields["listpages"] = $limit;
		$postfields["offset"] = $page - 1;
		$postfields["sales"] = "all";
		if (!in_array($status, [1, 2, 3, 4, 5, 6])) {
			$postfields["status"] = [1, 2, 3, 4, 5, 6];
		} else {
			$postfields["status"] = [$status];
		}
		$postfields["group_id"] = [];
		if (!empty($group)) {
			$postfields["group_id"][] = $group;
		}
		if (!empty($search)) {
			$postfields["ip"] = $search;
		}
		$res = $this->post("overview", $postfields);
		if ($res["status"] == "success") {
			$result["status"] = 200;
			$result["data"] = [];
			foreach ($res["listing"] as $v) {
				$one = ["id" => $v["id"], "wltag" => $v["wltag"], "typename" => $v["typename"] ?? "", "group_name" => $v["group_name"] ?? "", "mainip" => $v["zhuip"], "ip_num" => count($v["ip"]), "ip" => $v["ip"] ?: [], "in_bw" => $v["out_bw"] ?? "", "out_bw" => $v["in_bw"] ?? "", "remarks" => $v["remarks"], "status" => $v["status"], "email" => $v["email"] ?? "", "hostid" => $v["productid"], "uid" => $v["email"] ?? "", "token" => $v["token"] ?? ""];
				if (!empty($v["token"]) || $v["token"] == $system_token) {
					$one["self"] = true;
				} else {
					$one["self"] = false;
				}
				if ($v["type"] == 1 || $v["type"] == 9) {
					$one["type"] = "rent";
				} else {
					$one["type"] = "trust";
				}
				$cpu = [];
				if ($v["cpu"]["num"] > 0) {
					$cpu[] = $v["cpu"]["name"] . "x" . $v["cpu"]["num"];
				}
				$ram = [];
				foreach ($v["ram"] as $vv) {
					if ($vv["num"] > 0) {
						$ram[] = $vv["name"] . "x" . $vv["num"];
					}
				}
				$disk = [];
				foreach ($v["disk"] as $vv) {
					if ($vv["num"] > 0) {
						$disk[] = $vv["name"] . "x" . $vv["num"];
					}
				}
				if (!empty($v["cpu_info"])) {
					$one["cpu"] = $v["cpu_info"];
				} else {
					$one["cpu"] = $cpu[0] ?? "";
				}
				if (!empty($v["mem_info"])) {
					$one["ram"] = $v["mem_info"][0];
				} else {
					$one["ram"] = $ram[0] ?? "";
				}
				if (!empty($v["disk_info"])) {
					$one["disk"] = $v["disk_info"][0];
					foreach ($v["disk_info"] as $kk => $vv) {
						$v["disk_info"][$kk] = $vv . str_replace("&nbsp;", "", $v["disk_sn_info"][$kk]);
					}
				} else {
					$one["disk"] = $disk[0] ?? "";
				}
				$one["cpu_detail"]["assign"] = $cpu;
				$one["cpu_detail"]["real"] = [$v["cpu_info"]] ?: [];
				$one["ram_detail"]["assign"] = $ram;
				$one["ram_detail"]["real"] = $v["mem_info"] ?: [];
				$one["disk_detail"]["assign"] = $disk;
				$one["disk_detail"]["real"] = $v["disk_info"] ?: [];
				$one["dcim_url"] = $this->url . "/index.php?m=server&a=detailed&id=" . $v["id"];
				$result["data"]["list"][] = $one;
			}
			$result["data"]["count"] = \intval($res["sum"]);
			$result["data"]["limit"] = $res["listpages"];
			$result["data"]["page"] = $page;
			$result["data"]["max_page"] = ceil($res["sum"] / $res["listpages"]);
			$result["data"]["server_group"] = $res["server_group"] ?? [];
		} else {
			$result["status"] = 400;
			$result["msg"] = $res["msg"] ?: "获取失败";
		}
		return $result;
	}
	public function free($id)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,b.config_option1,a.uid")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcim")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		$this->setUrl($product["serverid"]);
		if ($this->error) {
			$result["status"] = 400;
			$result["msg"] = "该产品未选择接口";
			return $result;
		}
		if (empty($product["dcimid"])) {
			$result["status"] = 400;
			$result["msg"] = "服务器ID错误";
			return $result;
		}
		if ($product["config_option1"] == "rent") {
			$post_data["id"] = $product["dcimid"];
			$post_data["status"] = 1;
			$post_data["client_id"] = 0;
			$post_data["starttime"] = "";
			$post_data["token"] = "";
			$res = $this->post("editServerSales", $post_data);
			if ($res["status"] == "success") {
				$update = ["dcimid" => 0, "reinstall_info" => "", "show_last_act_message" => 1, "dedicatedip" => "", "assignedips" => "", "username" => "", "password" => "", "port" => 0, "dcim_area" => 0];
				\think\Db::name("host")->where("id", $id)->update($update);
				$result["status"] = 200;
				$result["msg"] = "删除成功";
				$description = sprintf("删除设备成功 - Host ID:%d, DCIM ID: %d", $id, $product["dcimid"]);
				pushHostInfo($id);
			} else {
				$result["status"] = 400;
				$result["msg"] = "删除失败";
				$result["msg"] = $this->log_prefix_error . $result["msg"];
				$description = $this->log_prefix_error . sprintf("删除设备失败,原因:%s - Host ID:%d", $res["msg"], $id);
			}
			active_log_final($description, $product["uid"], 2, $id);
		} else {
			$result["status"] = 400;
			$result["msg"] = "产品为机柜租用，不支持该操作";
		}
		return $result;
	}
	public function refreshPowerStatus($id)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.reinstall_info,b.config_option1,a.uid,b.api_type,b.zjmf_api_id,c.reinstall_times,b.id productid")->leftJoin("products b", "a.productid=b.id")->leftJoin("dcim_servers c", "a.serverid=c.serverid")->where("b.type", "dcim")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if ($product["api_type"] == "whmcs") {
			$dcimid = \think\Db::name("customfieldsvalues")->alias("a")->leftJoin("customfields b", "a.fieldid=b.id")->where("a.relid", $id)->where("b.type", "product")->where("b.relid", $product["productid"])->where("b.fieldname", "hostid")->value("value");
			$product["dcimid"] = $dcimid;
		}
		if (empty($product["dcimid"])) {
			$result["status"] = 400;
			$result["msg"] = "服务器ID错误";
			return $result;
		}
		if ($product["api_type"] == "zjmf_api") {
			$result = zjmfCurl($product["zjmf_api_id"], "/dcim/refresh_power_status", ["id" => $product["dcimid"], "is_api" => 1]);
		} elseif ($product["api_type"] == "whmcs") {
			$res = whmcsCurlPost($id, "status", ["type" => "host"]);
			if ($res["status"] == 200) {
				$result["status"] = 200;
				if ($res["power"] == "on") {
					$result["data"]["status"] = "on";
					$result["data"]["des"] = "开机";
				} elseif ($res["power"] == "off") {
					$result["data"]["status"] = "off";
					$result["data"]["des"] = "关机";
				} elseif ($res["power"] == "suspend") {
					$result["data"]["status"] = "suspend";
					$result["data"]["des"] = "暂停";
				} elseif ($res["power"] == "wait_reboot") {
					$result["data"]["status"] = "waiting";
					$result["data"]["des"] = "等待重启";
				} elseif ($res["power"] == "task") {
					$result["data"]["status"] = "process";
					$result["data"]["des"] = $res["task_name"] . "中";
				} elseif ($res["power"] == "paused") {
					$result["data"]["status"] = "paused";
					$result["data"]["des"] = "挂起";
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
		} else {
			$this->setUrl($product["serverid"]);
			if ($this->error) {
				$result["status"] = 400;
				if ($this->is_admin) {
					$result["msg"] = "该产品未选择接口";
				} else {
					$result["msg"] = "刷新失败";
				}
				return $result;
			}
			if ($product["config_option1"] == "rent") {
				$post_data["id"] = $product["dcimid"];
				$post_data["hostid"] = $id;
				$res = $this->post("ipmiPowerSync", $post_data, 20);
				if ($res["status"] == "success") {
					$result["status"] = 200;
					$result["data"]["msg"] = $res["power_msg"] ?? "";
					if ($res["msg"] == "on") {
						$result["data"]["power"] = "on";
					} elseif ($res["msg"] == "off") {
						$result["data"]["power"] = "off";
					} else {
						$result["data"]["power"] = "error";
					}
				} else {
					if ($res["msg"] == "nonsupport") {
						$result["status"] = 200;
						$result["data"]["power"] = "not_support";
						$result["data"]["msg"] = $res["power_msg"] ?? "";
					} else {
						$result["status"] = 400;
						$result["msg"] = $res["power_msg"] ?: $res["msg"];
					}
				}
			} elseif ($product["config_option1"] == "bms") {
				$res = $this->bmsCurl("/bms/source/" . $product["dcimid"] . "/status", [], "GET", 20);
				if ($res["code"] == 200) {
					$result["status"] = 200;
					if ($res["status"] == "on") {
						$result["data"]["status"] = "on";
						$result["data"]["des"] = "开机";
					} elseif ($res["status"] == "off") {
						$result["data"]["status"] = "off";
						$result["data"]["des"] = "关机";
					} elseif ($res["status"] == "suspend") {
						$result["data"]["status"] = "suspend";
						$result["data"]["des"] = "暂停";
					} elseif ($res["status"] == "wait_reboot") {
						$result["data"]["status"] = "waiting";
						$result["data"]["des"] = "等待重启";
					} elseif ($res["status"] == "task") {
						$result["data"]["status"] = "process";
						$result["data"]["des"] = $res["task_name"] . "中";
					} elseif ($res["status"] == "paused") {
						$result["data"]["status"] = "paused";
						$result["data"]["des"] = "挂起";
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
			} else {
				$result["status"] = 400;
				$result["msg"] = "产品为机柜租用，不支持该操作";
			}
		}
		return $result;
	}
	public function getTrafficUsage($id, $start, $end)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,b.config_option1,b.api_type,b.zjmf_api_id,b.id productid")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcim")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if (empty($product["dcimid"])) {
			$result["status"] = 400;
			$result["msg"] = "服务器ID错误";
			return $result;
		}
		if ($product["api_type"] == "zjmf_api") {
			$post_data["id"] = $product["dcimid"];
			$post_data["start"] = $start;
			$post_data["end"] = $end;
			$post_data["is_api"] = 1;
			$result = zjmfCurl($product["zjmf_api_id"], "/dcim/traffic_usage", $post_data, 30, "GET");
		} else {
			$this->setUrl($product["serverid"]);
			if ($this->error) {
				$result["status"] = 400;
				if ($this->is_admin) {
					$result["msg"] = "该产品未选择接口";
				} else {
					$result["msg"] = "获取用量信息失败";
				}
				return $result;
			}
			if ($product["config_option1"] == "rent") {
				$post_data["id"] = $product["dcimid"];
				$post_data["hostid"] = $id;
				$post_data["start"] = $start;
				$post_data["end"] = $end;
				$res = $this->post("trafficUsage", $post_data);
				if ($res["status"] == "success") {
					if (empty($res["decimal"])) {
						$res["decimal"] = 1000;
					}
					$x = array_keys($res["data"]);
					$data = [];
					foreach ($x as $v) {
						$data[] = ["time" => substr($v, 5), "value" => round($res["data"][$v][$res["direction"]] / $res["decimal"], 2)];
					}
					$result["status"] = 200;
					$result["data"] = $data;
				} else {
					$result["status"] = 400;
					$result["msg"] = $res["msg"];
				}
			} elseif ($product["config_option1"] == "bms") {
				$result["status"] = 400;
				$result["msg"] = "产品为裸金属，不支持该操作";
			} else {
				$result["status"] = 400;
				$result["msg"] = "产品为机柜租用，不支持该操作";
			}
		}
		return $result;
	}
	public function suspend($id)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,b.config_option1,a.uid")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcim")->where("a.id", $id)->find();
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
			if ($this->is_admin) {
				$result["msg"] = "该产品未选择接口";
			} else {
				$result["msg"] = "暂停失败";
			}
			return $result;
		}
		if ($product["config_option1"] == "rent") {
			$post_data["id"] = $product["dcimid"];
			$post_data["hostid"] = $id;
			$res = $this->post("ipmiSuspend", $post_data);
			if ($res["status"] == "success") {
				$result["status"] = 200;
				$result["msg"] = "暂停成功";
			} else {
				$result["status"] = 400;
				$result["msg"] = $res["msg"];
			}
		} elseif ($product["config_option1"] == "bms") {
			$res = $this->bmsCurl("/bms/source/" . $product["dcimid"] . "/suspend");
			if ($res["code"] == 200) {
				$result["status"] = 200;
				$result["msg"] = "暂停成功";
			} else {
				$result["status"] = 400;
				$result["msg"] = $res["msg"];
			}
		} else {
			$result["status"] = 400;
			$result["msg"] = "产品为机柜租用，不支持该操作";
		}
		if ($this->is_admin && $result["status"] != 200) {
			$result["msg"] = $this->log_prefix_error . $result["msg"];
		}
		return $result;
	}
	public function unsuspend($id)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,b.config_option1,a.uid")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcim")->where("a.id", $id)->find();
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
			if ($this->is_admin) {
				$result["msg"] = "该产品未选择接口";
			} else {
				$result["msg"] = "解除暂停失败";
			}
			return $result;
		}
		if ($product["config_option1"] == "rent") {
			$post_data["id"] = $product["dcimid"];
			$post_data["hostid"] = $id;
			$res = $this->post("ipmiUnsuspend", $post_data);
			if ($res["status"] == "success") {
				$result["status"] = 200;
				$result["msg"] = "解除暂停成功";
			} else {
				$result["status"] = 400;
				$result["msg"] = $res["msg"];
			}
		} elseif ($product["config_option1"] == "bms") {
			$res = $this->bmsCurl("/bms/source/" . $product["dcimid"] . "/unsuspend");
			if ($res["code"] == 200) {
				$result["status"] = 200;
				$result["msg"] = "解除暂停成功";
			} else {
				$result["status"] = 400;
				$result["msg"] = $res["msg"];
			}
		} else {
			$result["status"] = 400;
			$result["msg"] = "产品为机柜租用，不支持该操作";
		}
		if ($this->is_admin && $result["status"] != 200) {
			$result["msg"] = $this->log_prefix_error . $result["msg"];
		}
		return $result;
	}
	public function terminate($id)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,b.config_option1,a.uid")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcim")->where("a.id", $id)->find();
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
			if ($this->is_admin) {
				$result["msg"] = "该产品未选择接口";
			} else {
				$result["msg"] = "删除失败";
			}
			return $result;
		}
		if ($product["config_option1"] == "rent") {
			$post_data["id"] = $product["dcimid"];
			$post_data["hostid"] = $id;
			$res = $this->post("ipmiTerminate", $post_data, 60);
			if ($res["status"] == "success") {
				$result["status"] = 200;
				$result["msg"] = "删除成功";
			} else {
				$result["status"] = 400;
				$result["msg"] = $res["msg"];
			}
		} elseif ($product["config_option1"] == "bms") {
			$res = $this->bmsCurl("/bms/source/" . $product["dcimid"], [], "DELETE");
			if ($res["code"] == 200) {
				$result["status"] = 200;
				$result["msg"] = "删除成功";
			} else {
				$result["status"] = 400;
				$result["msg"] = $res["msg"];
			}
		} else {
			$result["status"] = 400;
			$result["msg"] = "产品为机柜租用，不支持该操作";
		}
		if ($this->is_admin && $result["status"] != 200) {
			$result["msg"] = $this->log_prefix_error . $result["msg"];
		}
		return $result;
	}
	public function getOs()
	{
		$result = $this->post("getAllMirrorOs", [], 30);
		if ($result["status"] == "success") {
			$data = $result["data"] ?? [];
		} else {
			$data = [];
		}
		return $data;
	}
	public function createApi($hostname = "", $check = true)
	{
		if (filter_var($hostname, FILTER_VALIDATE_IP)) {
			$ip = $hostname;
		} else {
			$ip = gethostbyname($hostname);
			if (!filter_var($ip, FILTER_VALIDATE_IP)) {
				$result["status"] = 400;
				$result["msg"] = "创建本地API失败";
				return $result;
			}
		}
		$exist = \think\Db::name("api")->where("is_auto", 1)->where("ip", $ip)->find();
		if (!empty($exist) && $check) {
			$result["status"] = 400;
			$result["msg"] = "已创建API";
			return $result;
		}
		$username = randStr(16);
		$password = substr(md5(randStr(16)), 0, 16);
		$token = \think\Db::name("configuration")->where("setting", "system_token")->value("value") ?? "";
		$postfields["api_username"] = $username;
		$postfields["api_password"] = $password;
		$postfields["token"] = $token;
		$postfields["url"] = request()->domain();
		$res = $this->post("createCwApi", $postfields, 30);
		if ($res["status"] == "success") {
			$insert = ["username" => $username, "password" => md5($password), "ip" => $ip, "create_time" => time(), "is_auto" => 1];
			if (!empty($exist)) {
				$r = \think\Db::name("api")->where("id", $exist["id"])->update($insert);
			} else {
				$r = \think\Db::name("api")->insert($insert);
			}
			if ($r) {
				$result["status"] = 200;
			} else {
				$result["status"] = 400;
			}
		} else {
			$result["status"] = 400;
			$result["msg"] = "远程创建失败";
		}
		return $result;
	}
	public function checkApi($hostname = "")
	{
		if (filter_var($hostname, FILTER_VALIDATE_IP)) {
			$ip = $hostname;
		} else {
			$ip = gethostbyname($hostname);
			if (!filter_var($ip, FILTER_VALIDATE_IP)) {
				$result["status"] = 400;
				$result["msg"] = "不能解析" . $hostname;
				return $result;
			}
		}
		$info = \think\Db::name("api")->where("is_auto", 1)->where("ip", $ip)->find();
		if (empty($info)) {
			$result["status"] = 400;
			$result["msg"] = "API未成功创建";
			return $result;
		}
		$token = \think\Db::name("configuration")->where("setting", "system_token")->value("value") ?? "";
		$postfields["api_username"] = $info["username"];
		$postfields["api_password"] = $info["password"];
		$postfields["token"] = $token;
		$postfields["url"] = request()->domain();
		$res = $this->post("checkCwApi", $postfields, 30);
		if ($res["status"] == "success") {
			$result["status"] = 200;
		} else {
			$result["status"] = 400;
			$result["msg"] = "远程创建失败";
		}
		return $result;
	}
	public function createAccount($hostid)
	{
		$mod = new \app\common\model\HostModel();
		$params = $mod->getProvisionParams($hostid);
		if ($params["type"] == "dcim") {
			if ($params["dcimid"] > 0) {
				$result["status"] = 400;
				$result["msg"] = "该产品已存在，不能重复开通";
				return $result;
			}
			if ($params["config_option1"] == "cabinet") {
				$result["status"] = 400;
				$result["msg"] = "产品为机柜租用，请直接将机柜ID输入到ipmi_id中";
			} elseif ($params["config_option1"] == "rent") {
				$this->setUrl($params["serverid"]);
				$dcim_server = \think\Db::name("dcim_servers")->where("serverid", $params["serverid"])->find();
				$dcim_server["auth"] = json_decode($dcim_server["auth"], true);
				$postfields["hostid"] = $hostid;
				$postfields["server_group"] = $params["configoptions"]["server_group"];
				$postfields["os"] = $params["configoptions"]["os"] ?: $params["dcim_os"];
				$postfields["user_id"] = $this->user_prefix . $params["uid"];
				$postfields["remote_user_id"] = $params["uid"];
				$postfields["token"] = configuration("system_token") ?? "";
				if ($dcim_server["auth"]["enable_ip_custom"] == "on") {
					$postfields["ip_customid"] = \intval($dcim_server["ip_customid"]);
				}
				$ip_group = $params["configoptions"]["ip_group"];
				$ip_num = $params["configoptions"]["ip_num"];
				if (is_numeric($ip_num)) {
					if (!empty($ip_group)) {
						$postfields["ip_num"][$ip_group] = $ip_num;
					} else {
						$postfields["ip_num"] = $ip_num;
					}
				} else {
					if ($ip_num == "NO_CHANGE") {
						$postfields["ip_num"] = $ip_num;
					} else {
						$ip_num = format_dcim_ipnum($ip_num);
						if ($ip_num === false) {
							$result["status"] = 400;
							$result["msg"] = "IP数量格式有误";
							return $result;
						}
						$postfields["ip_num"] = $ip_num;
					}
				}
				if (!empty($hosting["regdate"])) {
					$postfields["starttime"] = $hosting["regdate"] . " 00:00:00";
				}
				$bw = $params["configoptions"]["bw"];
				if ($bw == "NO_CHANGE") {
					$postfields["in_bw"] = "NO_CHANGE";
					$postfields["out_bw"] = "NO_CHANGE";
				} else {
					if (strpos($bw, ",") !== false) {
						$bw_arr = explode(",", $bw);
						$postfields["in_bw"] = $bw_arr[0];
						$postfields["out_bw"] = $bw_arr[1];
					} else {
						if (is_numeric($bw) && $bw >= 0) {
							$postfields["in_bw"] = $bw;
							$postfields["out_bw"] = $bw;
						} else {
							$postfields["in_bw"] = "";
							$postfields["out_bw"] = "";
						}
					}
				}
				$postfields["limit_traffic"] = $params["configoptions"]["bwt"] ?? "";
				if (empty($postfields["server_group"])) {
					$result["status"] = 400;
					$result["msg"] = "请选择服务器分组！";
					return $result;
				}
				if (empty($postfields["ip_num"])) {
					$result["status"] = 400;
					$result["msg"] = "请选择IP数量";
					return $result;
				}
				if (empty($postfields["in_bw"]) || empty($postfields["out_bw"])) {
					$result["status"] = 400;
					$result["msg"] = "请选择带宽";
					return $result;
				}
				if (empty($postfields["os"])) {
					$result["status"] = 400;
					$result["msg"] = "请选择操作系统";
					return $result;
				}
				if (is_numeric($params["customfields"]["port"]) || is_numeric($params["customfields"]["端口"])) {
					$postfields["port"] = $params["customfields"]["port"] ?: $params["customfields"]["端口"];
				}
				$res = $this->post("ipmiCreate", $postfields, 180);
				if ($res["status"] == "success") {
					$data = $res["data"];
					$update["dcimid"] = $data["id"];
					$update["dedicatedip"] = $data["zhuip"] ?: "";
					if ($dcim_server["auth"]["enable_ip_custom"] == "on") {
						$update["assignedips"] = str_replace("\r\n", ",", str_replace(",", "，", $data["ips"]));
					} else {
						$data["ips"] = explode("\r\n", $data["ips"]);
						foreach ($data["ips"] as $k => $v) {
							if ($v == $data["zhuip"]) {
								unset($data["ips"][$k]);
							} else {
								$data["ips"][$k] = str_replace(",", "，", $v);
							}
						}
						$update["assignedips"] = implode(",", $data["ips"]);
					}
					$update["domainstatus"] = "Active";
					$update["username"] = $data["username"];
					$update["password"] = cmf_encrypt($data["password"]);
					$update["port"] = intval($data["port"]);
					$update["dcim_area"] = intval($data["house"]);
					$update["bwlimit"] = intval($data["limit_traffic"]);
					if (is_numeric($data["decimal"]) && $data["decimal"] > 0) {
						$update["bwlimit"] = \intval($update["bwlimit"] / $data["decimal"]);
					}
					$update["bwusage"] = 0;
					$update["lastupdate"] = time();
					$product_host_rule = json_decode($params["host"], true);
					if (empty($product_host_rule) || $product_host_rule["show"] != 1) {
						$update["domain"] = $data["wltag"] ?: "";
					}
					\think\Db::name("host")->where("id", $hostid)->update($update);
					$this->savePanelPass($hostid, $params["productid"], $data["ippassword"]);
					$msg = "开通成功";
					if (isset($res["ip_error"])) {
						$msg .= ", IP分配失败,原因: " . $res["ip_error"];
					}
					if (isset($res["in_bw_error"])) {
						$msg .= ", 进带宽设置失败,原因: " . $res["in_bw_error"];
					}
					if (isset($res["out_bw_error"])) {
						$msg .= ", 出带宽设置失败,原因: " . $res["out_bw_error"];
					}
					$description = sprintf("开通成功,原因:%s - Host ID:%d", $msg, $hostid);
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
			} elseif ($params["config_option1"] == "bms") {
				$post_data = [];
				$post_data["name"] = $params["domain"];
				$post_data["type"] = 2;
				$post_data["size"] = $params["configoptions"]["system_disk_size"] ?: 50;
				$post_data["name3"] = $params["user_info"]["email"] ?: $params["user_info"]["phonenumber"];
				$post_data["group_id"] = \intval($params["configoptions"]["group_id"]);
				if (isset($params["configoptions"]["node"])) {
					$post_data["lc_id"] = \intval($params["configoptions"]["node"]);
				}
				$post_data["os_id"] = \intval($params["configoptions"]["os"]);
				$post_data["password"] = $params["password"];
				if (empty($post_data["password"])) {
					$post_data["password"] = "abcdefghijklmnopqrstuvwxyz"[mt_rand(0, 23)];
					$post_data["password"] .= "ABCDEFGHIJKLMNOPQRSTUVWXYZ"[mt_rand(0, 23)];
					$post_data["password"] .= mt_rand(0, 9);
					$post_data["password"] .= randStr(5);
				}
				$post_data["ip_num"] = \intval($params["configoptions"]["ip_num"]);
				$post_data["ip_group_id"] = $params["configoptions"]["ip_group"];
				if ($params["configoptions"]["data_disk_size"] > 0) {
					$post_data["data_size"] = \intval($params["configoptions"]["data_disk_size"]);
				}
				if ($params["configoptions"]["ceph_id"] > 0) {
					$post_data["ceph_id"] = \intval($params["configoptions"]["ceph_id"]);
				}
				$post_data["backup_num"] = isset($params["configoptions"]["backup_num"]) ? $params["configoptions"]["backup_num"] : 2;
				$post_data["snap_num"] = isset($params["configoptions"]["snap_num"]) ? $params["configoptions"]["snap_num"] : 2;
				$this->setUrl($params["serverid"]);
				$res = $this->bmsCurl("/bms/source", $post_data);
				if ($res["code"] == 200) {
					$dcimid = $res["data"];
					$update = [];
					$update["dcimid"] = $dcimid;
					$update["domainstatus"] = "Active";
					$detail = $this->bmsCurl("/bms/source/" . $dcimid, [], "GET", 20);
					if ($detail["code"] == 200) {
						$update["dedicatedip"] = $detail["data"]["ip"] ?: "";
						if (!empty($detail["data"]["float_ip"])) {
							$update["assignedips"] = implode(",", array_column($detail["data"]["float_ip"], "ipaddress"));
						}
						$update["domain"] = $detail["data"]["name"];
						$update["username"] = $detail["data"]["username"];
						$update["password"] = cmf_encrypt($detail["data"]["password"]);
					}
					\think\Db::name("host")->where("id", $hostid)->update($update);
					$result["status"] = 200;
					$result["msg"] = $res["msg"];
				} else {
					$result["status"] = 400;
					$result["msg"] = $res["msg"] ?: "开通失败";
				}
			} else {
				$result["status"] = 400;
				$result["msg"] = "不支持的类型";
			}
		} else {
			$result["status"] = 400;
			$result["msg"] = "不支持的类型";
		}
		if ($this->is_admin && $result["status"] != 200) {
			$result["msg"] = $this->log_prefix_error . $result["msg"];
		}
		return $result;
	}
	public function upgrade($id)
	{
		$mod = new \app\common\model\HostModel();
		$params = $mod->getProvisionParams($id);
		if (empty($params["dcimid"])) {
			$result["status"] = "error";
			$result["msg"] = "数据获取失败";
			return $result;
		}
		$this->setUrl($params["serverid"]);
		if ($this->error) {
			$result["status"] = "error";
			$result["msg"] = "接口错误";
			return $result;
		}
		if ($params["config_option1"] == "rent") {
			if (isset($params["configoptions_upgrade"]["bwt"])) {
				$post_data["id"] = $params["dcimid"];
				$post_data["traffic"] = $params["configoptions"]["bwt"];
				$this->post("modifyServerTraffic", $post_data, 20);
				$res = $this->serverDetailflowData($id, $params["dcimid"]);
				if ($res["status"] == "success") {
					$bill_type = \think\Db::name("dcim_servers")->field("bill_type")->where("serverid", $params["serverid"])->find();
					if (!empty($bill_type)) {
						$bill_type = $bill_type["bill_type"] ?: "month";
						if ($res["limit"] != 0) {
							$res["limit"] = $res["limit"] + $res["temp_traffic"];
						}
						$update["bwlimit"] = \intval($res["limit"]);
						$update["bwusage"] = round(str_replace("GB", "", $res["data"][$bill_type][$res["type"]]), 2);
						$update["lastupdate"] = time();
						\think\Db::name("host")->where("id", $id)->update($update);
						$data = $res["data"];
						$now_percent = str_replace("%", "", $data[$bill_type]["used_percent"]);
						if ($params["domainstatus"] == "Active" && $now_percent >= 100) {
							$this->overTraffic($id, $params["dcimid"], $bill_type);
						}
						$reason = explode("-", $params["suspendreason"])[0];
						if ($params["domainstatus"] == "Suspended" && ($reason == "用量超额" || $reason == "flow") && $now_percent < 100) {
							$this->post("serverResume", ["id" => $params["dcimid"]], 5);
						}
					}
				}
			}
			if (isset($params["configoptions_upgrade"]["bw"])) {
				$bw = $params["configoptions"]["bw"];
				if ($bw != "NO_CHANGE") {
					if (strpos($bw, ",") !== false) {
						$bw_arr = explode(",", $bw);
						$this->post("inBwSetting", ["num" => $bw_arr[1], "server_id" => $params["dcimid"]], 5);
						$this->post("outBwSetting", ["num" => $bw_arr[0], "server_id" => $params["dcimid"]], 5);
					} else {
						if (is_numeric($bw) && $bw >= 0) {
							$this->post("inBwSetting", ["num" => $bw, "server_id" => $params["dcimid"]], 5);
							$this->post("outBwSetting", ["num" => $bw, "server_id" => $params["dcimid"]], 5);
						}
					}
				}
			}
			if (isset($params["configoptions_upgrade"]["ip_num"])) {
				$post_data = [];
				$ip_group = $params["configoptions"]["ip_group"];
				$ip_num = $params["configoptions"]["ip_num"];
				if (is_numeric($ip_num)) {
					if (!empty($ip_group)) {
						$post_data["ip_num"][$ip_group] = $ip_num;
					} else {
						$post_data["ip_num"] = $ip_num;
					}
				} else {
					if ($ip_num == "NO_CHANGE") {
						$post_data["ip_num"] = $ip_num;
					} else {
						$ip_num = format_dcim_ipnum($ip_num);
						if ($ip_num === false) {
						} else {
							$post_data["ip_num"] = $ip_num;
						}
					}
				}
				if (!empty($post_data)) {
					$dcim_server = \think\Db::name("dcim_servers")->where("serverid", $params["serverid"])->find();
					$dcim_server["auth"] = json_decode($dcim_server["auth"], true);
					$this->post("setServerIp", ["ip_num" => $post_data["ip_num"], "id" => $params["dcimid"]], 20);
					if ($dcim_server["auth"]["enable_ip_custom"] == "on") {
						$res = $this->post("getServerIp&id=" . $params["dcimid"], ["ip_customid" => $dcim_server["ip_customid"], "custom_type" => "api"], 20);
					} else {
						$res = $this->post("getServerIp&id=" . $params["dcimid"], [], 20);
					}
					if ($res["status"] == "success") {
						$update = [];
						$assignedips = [];
						if (!empty($res["data"])) {
							if ($dcim_server["auth"]["enable_ip_custom"] == "on") {
								$update["dedicatedip"] = $res["data"][0]["ipaddress"] ?: "";
								$update["assignedips"] = [];
								foreach ($res["data"] as $v) {
									$update["assignedips"][] = !empty($v["custom_field_value"]) ? $v["ipaddress"] . "(" . str_replace(",", "，", $v["custom_field_value"]) . ")" : $v["ipaddress"];
								}
								$update["assignedips"] = implode(",", $update["assignedips"]);
							} else {
								$dedicatedip = array_shift($res["data"]);
								$update["dedicatedip"] = $dedicatedip["ipaddress"] ?: "";
								$update["assignedips"] = implode(",", array_column($res["data"], "ipaddress")) ?: "";
							}
						} else {
							$update["dedicatedip"] = "";
							$update["assignedips"] = "";
						}
						\think\Db::name("host")->where("id", $id)->update($update);
					}
				}
			}
		} elseif ($params["config_option1"] == "bms") {
			$post_data = [];
			if (isset($params["configoptions_upgrade"]["snap_num"])) {
				$post_data["snap_num"] = $params["configoptions"]["snap_num"];
			}
			if (isset($params["configoptions_upgrade"]["backup_num"])) {
				$post_data["backup_num"] = $params["configoptions"]["backup_num"];
			}
			if (!empty($post_data)) {
				$this->bmsCurl("/bms/source/" . $params["dcimid"], $post_data, "PUT", 5);
			}
			if (isset($params["configoptions_upgrade"]["ip_num"])) {
				$this->bmsCurl("/bms/source/" . $params["dcimid"] . "/ip", ["num" => $params["configoptions"]["ip_num"]], "PUT", 10);
			}
			if (isset($params["configoptions_upgrade"]["data_disk_size"])) {
				$disk_id = 0;
				$res = $this->bmsCurl("/bms/source/" . $params["dcimid"], [], "GET", 20);
				if ($res["code"] == 200) {
					$store_id = 0;
					foreach ($res["data"]["disk"] as $v) {
						if ($v["type"] == 0) {
							$store_id = \intval($v["ceph_id"]);
						}
						if ($v["type"] == 1) {
							$disk_id = $v["id"];
							break;
						}
					}
					if (!empty($disk_id)) {
						$this->bmsCurl("/bms/disk/" . $disk_id, ["size" => $params["configoptions"]["data_disk_size"]], "PUT", 20);
					} else {
						if (!empty($store_id)) {
							$this->bmsCurl("/bms/source/" . $params["dcimid"] . "/disk", ["size" => $params["configoptions"]["data_disk_size"], "ceph_id" => $store_id]);
						}
					}
				}
			}
			$detail = $this->bmsCurl("/bms/source/" . $params["dcimid"], [], "GET", 20);
			if ($detail["code"] == 200) {
				$update = [];
				$update["dedicatedip"] = $detail["data"]["ip"] ?: "";
				if (!empty($detail["data"]["float_ip"])) {
					$update["assignedips"] = implode(",", array_column($detail["data"]["float_ip"], "ipaddress"));
				}
				$update["domain"] = $detail["data"]["name"];
				$update["username"] = $detail["data"]["username"];
				$update["password"] = cmf_encrypt($detail["data"]["password"]);
				\think\Db::name("host")->where("id", $id)->update($update);
				if ($detail["data"]["status"] == "wait_reboot") {
					$this->bmsCurl("/bms/source/" . $params["dcimid"] . "/restart", [], "POST", 10);
				}
			}
		}
		$result["status"] = "success";
		return $result;
	}
	public function assignServer($id, $dcimid)
	{
		if (empty($dcimid)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.regdate,a.dcimid,b.config_option1,a.uid,a.nextduedate,b.host,b.api_type")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcim")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if ($product["api_type"] != "normal") {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if (!empty($product["dcimid"])) {
			$result["status"] = 200;
			$result["msg"] = "该产品不能重复分配";
			return $result;
		}
		$this->setUrl($product["serverid"]);
		if ($this->error) {
			$result["status"] = 400;
			$result["msg"] = "该产品未选择接口";
			return $result;
		}
		$is_assign = \think\Db::name("host")->alias("h")->join("products p", "h.productid=p.id")->where("h.serverid", $product["serverid"])->where("h.dcimid", $dcimid)->where("p.api_type", "normal")->find();
		if (!empty($is_assign)) {
			$result["status"] = 400;
			$result["msg"] = "DCIM ID:" . $dcimid . "已分配给产品" . $is_assign["domain"] . ",请去确认产品状态";
			return $result;
		}
		$cache_key = "HOST_DEFAULT_ACTION_CREATE_" . $id;
		if (cache($cache_key)) {
			$result["status"] = 406;
			$result["msg"] = "产品正在开通中";
			return $result;
		}
		cache($cache_key, 1, 300);
		$description = "";
		if ($product["config_option1"] == "rent") {
			$dcim_server = \think\Db::name("dcim_servers")->where("serverid", $product["serverid"])->find();
			$dcim_server["auth"] = json_decode($dcim_server["auth"], true);
			$post_data["id"] = $dcimid;
			$post_data["hostid"] = $id;
			$post_data["user_id"] = $this->user_prefix . $product["uid"];
			$post_data["remote_user_id"] = $product["uid"];
			$post_data["domainstatus"] = "Active";
			if ($product["nextduedate"] > 0) {
				$post_data["expiretime"] = date("Y-m-d H:i:s", $product["nextduedate"]);
			}
			$post_data["starttime"] = date("Y-m-d H:i:s", $product["regdate"]);
			$post_data["token"] = configuration("system_token") ?? "";
			if ($dcim_server["auth"]["enable_ip_custom"] == "on") {
				$post_data["ip_customid"] = \intval($dcim_server["ip_customid"]);
			}
			$res = $this->post("ipmiSync", $post_data);
			if ($res["status"] == "success") {
				$mod = new \app\common\model\HostModel();
				$params = $mod->getProvisionParams($id);
				$update["dcimid"] = $dcimid;
				$update["dedicatedip"] = $res["zhuip"] ?: "";
				$ips = explode("\r\n", $res["ips"]);
				if ($dcim_server["auth"]["enable_ip_custom"] != "on") {
					foreach ($ips as $k => $v) {
						if (empty($v) || $v == $res["zhuip"]) {
							unset($ips[$k]);
						}
					}
				} else {
					foreach ($ips as $k => $v) {
						$ips[$k] = str_replace(",", "，", $v);
					}
				}
				$update["assignedips"] = implode(",", $ips);
				$update["domainstatus"] = "Active";
				$update["os"] = $res["os"] ?? "";
				$update["username"] = $res["username"];
				$update["password"] = cmf_encrypt($res["password"]);
				$update["port"] = $res["port"];
				if (!empty($res["os_id"])) {
					$update["dcim_os"] = $res["os_id"];
				}
				$update["dcim_area"] = intval($res["house"]);
				$update["bwlimit"] = intval($params["configoptions"]["bwt"]);
				$update["bwusage"] = 0;
				$update["lastupdate"] = time();
				$product_host_rule = json_decode($product["host"], true);
				if (empty($product_host_rule) || $product_host_rule["show"] != 1) {
					$update["domain"] = $res["wltag"] ?: "";
				}
				\think\Db::name("host")->where("id", $id)->update($update);
				$this->savePanelPass($id, $params["productid"], $res["ippassword"]);
				$result = [];
				$result["status"] = 200;
				$result["msg"] = "分配成功";
				$description .= sprintf("分配成功#Host ID:%s, DCIM ID: %d", $id, $dcimid);
				pushHostInfo($id);
			} else {
				$result["status"] = 400;
				$result["msg"] = $res["msg"] ?: "分配失败";
				$description .= sprintf("分配失败#Host ID:%s 失败原因:%s", $id, $res["msg"]);
				if ($this->is_admin) {
					$result["msg"] = $this->log_prefix_error . $result["msg"];
					$description = $this->log_prefix_error . $description;
				}
			}
			active_log_final($description, $product["uid"], 2, $id);
		} elseif ($product["config_option1"] == "bms") {
			$result["status"] = 400;
			$result["msg"] = "产品为裸金属，不支持该操作";
		} else {
			$result["status"] = 400;
			$result["msg"] = "产品为机柜租用，不支持该操作";
		}
		cache($cache_key, null);
		return $result;
	}
	public function resetFlow($hostid, $dcimid, $nextduedate = 0)
	{
		$postfields["id"] = $dcimid;
		$postfields["hostid"] = $hostid;
		$res = $this->post("resetServerFlow", $postfields);
		if ($res["status"] == "success") {
			\think\Db::name("dcim_percent")->where("hostid", $hostid)->update(["send_email" => 0]);
			$description = sprintf("流量清零成功 - Host ID:%d", $hostid);
			$update = [];
			if (isset($res["limit_flow"])) {
				$update["bwlimit"] = \intval($res["limit_flow"]);
			}
			$update["bwusage"] = 0;
			$update["lastupdate"] = time();
			\think\Db::name("host")->where("id", $hostid)->update($update);
			$suspendreason = \think\Db::name("host")->where("id", $hostid)->value("suspendreason");
			$suspendreason_type = explode("-", $suspendreason)[0];
			if ($res["act"] == 1 && ($nextduedate == 0 || date("Ymd") < date("Ymd", $nextduedate)) && $suspendreason_type == "flow") {
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
			$description = sprintf("流量清零失败,原因:%s - Host ID:%d", $res["msg"], $hostid);
			$res_data["status"] = 400;
			$res_data["msg"] = $res["msg"];
		}
		$reshost = \think\Db::name("host")->field("uid")->where("id", $hostid)->find();
		active_log_final($description, $reshost["uid"], 2, $hostid);
		return $res_data;
	}
	public function savePanelPass($hostid, $pid, $password)
	{
		$is_dcim = \think\Db::name("products")->where("id", $pid)->where("type", "dcim")->value("id");
		if (empty($is_dcim)) {
			return false;
		}
		$customid = \think\Db::name("customfields")->where("type", "product")->where("relid", $pid)->where("fieldname='面板管理密码' OR fieldname='panel_passwd'")->value("id");
		if (empty($customid)) {
			$customfields = ["type" => "product", "relid" => $pid, "fieldname" => "面板管理密码", "fieldtype" => "password", "adminonly" => 1, "create_time" => time()];
			$customid = \think\Db::name("customfields")->insertGetId($customfields);
		}
		$exist = \think\Db::name("customfieldsvalues")->where("fieldid", $customid)->where("relid", $hostid)->find();
		if (empty($exist)) {
			$data = ["fieldid" => $customid, "relid" => $hostid, "value" => $password, "create_time" => time()];
			\think\Db::name("customfieldsvalues")->insert($data);
		} else {
			\think\Db::name("customfieldsvalues")->where("id", $exist["id"])->update(["value" => $password]);
		}
		return true;
	}
	public function getPanelPass($hostid, $pid)
	{
		$is_dcim = \think\Db::name("products")->where("id", $pid)->where("type", "dcim")->value("id");
		if (empty($is_dcim)) {
			return false;
		}
		$value = \think\Db::name("customfields")->alias("a")->leftJoin("customfieldsvalues b", "a.id=b.fieldid")->where("a.type", "product")->where("a.relid", $pid)->where("a.fieldname", "面板管理密码")->where("b.relid", $hostid)->value("value");
		return is_null($value) ? false : ($value ?: "");
	}
	public function overTraffic($hostid, $dcimid, $bill_type = "month")
	{
		$postfields["id"] = $dcimid;
		$postfields["type"] = $bill_type;
		$postfields["hostid"] = $hostid;
		$res = $this->post("overTraffic", $postfields);
		if ($res["status"] == "success") {
			$description = sprintf("流量使用超额, 执行超额动作成功 - Host ID:%d", $hostid);
			$reshost = \think\Db::name("host")->field("uid")->where("id", $hostid)->find();
			active_log_final($description, $reshost["uid"], 2, $hostid);
			if ($res["act"] == 1) {
				$host = new Host();
				$host->is_admin = $this->is_admin;
				$result = $host->suspend($hostid, "flow", "流量使用超额");
				$logic_run_map = new RunMap();
				$model_host = new \app\common\model\HostModel();
				$data_i = [];
				$data_i["host_id"] = $hostid;
				$data_i["active_type_param"] = [$hostid, "flow", "流量使用超额", 0];
				$is_zjmf = $model_host->isZjmfApi($data_i["host_id"]);
				if ($result["status"] == 200) {
					$data_i["description"] = " 流量使用超额 - 暂停 Host ID:{$data_i["host_id"]}的产品成功";
					if ($is_zjmf) {
						$logic_run_map->saveMap($data_i, 1, 400, 2);
					}
					if (!$is_zjmf) {
						$logic_run_map->saveMap($data_i, 1, 100, 2);
					}
				} else {
					$data_i["description"] = " 流量使用超额 - 暂停 Host ID:{$data_i["host_id"]}的产品失败：{$result["msg"]}";
					if ($is_zjmf) {
						$logic_run_map->saveMap($data_i, 0, 400, 2);
					}
					if (!$is_zjmf) {
						$logic_run_map->saveMap($data_i, 0, 100, 2);
					}
				}
			}
		}
	}
	public function buyFlowPacket($params)
	{
		$postfields["id"] = $params["dcimid"];
		$postfields["type"] = $params["bill_type"];
		$postfields["traffic"] = $params["capacity"];
		$postfields["hostid"] = $params["hostid"];
		$res = $this->post("addTempTraffic", $postfields);
		if ($res["status"] == "success") {
			$description = sprintf("流量包购买成功，已成功附加到产品。- Invoice ID:%d - Host ID:%d", $params["invoiceid"], $params["hostid"]);
			if ($res["act"] == 1 && ($params["nextduedate"] == 0 || date("Ymd") < date("Ymd", $params["nextduedate"]))) {
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
			$r = $this->serverDetailflowData($params["hostid"], $params["dcimid"]);
			if ($r["status"] == "success") {
				$bill_type = $params["bill_type"] ?: "month";
				if ($r["limit"] != 0) {
					$r["limit"] = $r["limit"] + $r["temp_traffic"];
				}
				$update["bwlimit"] = \intval($r["limit"]);
				$update["bwusage"] = round(str_replace("GB", "", $r["data"][$bill_type][$r["type"]]), 2);
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
	public function serverDetailflowData($hostid, $dcimid, $unit = "GB")
	{
		$postfields["id"] = $dcimid;
		$postfields["hostid"] = $hostid;
		$postfields["unit"] = $unit;
		$result = $this->post("serverDetailflowData", $postfields);
		return $result;
	}
	public function post($action, $data = [], $timeout = 30)
	{
		$url = $this->url . "/index.php?m=api&a=" . $action;
		$data["username"] = $this->username;
		$data["password"] = $this->password;
		if ($this->error || empty($this->url)) {
			return ["status" => "error", "msg" => "接口错误"];
		}
		if ($data) {
			$data = http_build_query($data);
		}
		$ssl = substr($url, 0, 8) == "https://" ? true : false;
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		if ($ssl) {
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		}
		curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
		$result = curl_exec($curl);
		$curl_errno = curl_errno($curl);
		$curl_error = curl_error($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		if ($curl_errno > 0) {
			$this->curl_error = $curl_error;
			$result = [];
			$result["status"] = "error";
			$result["msg"] = "无法连接到服务器管理系统 - CURL ERROR:" . $curl_error;
		} else {
			if ($http_code != 200) {
				$result = [];
				$result["status"] = "error";
				$result["msg"] = "无法连接到服务器管理系统,HTTP状态码:" . $http_code;
			} else {
				$result = json_decode($result, true);
			}
		}
		return $result;
	}
	public function curl_get($url, $timeout = 30)
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_HTTPGET, 1);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
		$content = curl_exec($curl);
		curl_close($curl);
		return $content;
	}
	public function bmsLcGroup()
	{
		$res = $this->bmsCurl("/bms/lcGroup?listpages=9999&sort=asc", [], "GET");
		$data = [];
		if ($res["code"] == 200) {
			foreach ($res["data"]["list"] as $v) {
				$data[] = ["name" => $v["id"] . "|" . $v["name"]];
			}
		}
		return $data;
	}
	public function bmsOs()
	{
		$res = $this->bmsCurl("/bms/os?listpages=9999&sort=asc", [], "GET");
		$data = [];
		if ($res["code"] == 200) {
			foreach ($res["data"]["list"] as $v) {
				if ($v["status"] == 1) {
					if ($v["os_name"] == "Windows_Rescue.raw" || $v["os_name"] == "Linux_Rescue.raw") {
						continue;
					}
					$data[] = ["name" => $v["id"] . "|" . $v["group_name"] . "^" . $v["name"]];
				}
			}
		}
		return $data;
	}
	public function moduleAdminButton($id)
	{
		$button = [["type" => "default", "func" => "create", "name" => "开通"], ["type" => "default", "func" => "suspend", "name" => "暂停"], ["type" => "default", "func" => "unsuspend", "name" => "解除暂停"], ["type" => "default", "func" => "terminate", "name" => "删除"], ["type" => "default", "func" => "on", "name" => "开机"], ["type" => "default", "func" => "off", "name" => "关机"], ["type" => "default", "func" => "reboot", "name" => "重启"], ["type" => "default", "func" => "hard_off", "name" => "硬关机"], ["type" => "default", "func" => "hard_reboot", "name" => "硬重启"], ["type" => "default", "func" => "reinstall", "name" => "重装系统"], ["type" => "default", "func" => "crack_pass", "name" => "重置密码"], ["type" => "default", "func" => "vnc", "name" => "VNC"], ["type" => "default", "func" => "sync", "name" => "拉取信息"]];
		if (!empty($id)) {
			array_shift($button);
		} else {
			$button = [array_shift($button)];
		}
		return $button;
	}
	public function moduleClientButton($id)
	{
		if (empty($id)) {
			return ["control" => [], "console" => []];
		}
		$button = ["control" => [["type" => "default", "func" => "on", "name" => "开机"], ["type" => "default", "func" => "off", "name" => "关机"], ["type" => "default", "func" => "reboot", "name" => "重启"], ["type" => "default", "func" => "hard_off", "name" => "硬关机"], ["type" => "default", "func" => "hard_reboot", "name" => "硬重启"], ["type" => "default", "func" => "reinstall", "name" => "重装系统"], ["type" => "default", "func" => "crack_pass", "name" => "重置密码"]], "console" => [["type" => "default", "func" => "vnc", "name" => "VNC"]]];
		return $button;
	}
	public function moduleClientArea($id)
	{
		$mod = new \app\common\model\HostModel();
		$params = $mod->getProvisionParams($id);
		if (empty($params["dcimid"])) {
			return [];
		}
		if ($params["config_option1"] != "bms") {
			return [];
		}
		$name = "";
		if (\intval($params["configoptions"]["snap_num"]) >= 0) {
			$name = "快照";
		}
		if (\intval($params["configoptions"]["backup_num"]) >= 0) {
			$name = $name ? $name . "/备份" : "备份";
		}
		$data = [];
		if (!empty($name)) {
			$data = [["name" => $name, "key" => "snapshot"]];
		}
		return $data;
	}
	public function moduleAllowFunction()
	{
		return ["createSnap", "deleteSnap", "restoreSnap", "createBackup", "deleteBackup", "restoreBackup", "getMirrorOsConfig"];
	}
	public function createSnap($id)
	{
		$post = input("post.");
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.config_option1,b.api_type,b.zjmf_api_id")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcim")->where("a.id", $id)->find();
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
			if (empty($product["dcimid"]) || $product["config_option1"] != "bms") {
				$result["status"] = 400;
				$result["msg"] = "裸金属ID错误";
				return $result;
			}
			if (empty($post["id"])) {
				$result["status"] = 400;
				$result["msg"] = "参数错误";
				return $result;
			}
			$res = $this->bmsCurl("/bms/source/" . $product["dcimid"] . "/snap", ["disk_id" => $post["id"], "remarks" => $post["name"]]);
		}
		if ($res["code"] == 200 || $res["status"] == 200) {
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
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.config_option1,b.api_type,b.zjmf_api_id")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcim")->where("a.id", $id)->find();
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
			if (empty($product["dcimid"]) || $product["config_option1"] != "bms") {
				$result["status"] = 400;
				$result["msg"] = "裸金属ID错误";
				return $result;
			}
			if (empty($post["id"])) {
				$result["status"] = 400;
				$result["msg"] = "参数错误";
				return $result;
			}
			$list = $this->bmsCurl("/bms/source/" . $product["dcimid"] . "/snap?listpages=999&sorting=asc", [], "GET", 30);
			if ($list["code"] == 200) {
				$ids = array_column($list["data"]["list"], "id");
				if (!in_array($post["id"], $ids)) {
					$result["status"] = 400;
					$result["msg"] = "快照不存在";
					return $result;
				}
				$res = $this->bmsCurl("/bms/snap/" . $post["id"], [], "DELETE");
			} else {
				$res["msg"] = $list["msg"] ?: "快照删除失败";
			}
		}
		if ($res["code"] == 200 || $res["status"] == 200) {
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
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.config_option1,b.api_type,b.zjmf_api_id")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcim")->where("a.id", $id)->find();
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
			if (empty($product["dcimid"]) || $product["config_option1"] != "bms") {
				$result["status"] = 400;
				$result["msg"] = "裸金属ID错误";
				return $result;
			}
			if (empty($post["id"])) {
				$result["status"] = 400;
				$result["msg"] = "参数错误";
				return $result;
			}
			$list = $this->bmsCurl("/bms/source/" . $product["dcimid"] . "/snap?listpages=999&sorting=asc", [], "GET", 30);
			if ($list["code"] == 200) {
				$ids = array_column($list["data"]["list"], "id");
				if (!in_array($post["id"], $ids)) {
					$result["status"] = 400;
					$result["msg"] = "快照不存在";
					return $result;
				}
				$res = $this->bmsCurl("/bms/snap/" . $post["id"] . "/restore");
			} else {
				$res["msg"] = $list["msg"] ?: "快照恢复失败";
			}
		}
		if ($res["code"] == 200 || $res["status"] == 200) {
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
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.config_option1,b.api_type,b.zjmf_api_id")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcim")->where("a.id", $id)->find();
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
			if (empty($product["dcimid"]) || $product["config_option1"] != "bms") {
				$result["status"] = 400;
				$result["msg"] = "裸金属ID错误";
				return $result;
			}
			if (empty($post["id"])) {
				$result["status"] = 400;
				$result["msg"] = "参数错误";
				return $result;
			}
			$res = $this->bmsCurl("/bms/source/" . $product["dcimid"] . "/backup", ["disk_id" => $post["id"], "remarks" => $post["name"]]);
		}
		if ($res["code"] == 200 || $res["status"] == 200) {
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
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.config_option1,b.api_type,b.zjmf_api_id")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcim")->where("a.id", $id)->find();
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
			if (empty($product["dcimid"]) || $product["config_option1"] != "bms") {
				$result["status"] = 400;
				$result["msg"] = "裸金属ID错误";
				return $result;
			}
			if (empty($post["id"])) {
				$result["status"] = 400;
				$result["msg"] = "参数错误";
				return $result;
			}
			$list = $this->bmsCurl("/bms/source/" . $product["dcimid"] . "/backup?listpages=999&sorting=asc", [], "GET", 30);
			if ($list["code"] == 200) {
				$ids = array_column($list["data"]["list"], "id");
				if (!in_array($post["id"], $ids)) {
					$result["status"] = 400;
					$result["msg"] = "备份不存在";
					return $result;
				}
				$res = $this->bmsCurl("/bms/backup/" . $post["id"], [], "DELETE");
			} else {
				$res["msg"] = $list["msg"] ?: "备份删除失败";
			}
		}
		if ($res["code"] == 200 || $res["status"] == 200) {
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
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.config_option1,b.api_type,b.zjmf_api_id")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcim")->where("a.id", $id)->find();
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
			if (empty($product["dcimid"]) || $product["config_option1"] != "bms") {
				$result["status"] = 400;
				$result["msg"] = "云主机ID错误";
				return $result;
			}
			if (empty($post["id"])) {
				$result["status"] = 400;
				$result["msg"] = "参数错误";
				return $result;
			}
			$list = $this->bmsCurl("/bms/source/" . $product["dcimid"] . "/backup?listpages=999&sorting=asc", [], "GET", 30);
			if ($list["code"] == 200) {
				$ids = array_column($list["data"]["list"], "id");
				if (!in_array($post["id"], $ids)) {
					$result["status"] = 400;
					$result["msg"] = "备份不存在";
					return $result;
				}
				$res = $this->bmsCurl("/bms/backup/" . $post["id"] . "/restore");
			} else {
				$res["msg"] = $list["msg"] ?: "备份还原失败";
			}
		}
		if ($res["code"] == 200 || $res["status"] == 200) {
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
	public function moduleClientAreaDetail($id, $key, $api_url = "")
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.config_option1")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcim")->where("a.id", $id)->find();
		if (empty($product) || $product["config_option1"] != "bms") {
			return "";
		}
		$this->setUrl($product["serverid"]);
		if ($this->error) {
			return "";
		}
		if (empty($product["dcimid"])) {
			return "";
		}
		if ($key == "snapshot") {
			$mod = new \app\common\model\HostModel();
			$params = $mod->getProvisionConfigOption($id);
			$support_snap = \intval($params["configoptions"]["snap_num"]) >= 0;
			$support_backup = \intval($params["configoptions"]["backup_num"]) >= 0;
			$detail = $this->bmsCurl("/bms/source/" . $product["dcimid"], [], "GET", 15);
			if ($support_snap) {
				$snap = $this->bmsCurl("/bms/source/" . $product["dcimid"] . "/snap", ["listpages" => 999], "GET", 15);
			}
			if ($support_backup) {
				$backup = $this->bmsCurl("/bms/source/" . $product["dcimid"] . "/backup", ["listpages" => 999], "GET", 15);
			}
			$res = ["template" => "snapshot.html", "vars" => ["snap" => $snap["data"]["list"] ?? [], "backup" => $backup["data"]["list"] ?? [], "disk" => $detail["data"]["disk"], "support_snap" => $support_snap, "support_backup" => $support_backup]];
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
	public function execCustomButton($host_data, $func = "")
	{
		if (empty($host_data["dcimid"])) {
			$result["status"] = 400;
			$result["msg"] = "操作失败";
			return $result;
		}
		$this->setUrl($host_data["serverid"]);
		if ($this->error) {
			$result["status"] = 400;
			$result["msg"] = "操作失败";
			return $result;
		}
		$id = $host_data["id"];
		if ($func == "rescue") {
			$res = $this->bmsCurl("/bms/source/" . $host_data["dcimid"] . "/rescue");
			if ($res["code"] == 200) {
				active_log_final(sprintf("模块命令:救援系统发起成功#Host ID:%d", $id), $host_data["uid"], 2, $id);
				$result["status"] = 200;
				$result["msg"] = "救援系统发起成功";
			} else {
				$result["status"] = 400;
				$result["msg"] = $res["msg"];
				if ($this->is_admin) {
					$result["msg"] = $this->log_prefix_error . $result["msg"];
					$res["msg"] = $this->log_prefix_error . $res["msg"];
					active_log_final(sprintf("救援系统发起失败#Host ID:%d - 原因:%s", $id, $res["msg"]), $host_data["uid"], 2, $id);
				} else {
					active_log_final(sprintf("救援系统发起失败#Host ID:%d - 原因:%s", $id, $res["msg"]), $host_data["uid"], 2, $id);
					$result["msg"] = "救援系统发起失败";
				}
			}
		} elseif ($func == "exit_rescue") {
			$res = $this->bmsCurl("/bms/source/" . $host_data["dcimid"] . "/rescue", [], "DELETE");
			if ($res["code"] == 200) {
				active_log_final(sprintf("模块命令:退出救援系统发起成功#Host ID:%d", $id), $host_data["uid"], 2, $id);
				$result["status"] = 200;
				$result["msg"] = "退出救援系统发起成功";
			} else {
				$result["status"] = 400;
				$result["msg"] = $res["msg"];
				if ($this->is_admin) {
					$result["msg"] = $this->log_prefix_error . $result["msg"];
					$res["msg"] = $this->log_prefix_error . $res["msg"];
					active_log_final(sprintf("退出救援系统失败#Host ID:%d - 原因:%s", $id, $res["msg"]), $host_data["uid"], 2, $id);
				} else {
					active_log_final(sprintf("退出救援系统失败#Host ID:%d - 原因:%s", $id, $res["msg"]), $host_data["uid"], 2, $id);
					$result["msg"] = "退出救援系统失败";
				}
			}
		} else {
			$result["status"] = 400;
			$result["msg"] = lang("NO_SUPPORT_FUNCTION");
		}
		return $result;
	}
	public function bmsCrackPassword($id, $new_pass)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.config_option1")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcim")->where("a.id", $id)->find();
		if (empty($product) || $product["config_option1"] != "bms") {
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
		$res = $this->bmsCurl("/bms/source/" . $product["dcimid"] . "/crackPwd", ["password" => $new_pass]);
		if ($res["code"] == 200) {
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
	public function bmsHardOff($id)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.config_option1")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcim")->where("a.id", $id)->find();
		if (empty($product) || $product["config_option1"] != "bms") {
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
			$result["msg"] = "裸金属ID错误";
			return $result;
		}
		$res = $this->bmsCurl("/bms/source/" . $product["dcimid"] . "/destroy");
		if ($res["code"] == 200) {
			$result["status"] = 200;
			$result["msg"] = "发起硬关机成功";
		} else {
			$result["status"] = 400;
			$result["msg"] = "硬关机失败";
			if ($this->is_admin) {
				$result["msg"] = $this->log_prefix_error . $result["msg"];
			}
		}
		return $result;
	}
	public function bmsHardReboot($id)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,b.config_option1")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcim")->where("a.id", $id)->find();
		if (empty($product) || $product["config_option1"] != "bms") {
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
		$res = $this->bmsCurl("/bms/source/" . $product["dcimid"] . "/restart");
		if ($res["code"] == 200) {
			$result["status"] = 200;
			$result["msg"] = "发起硬重启成功";
		} else {
			$result["status"] = 400;
			$result["msg"] = "硬重启失败";
			if ($this->is_admin) {
				$result["msg"] = $this->log_prefix_error . $result["msg"];
			}
		}
		return $result;
	}
	public function bmsReinstall($id, $os, $os_name)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.uid,a.dcimid,a.password,a.reinstall_info,b.config_option1")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcim")->where("a.id", $id)->find();
		if (empty($product) || $product["config_option1"] != "bms") {
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
		$post_data["os_id"] = $os;
		$post_data["password"] = cmf_decrypt($product["password"]);
		$res = $this->bmsCurl("/bms/source/" . $product["dcimid"] . "/reinstall", $post_data);
		if ($res["code"] == 200) {
			$result["status"] = 200;
			$result["msg"] = "重装发起成功";
			$update["username"] = strpos($os_name, "win") !== false ? "administrator" : "root";
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
				$update["reinstall_info"] = json_encode($d);
			}
			\think\Db::name("host")->where("id", $id)->update($update);
		} else {
			$result["status"] = 400;
			$result["msg"] = "重装失败";
			if ($this->is_admin) {
				$result["msg"] = $this->log_prefix_error . $result["msg"];
			}
		}
		return $result;
	}
	public function sync($id)
	{
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.uid,a.productid,a.nextduedate,a.regdate,b.config_option1")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcim")->where("a.id", $id)->find();
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
		if ($product["config_option1"] == "bms") {
			$res = $this->bmsCurl("/bms/source/" . $product["dcimid"], [], "GET");
			if ($res["code"] == 200) {
				$update["dedicatedip"] = $res["data"]["ip"] ?: "";
				if (!empty($res["data"]["float_ip"])) {
					$update["assignedips"] = implode(",", array_column($res["data"]["float_ip"], "ipaddress"));
				}
				$update["domain"] = $res["data"]["name"];
				$update["username"] = $res["data"]["username"];
				$update["password"] = cmf_encrypt($res["data"]["password"]);
				$configoptions = \think\Db::name("product_config_links")->alias("a")->leftJoin("product_config_options b", "a.gid=b.gid")->where("a.pid", $product["productid"])->where("b.option_type", 5)->value("b.id");
				if (!empty($configoptions)) {
					$sub = \think\Db::name("product_config_options_sub")->field("id,config_id,option_name")->whereLike("option_name", $res["data"]["os_id"] . "|%")->where("config_id", $configoptions)->find();
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
				\think\Db::name("host")->where("id", $id)->update($update);
				$result["status"] = 200;
				$result["msg"] = "同步成功";
			} else {
				$result["status"] = 400;
				$result["msg"] = $res["msg"];
				if ($this->is_admin) {
					$result["msg"] = $this->log_prefix_error . $result["msg"];
				}
			}
		} else {
			$dcim_server = \think\Db::name("dcim_servers")->where("serverid", $product["serverid"])->find();
			$dcim_server["auth"] = json_decode($dcim_server["auth"], true);
			$post_data["id"] = $product["dcimid"];
			$post_data["hostid"] = $id;
			$post_data["user_id"] = $this->user_prefix . $product["uid"];
			$post_data["domainstatus"] = "Active";
			if ($product["nextduedate"] > 0) {
				$post_data["expiretime"] = date("Y-m-d H:i:s", $product["nextduedate"]);
			}
			$post_data["starttime"] = date("Y-m-d H:i:s", $product["regdate"]);
			$post_data["token"] = configuration("system_token") ?? "";
			if ($dcim_server["auth"]["enable_ip_custom"] == "on") {
				$post_data["ip_customid"] = \intval($dcim_server["ip_customid"]);
			}
			$res = $this->post("ipmiSync", $post_data);
			if ($res["status"] == "success") {
				$mod = new \app\common\model\HostModel();
				$params = $mod->getProvisionParams($id);
				$update["dcimid"] = $product["dcimid"];
				$update["dedicatedip"] = $res["zhuip"] ?: "";
				$ips = explode("\r\n", $res["ips"]);
				if ($dcim_server["auth"]["enable_ip_custom"] != "on") {
					foreach ($ips as $k => $v) {
						if (empty($v) || $v == $res["zhuip"]) {
							unset($ips[$k]);
						}
					}
				} else {
					foreach ($ips as $k => $v) {
						$ips[$k] = str_replace(",", "，", $v);
					}
				}
				$update["assignedips"] = implode(",", $ips);
				$update["domainstatus"] = "Active";
				$update["os"] = $res["os"] ?? "";
				$update["username"] = $res["username"] ?? "";
				$update["password"] = cmf_encrypt($res["password"]);
				$update["port"] = $res["port"];
				if (!empty($res["os_id"])) {
					$update["dcim_os"] = $res["os_id"];
				}
				$update["dcim_area"] = intval($res["house"]);
				$update["bwlimit"] = intval($params["configoptions"]["bwt"]);
				$update["bwusage"] = 0;
				$update["lastupdate"] = time();
				$product_host_rule = json_decode($product["host"], true);
				if (empty($product_host_rule) || $product_host_rule["show"] != 1) {
					$update["domain"] = $res["wltag"] ?: "";
				}
				\think\Db::name("host")->where("id", $id)->update($update);
				$this->savePanelPass($id, $params["productid"], $res["ippassword"]);
				$result = [];
				$result["status"] = 200;
				$result["msg"] = "同步成功";
				$description .= sprintf("同步成功#Host ID:%s", $id);
				pushHostInfo($id);
			} else {
				$result["status"] = 400;
				$result["msg"] = $res["msg"] ?: "同步失败";
				$description .= sprintf("同步失败#Host ID:%s 失败原因:%s", $id, $res["msg"]);
				if ($this->is_admin) {
					$result["msg"] = $this->log_prefix_error . $result["msg"];
					$description = $this->log_prefix_error . $description;
				}
			}
			active_log_final($description, $product["uid"], 2, $id);
		}
		return $result;
	}
	public function bmsCurl($action, $data = [], $request = "POST", $timeout = 30)
	{
		$url = $this->url . $action;
		$header = ["access-user: " . $this->username, "access-token: " . $this->password];
		$res = commonCurl($url, $data, $timeout, $request, $header);
		if (!isset($res["code"])) {
			$res["code"] = $res["status"];
		}
		return $res;
	}
}