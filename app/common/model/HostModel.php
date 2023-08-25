<?php

namespace app\common\model;

class HostModel extends \think\Model
{
	public function getProvisionParams($hostid = 0)
	{
		$data = \think\Db::name("host")->field("a.id hostid,a.productid,a.uid,a.serverid,a.regdate,a.domain,a.payment,a.amount,a.billingcycle,
					a.nextduedate,a.nextinvoicedate,a.termination_date,a.domainstatus,a.username,a.password host_password,a.notes,a.promoid,
					a.suspendreason,a.overideautosuspend,a.overidesuspenduntil,a.dedicatedip,a.assignedips,a.dcimid,a.dcim_os,a.os,b.type,b.gid,b.name,b.description,b.hidden,b.show_domain_options,b.welcome_email,b.stock_control,b.qty,b.pay_type,b.allow_qty,b.auto_setup,b.server_group,b.config_option1,b.config_option2,b.config_option3,b.config_option4,b.config_option5,b.config_option6,b.config_option7,b.config_option8,b.config_option9,b.config_option10,b.config_option11,b.config_option12,b.config_option13,b.config_option14,b.config_option15,b.config_option16,b.config_option17,b.config_option18,b.config_option19,b.config_option20,b.config_option21,b.config_option22,b.config_option23,b.config_option24,b.config_options_upgrade,b.retired,b.is_featured,b.api_type,b.location_version,b.upstream_version,b.upstream_price_type,b.upstream_price_value,b.zjmf_api_id,b.upstream_pid,c.ip_address server_ip,
					c.hostname server_host,c.username server_username,c.password server_password,c.accesshash,c.secure,c.port,c.type module_type")->alias("a")->leftJoin("products b", "a.productid=b.id")->leftJoin("servers c", "a.serverid=c.id")->where("a.id", $hostid)->find();
		if (empty($data)) {
			return [];
		}
		if ($data["secure"] == 1) {
			$data["server_http_prefix"] = "https";
		} else {
			$data["server_http_prefix"] = "http";
		}
		$data["server_password"] = aesPasswordDecode($data["server_password"]);
		$data["password"] = cmf_decrypt($data["host_password"]);
		$data["user_info"] = \think\Db::name("clients")->where("id", $data["uid"])->find();
		$customfields = (new CustomfieldsModel())->getCustomValue($data["productid"], $hostid, "product");
		$data["customfields"] = [];
		foreach ($customfields as $k => $v) {
			$data["customfields"][$v["fieldname"]] = $v["value"];
		}
		$config_options = \think\Db::name("host_config_options")->field("a.id,a.qty,b.option_name,b.option_type,c.option_name sub_option,b.upgrade")->alias("a")->leftJoin("product_config_options b", "a.configid=b.id")->leftJoin("product_config_options_sub c", "a.configid=c.config_id and a.optionid=c.id and b.option_type!=4")->where("a.relid", $hostid)->select()->toArray();
		$data["configoptions"] = [];
		$data["configoptions_upgrade"] = [];
		foreach ($config_options as $k => $v) {
			if (strpos($v["option_name"], "|") !== false) {
				$key = explode("|", $v["option_name"])[0];
			} else {
				$key = $v["option_name"];
			}
			if (judgeQuantity($v["option_type"])) {
				$value = $v["qty"];
			} else {
				if (strpos($v["sub_option"], "|") !== false) {
					$value = explode("|", $v["sub_option"])[0];
				} else {
					$value = $v["sub_option"];
				}
			}
			if ($v["upgrade"] == 1) {
				$data["configoptions_upgrade"][$key] = $value;
			}
			$data["configoptions"][$key] = $value;
		}
		return $data;
	}
	public function getProvisionConfigOption($hostid = 0)
	{
		$config_options = \think\Db::name("host_config_options")->field("a.id,a.qty,b.option_name,b.option_type,c.option_name sub_option,b.upgrade")->alias("a")->leftJoin("product_config_options b", "a.configid=b.id")->leftJoin("product_config_options_sub c", "a.configid=c.config_id and a.optionid=c.id and b.option_type!=4")->where("a.relid", $hostid)->select()->toArray();
		$data["configoptions"] = [];
		$data["configoptions_upgrade"] = [];
		foreach ($config_options as $k => $v) {
			if (strpos($v["option_name"], "|") !== false) {
				$key = explode("|", $v["option_name"])[0];
			} else {
				$key = $v["option_name"];
			}
			if (judgeQuantity($v["option_type"])) {
				$value = $v["qty"];
			} else {
				if (strpos($v["sub_option"], "|") !== false) {
					$value = explode("|", $v["sub_option"])[0];
				} else {
					$value = $v["sub_option"];
				}
			}
			if ($v["upgrade"] == 1) {
				$data["configoptions_upgrade"][$key] = $value;
			}
			$data["configoptions"][$key] = $value;
		}
		return $data;
	}
	public function getConfigOption($host, $key)
	{
		if (empty($key)) {
			return [];
		}
		$res = \think\Db::name("host_config_options")->field("a.id,a.qty,a.configid,a.optionid,b.option_name,b.option_type,b.upgrade,c.option_name sub_option")->alias("a")->leftJoin("product_config_options b", "a.configid=b.id")->leftJoin("product_config_options_sub c", "a.configid=c.config_id and a.optionid=c.id")->where("a.relid", $host)->whereLike("b.option_name", $key . "|%")->find();
		if (empty($res)) {
			return [];
		}
		$res["sub_option_arr"] = explode("|", $res["sub_option"]);
		return $res;
	}
	public function isZjmfApi($host_id)
	{
		$host = \think\Db::name("host")->alias("a")->field("b.api_type")->leftJoin("products b", "a.productid=b.id")->where("a.id", $host_id)->find();
		if ($host["api_type"] == "zjmf_api" || $host["api_type"] == "resource") {
			return true;
		}
		return false;
	}
}