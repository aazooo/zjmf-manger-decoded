<?php

namespace app\admin\controller;

/**
 * @title 后台服务模块
 * @description 接口说明
 */
class ProvisionController extends AdminBaseController
{
	/**
	 * @title 获取所有模块
	 * @description 获取所有模块
	 * @author      huanghao
	 * @url         admin/provision/list
	 * @method     	GET
	 * @time        2019-11-21
	 * @return      .value:值
	 * @return      .name:显示的名称
	 */
	public function getModules()
	{
		$provision = new \app\common\logic\Provision();
		$modules = $provision->getModules();
		$result["status"] = 200;
		$result["data"] = $modules;
		return jsonrule($result);
	}
	/**
	 * @title 获取模块metadata
	 * @description 获取模块metadata
	 * @author       huanghao
	 * @url         /admin/provision/metadata
	 * @method      GET
	 * @time        2019-11-21
	 * @param  .name:name type:string default: other: desc:模块名称
	 * @return    .DefaultNonSSLPort:默认非ssl端口   
	 * @return    .DefaultSSLPort:默认ssl端口   
	 */
	public function getMetaData()
	{
		$module = input("get.name");
		$provision = new \app\common\logic\Provision();
		$data = $provision->getModuleMetaData($module);
		$result["status"] = 200;
		$result["data"] = $data;
		return jsonrule($result);
	}
	/**
	 * @title 获取模块设置
	 * @description 获取模块设置
	 * @author huanghao
	 * @url         /admin/provision/:id
	 * @method      GET
	 * @time        2019-12-05
	 * @param       .name:id type:string require:1 default: other: desc:服务器组id
	 * @return      .name:名称
	 * @return      .placeholder:placeholder
	 * @return      .description:描述
	 * @return      .default:默认值
	 * @return      .type:类型text,password,yesno(值 on|off),radio,dropdown,textarea,
	 * @return      .options:选项,单选和下拉才有
	 * @return      .rows:文本域属性rows
	 * @return      .cols:文本域属性cols
	 * @return     module_meta.APIVersion:API版本
	 * @return     module_meta.HelpDoc:帮助文档地址
	 */
	public function getModuleConfig($id = 0)
	{
		$server = \think\Db::name("server_groups")->where("id", $id)->find();
		if (empty($server)) {
			$result["status"] = 200;
			$result["data"] = [];
			return jsonrule($result);
		}
		$result["module_meta"] = [];
		if ($server["system_type"] == "dcim") {
			$data = [["default" => "rent", "description" => "", "name" => "产品类型", "type" => "dropdown", "options" => [["value" => "rent", "name" => "租用/托管"], ["value" => "cabinet", "name" => "机柜/带宽/IP"], ["value" => "bms", "name" => "裸金属"]]]];
			$result["module_meta"]["HelpDoc"] = "https://www.idcsmart.com/wiki_list/338.html#2.1.5";
		} elseif ($server["system_type"] == "dcimcloud") {
			$data = [];
			$result["module_meta"]["HelpDoc"] = "https://www.idcsmart.com/wiki_list/358.html#2.1.3";
		} else {
			$servers = \think\Db::name("servers")->where("gid", $id)->find();
			if (empty($servers)) {
				$result["status"] = 200;
				$result["data"] = [];
				return jsonrule($result);
			}
			$module = $servers["type"];
			$provision = new \app\common\logic\Provision();
			$data = $provision->getModuleConfigOptions($module);
			$result["module_meta"] = $provision->getModuleMetaData($module);
		}
		$result["status"] = 200;
		$result["data"] = $data;
		return jsonrule($result);
	}
	/**
	 * @title 执行开通,暂停,解除暂停,删除,修改密码
	 * @description 执行开通,暂停,解除暂停,删除,修改密码
	 * @author huanghao
	 * @url         /admin/provision/default
	 * @method      POST
	 * @time        2019-12-04
	 * @param       .name:id type:int require:1 default: other: desc:hostid
	 * @param       .name:func type:string require:1 default: other: desc:执行的方法create开通,suspend暂停,unsuspend解除暂停,terminate删除
	 * @param       .name:reason_type type:string require:0 default: other: desc:暂停原因类型
	 * @param       .name:reason type:string require:0 default: other: desc:暂停原因
	 * @param       .name:send type:int require:0 default:0 other: desc:是否发送暂停|解除暂停邮件
	 * @param       .name:password type:string require:0 default:0 other: desc:重置密码的新密码
	 * @param       .name:os type:int require:0 default:0 other: desc:操作系统id(func=reinstall)
	 * @return      url:获取vnc返回的地址(func=vnc)
	 * @return      status:机器状态(func=status)
	 * @return      des:机器描述(func=status)
	 */
	public function execute()
	{
		session_write_close();
		$id = input("post.id", 0, "int");
		$func = input("post.func", "");
		$reason_type = input("post.reason_type", "other");
		$arr = ["flow" => "用量超额", "due" => "到期", "uncertifi" => "未实名认证", "other" => "其他"];
		$reason_type_desc = $arr[$reason_type];
		$reason = input("post.reason", "");
		$send = input("post.send", 0);
		$os = input("post.os", 0, "intval");
		if (empty($id)) {
			$result["status"] = 406;
			$result["msg"] = lang("ID_ERROR");
			return jsonrule($result);
		}
		$host = new \app\common\logic\Host();
		$host->is_admin = true;
		$logic_run_map = new \app\common\logic\RunMap();
		$model_host = new \app\common\model\HostModel();
		switch ($func) {
			case "create":
				$result = $host->create($id);
				$data_i = [];
				$data_i["host_id"] = $id;
				$data_i["active_type_param"] = [$id, ""];
				$is_zjmf = $model_host->isZjmfApi($data_i["host_id"]);
				if ($result["status"] == 200) {
					$data_i["description"] = " 模块命令 - 开通 Host ID:{$id}的产品成功";
					if ($is_zjmf) {
						$logic_run_map->saveMap($data_i, 1, 400, 1, 1);
					}
					if (!$is_zjmf) {
						$logic_run_map->saveMap($data_i, 1, 200, 1, 1);
					}
				} else {
					$data_i["description"] = " 模块命令 - 开通 Host ID:{$id}的产品失败：{$result["msg"]}";
					if ($is_zjmf) {
						$logic_run_map->saveMap($data_i, 0, 400, 1, 1);
					}
					if (!$is_zjmf) {
						$logic_run_map->saveMap($data_i, 0, 200, 1, 1);
					}
				}
				break;
			case "suspend":
				if ($reason_type === "other" && !$reason) {
					return jsonrule(["status" => 406, "msg" => "请填写暂停原因!"]);
				}
				if ($reason_type !== "other") {
					$reason = $reason_type_desc;
				}
				$result = $host->suspend($id, $reason_type, $reason, $send);
				$data_i = [];
				$data_i["host_id"] = $id;
				$data_i["active_type_param"] = [$id, $reason_type, $reason, $send];
				$is_zjmf = $model_host->isZjmfApi($data_i["host_id"]);
				if ($result["status"] == 200) {
					$data_i["description"] = " 模块命令 - 暂停 Host ID:{$id}的产品成功";
					if ($is_zjmf) {
						$logic_run_map->saveMap($data_i, 1, 400, 2, 1);
					}
					if (!$is_zjmf) {
						$logic_run_map->saveMap($data_i, 1, 200, 2, 1);
					}
				} else {
					$data_i["description"] = " 模块命令 - 暂停 Host ID:{$id}的产品失败：{$result["msg"]}";
					if ($is_zjmf) {
						$logic_run_map->saveMap($data_i, 0, 400, 2, 1);
					}
					if (!$is_zjmf) {
						$logic_run_map->saveMap($data_i, 0, 200, 2, 1);
					}
				}
				break;
			case "unsuspend":
				$result = $host->unsuspend($id, $send);
				$data_i = [];
				$data_i["host_id"] = $id;
				$data_i["active_type_param"] = [$id, $send, "", 0];
				$is_zjmf = $model_host->isZjmfApi($data_i["host_id"]);
				if ($result["status"] == 200) {
					$data_i["description"] = " 模块命令 - 解除暂停 Host ID:{$id}的产品成功";
					if ($is_zjmf) {
						$logic_run_map->saveMap($data_i, 1, 400, 3, 1);
					}
					if (!$is_zjmf) {
						$logic_run_map->saveMap($data_i, 1, 200, 3, 1);
					}
				} else {
					$data_i["description"] = " 模块命令 - 解除暂停 Host ID:{$id}的产品失败：{$result["msg"]}";
					if ($is_zjmf) {
						$logic_run_map->saveMap($data_i, 0, 400, 3, 1);
					}
					if (!$is_zjmf) {
						$logic_run_map->saveMap($data_i, 0, 200, 3, 1);
					}
				}
				break;
			case "terminate":
				$result = $host->terminate($id);
				$data_i = [];
				$data_i["host_id"] = $id;
				$data_i["active_type_param"] = [$id, ""];
				$is_zjmf = $model_host->isZjmfApi($data_i["host_id"]);
				if ($result["status"] == 200) {
					$data_i["description"] = " 模块命令 - 删除 Host ID:{$id}的产品成功";
					if ($is_zjmf) {
						$logic_run_map->saveMap($data_i, 1, 400, 4, 1);
					}
					if (!$is_zjmf) {
						$logic_run_map->saveMap($data_i, 1, 200, 4, 1);
					}
				} else {
					$data_i["description"] = " 模块命令 - 删除 Host ID:{$id}的产品失败：{$result["msg"]}";
					if ($is_zjmf) {
						$logic_run_map->saveMap($data_i, 0, 400, 4, 1);
					}
					if (!$is_zjmf) {
						$logic_run_map->saveMap($data_i, 0, 200, 4, 1);
					}
				}
				break;
			case "crack_pass":
				$password = input("post.password");
				$result = $host->crackPass($id, $password);
				break;
			case "on":
				$result = $host->on($id);
				break;
			case "off":
				$result = $host->off($id);
				break;
			case "reboot":
				$result = $host->reboot($id);
				break;
			case "hard_off":
				$result = $host->hardOff($id);
				break;
			case "hard_reboot":
				$result = $host->hardReboot($id);
				break;
			case "reinstall":
				$port = input("post.port", 0, "intval");
				$result = $host->reinstall($id, $os, $port);
				break;
			case "vnc":
				$result = $host->vnc($id);
				break;
			case "status":
				$result = $host->status($id);
				break;
			case "sync":
				$result = $host->sync($id);
				break;
			case "panel":
				$result = $host->panel($id);
				break;
			case "pushHostInfo":
				$result = pushHostInfo($id);
				break;
			case "rescue_system":
				$system = input("post.system", 1, "intval");
				$result = $host->rescueSystem($id, $system);
				break;
			default:
				$result["status"] = 406;
				$result["msg"] = lang("NO_SUPPORT_FUNCTION");
				break;
		}
		session_write_close();
		return jsonrule($result);
	}
	/**
	 * @title 执行模块自定义方法
	 * @description 执行模块自定义方法
	 * @author huanghao
	 * @url         /admin/provision/custom
	 * @method      POST
	 * @time        2019-12-05
	 * @param       .name:id type:int require:1 default: other: desc:服务id
	 * @param       .name:func type:int require:1 default: other: desc:自定义方法名称
	 * @return      
	 */
	public function execAdmin()
	{
		$hostid = input("post.id", 0, "int");
		$func = input("post.func", "");
		$host = \think\Db::name("host")->alias("h")->field("h.id,p.type,p.api_type")->leftjoin("products p", "h.productid=p.id")->where("h.id", $hostid)->find();
		if ($host["api_type"] == "normal" && $host["type"] == "dcimcloud") {
			$dcimcloud = new \app\common\logic\DcimCloud();
			$dcimcloud->is_admin = true;
			$result = $dcimcloud->execCustomButton($hostid, $func);
		} else {
			$provision = new \app\common\logic\Provision();
			$result = $provision->execAdminButton($hostid, $func);
			$result["status"] = $result["status"] == "success" || $result["status"] == 200 ? 200 : 406;
		}
		return jsonrule($result);
	}
}