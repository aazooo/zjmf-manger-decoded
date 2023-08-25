<?php

namespace app\home\controller;

/**
 * @title bot前台
 */
class InterflowController extends CommonController
{
	/**
	 * @title 获取配置绑定设置
	 * @description
	 * @url /interflow/accountbind
	 * @method  get
	 * @return list
	 * @return .id:记录ID
	 * @return .i_type:交互类型
	 * @return .uid:用户ID
	 * @return .i_account:交互账号
	 * @return .security_verify:敏感开关
	 * @return .security_code:敏感安全码
	 * @return .is_bind:是否绑定
	 * @return .create_time:创建时间
	 * @return .update_time:最近修改时间
	 */
	public function interflowAccountBindInfo()
	{
		$params = $this->request->param();
		$data = \think\Db::name("robot_clients")->field("qq")->where("uid", $params["uid"])->find();
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data ?: []]);
	}
	/**
	 * @title 保存配置绑定设置
	 * @description 请求参数格式i_data[i_type][.name] 例：i_data[Qq][i_type] = Qq ... i_data[Wx][i_type] = Wx ...）
	 * @url /interflow/accountbind
	 * @method  POST
	 * @param   .name:i_data[Qq][id] type:string require:1 default: desc:记录ID(无id是添加,有id是修改)
	 * @param   .name:i_data[Qq][i_type] type:string require:1 default: desc:交互类型
	 * @param   .name:i_data[Qq][i_account] type:int require:1 default: desc:交互账号
	 * @param   .name:i_data[Qq][security_verify] type:int require:1 default: 0 desc:敏感开关
	 * @param   .name:i_data[Qq][security_code] type:string require:1 default: desc:敏感安全码
	 * @return  json
	 */
	public function interflowAccountBind()
	{
		$params = $this->request->param();
		$data = \think\Db::name("robot_clients")->where("uid", $params["uid"])->find();
		$robot_clients["uid"] = $params["uid"];
		$robot_clients["qq"] = trim(str_replace("，", ",", $params["qq"]), ",") . ",";
		if ($data) {
			$keyword["update_time"] = time();
			\think\Db::name("robot_clients")->where(["uid" => $params["uid"]])->update($robot_clients);
		} else {
			$keyword["create_time"] = time();
			\think\Db::name("robot_clients")->insert($robot_clients);
		}
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => []]);
	}
	/**
	 * @title 解除账号绑定
	 * @description
	 * @url /interflow/accountbind
	 * @method  detele
	 * @param   .name:id type:string require:1 default: desc:记录ID
	 * @return
	 */
	public function interflowAccountBindRelieve()
	{
		$params = $this->request->param();
		$mode = new \app\home\model\InterflowClientsModel();
		$mode->deleteData("id", $params["id"], ["uid", $params["uid"]]);
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => []]);
	}
}