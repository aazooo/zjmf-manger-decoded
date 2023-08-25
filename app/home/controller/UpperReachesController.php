<?php

namespace app\home\controller;

/**
 * @title 上游资源管理模块
 */
class UpperReachesController extends CommonController
{
	/**
	 * @title DCIM客户端重装系统
	 * @description 接口说明:DCIM客户端重装系统
	 * @author xj
	 * @url /upper/dcim_client/reinstall
	 * @method post
	 * @param .name:id type:int require:1  other: desc:资源id
	 * @param   .name:os type:int require:1 desc:操作系统ID
	 * @param   .name:password type:string require:1 desc:密码(六位以上且由大小写字母数字三种组成)
	 * @param   .name:mcon type:int require:0 desc:附加配置ID
	 * @param   .name:port type:int require:1 desc:端口号
	 * @param   .name:part_type type:int require:0 desc:分区类型(windows才有0全盘格式化1第一分区格式化) default:0
	 */
	public function dcimClientReinstall()
	{
		$params = $this->request->param();
		$id = !empty($params["id"]) ? trim($params["id"]) : "";
		$password = !empty($params["password"]) ? trim($params["password"]) : "";
		$os = !empty($params["os"]) ? intval($params["os"]) : 0;
		$port = !empty($params["port"]) ? intval($params["port"]) : 0;
		$part_type = !empty($params["part_type"]) ? intval($params["part_type"]) : 0;
		$re = \think\Db::name("upper_reaches_res")->where("id", $id)->find();
		if (empty($re)) {
			return jsonrule(["status" => 400, "msg" => "没有此资源配置"]);
		}
		if ($re["control_mode"] != "dcim_client") {
			return jsonrule(["status" => 400, "msg" => "资源配置控制方式有误"]);
		}
		$data = ["password" => $password, "os" => $os, "port" => $port, "part_type" => $part_type];
		$UpperReaches = new \app\common\logic\UpperReaches();
		$UpperReaches->is_admin = true;
		$result = $UpperReaches->dcimClientReinstall($re, $data);
		return jsonrule($result);
	}
	/**
	 * @title DCIM客户端破解密码
	 * @description 接口说明:DCIM客户端破解密码
	 * @author xj
	 * @url /upper/dcim_client/crack_pass
	 * @method post
	 * @param .name:id type:int require:1  other: desc:资源id
	 * @param .name:password type:string require:1  other: desc:密码(六位以上且由大小写字母数字三种组成)
	 * @param .name:other_user type:int require:1  other: desc:是否破解其他用户(0:否1:是)
	 * @param .name:user type:string require:0  other: desc:要破解的其他用户名称
	 */
	public function dcimClientCrackPass()
	{
		$params = $this->request->param();
		$id = !empty($params["id"]) ? trim($params["id"]) : "";
		$password = !empty($params["password"]) ? trim($params["password"]) : "";
		$other_user = !empty($params["other_user"]) ? intval($params["other_user"]) : 0;
		$user = !empty($params["user"]) ? trim($params["user"]) : "";
		$re = \think\Db::name("upper_reaches_res")->where("id", $id)->find();
		if (empty($re)) {
			return jsonrule(["status" => 400, "msg" => "没有此资源配置"]);
		}
		if ($re["control_mode"] != "dcim_client") {
			return jsonrule(["status" => 400, "msg" => "资源配置控制方式有误"]);
		}
		$data = ["password" => $password, "other_user" => $other_user, "user" => $user];
		$UpperReaches = new \app\common\logic\UpperReaches();
		$UpperReaches->is_admin = false;
		$result = $UpperReaches->dcimClientCrackPass($re, $data);
		return jsonrule($result);
	}
	/**
	 * @title DCIM客户端取消重装,救援,重置密码
	 * @description 接口说明:DCIM客户端取消重装,救援,重置密码
	 * @author xj
	 * @url /upper/dcim_client/cancel_task
	 * @method post
	 * @param .name:id type:int require:1  other: desc:资源id
	 */
	public function dcimClientCancelReinstall()
	{
		$params = $this->request->param();
		$id = !empty($params["id"]) ? trim($params["id"]) : "";
		$re = \think\Db::name("upper_reaches_res")->where("id", $id)->find();
		if (empty($re)) {
			return jsonrule(["status" => 400, "msg" => "没有此资源配置"]);
		}
		if ($re["control_mode"] != "dcim_client") {
			return jsonrule(["status" => 400, "msg" => "资源配置控制方式有误"]);
		}
		$UpperReaches = new \app\common\logic\UpperReaches();
		$UpperReaches->is_admin = false;
		$result = $UpperReaches->dcimClientCancelReinstall($re);
		return jsonrule($result);
	}
	/**
	 * @title DCIM客户端获取重装,重置密码进度
	 * @description 接口说明:DCIM客户端获取重装,重置密码进度
	 * @author xj
	 * @url /upper/dcim_client/resintall_status
	 * @method post
	 * @param .name:id type:int require:1  other: desc:资源id
	 * @return  disk_check:弹出错误时@
	 * @disk_check  value:disk_part的值
	 * @disk_check  description:描述
	 * @return  error_type:0,1,2,其他(当error_type>0并且progress>=20时弹出磁盘分区错误提示,1Windows磁盘错误,2Windows分区错误,其他Windows磁盘分区提示)
	 * @return  error_msg:当error_type>0时弹出磁盘分区错误提示信息
	 * @return  disk_info:当显示弹出磁盘分区错误提示@
	 * @disk_info  disk:磁盘
	 * @disk_info  part:分区
	 * @disk_info  size:大小
	 * @disk_info  type:类型
	 * @disk_info  windows:类型
	 * @return  progress:进度
	 * @return  windows_finish:是否是windows已完成
	 * @return  hostid:当前产品ID
	 * @return  task_type:类型(0重装系统,1救援系统,2重置密码,3获取硬件信息)
	 * @return  reinstall_msg:重装信息
	 * @return  crackPwd:当有数据返回时,弹出重置密码用户选择@
	 * @crackPwd  user:可选择的用户
	 * @crackPwd  password:重置的密码
	 * @return  step:当前步骤描述
	 * @return  last_result:上次执行结果@
	 * @last_result  act:操作名称
	 * @last_result  status:1成功
	 * @last_result  msg:描述
	 */
	public function dcimClientReinstallStatus()
	{
		$params = $this->request->param();
		$id = !empty($params["id"]) ? trim($params["id"]) : "";
		$re = \think\Db::name("upper_reaches_res")->where("id", $id)->find();
		if (empty($re)) {
			return jsonrule(["status" => 400, "msg" => "没有此资源配置"]);
		}
		if ($re["control_mode"] != "dcim_client") {
			return jsonrule(["status" => 400, "msg" => "资源配置控制方式有误"]);
		}
		$UpperReaches = new \app\common\logic\UpperReaches();
		$UpperReaches->is_admin = false;
		$result = $UpperReaches->dcimClientReinstallStatus($re);
		return jsonrule($result);
	}
	/**
	 * @title DCIM客户端获取操作系统
	 * @description 接口说明:DCIM客户端获取操作系统
	 * @author xj
	 * @url /upper/dcim_client/get_os
	 * @method post
	 * @param .name:id type:int require:1  other: desc:资源id
	 */
	public function dcimClientGetOs()
	{
		$params = $this->request->param();
		$id = !empty($params["id"]) ? trim($params["id"]) : "";
		$re = \think\Db::name("upper_reaches_res")->where("id", $id)->find();
		if (empty($re)) {
			return jsonrule(["status" => 400, "msg" => "没有此资源配置"]);
		}
		if ($re["control_mode"] != "dcim_client") {
			return jsonrule(["status" => 400, "msg" => "资源配置控制方式有误"]);
		}
		$UpperReaches = new \app\common\logic\UpperReaches();
		$UpperReaches->is_admin = false;
		$result = $UpperReaches->dcimClientGetOs($re);
		return jsonrule($result);
	}
}