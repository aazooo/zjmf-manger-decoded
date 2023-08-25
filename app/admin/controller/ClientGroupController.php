<?php

namespace app\admin\controller;

/**
 * @title 后台用户分组
 */
class ClientGroupController extends AdminBaseController
{
	/**
	 * @title 分组列表
	 * @description 接口说明:
	 * @author 上官🔪
	 * @url admin/client_group
	 * @method get
	 * @return id:
	 * @return group_name:  名称
	 * @return group_colour:  组颜色
	 * @return discount_percent:  折扣百分比
	 * @return susptermexempt:  暂停/删除豁免权(1是0否)
	 * @return separateinvoices:拆分服务账单
	 * @throws
	 **/
	public function index()
	{
		$data = $this->request->param();
		$order = isset($data["order"]) ? trim($data["order"]) : "id";
		$sort = isset($data["sort"]) ? trim($data["sort"]) : "DESC";
		$res = db("client_groups")->order($order, $sort)->select();
		return jsonrule(["data" => $res, "status" => 200]);
	}
	/**
	 * 显示创建资源表单页.
	 *
	 * @return \think\Response
	 */
	public function create()
	{
	}
	/**
	 * @title 创建用户组
	 * @description 接口说明:
	 * @author 上官🔪
	 * @url admin/client_group
	 * @method post
	 * @param .name:group_name type:string require:1  other: desc:名称
	 * @param .name:group_colour type:string require:0  other: desc:组颜色
	 * @param .name:discount_percent type:int require:1  other: desc:折扣百分比
	 * @param .name:susptermexempt type:int require:1  other: desc:暂停/删除豁免权(1是0否)
	 * @param .name:separateinvoices type:int require:0  other: desc:拆分服务账单
	 * @return
	 * @throws
	 **/
	public function save(\think\Request $request)
	{
		$param = $request->only(["group_name", "group_colour", "discount_percent", "susptermexempt", "separateinvoices"]);
		$res = db("client_groups")->insert($param);
		active_log(sprintf($this->lang["ClientGroup_admin_add"], $param["group_name"], $res));
		if ($res) {
			return jsonrule(["msg" => "ok", "status" => 201], 201);
		} else {
			return jsonrule(["msg" => "error", "status" => 400]);
		}
	}
	/**
	 * @title 组详情
	 * @description 接口说明:
	 * @author 上官🔪
	 * @url admin/client_group/id
	 * @method get
	 * @param .name:id type:int require:0  other: desc:
	 * @return group_name:  名称
	 * @return group_colour:  组颜色
	 * @return discount_percent:  折扣百分比
	 * @return susptermexempt:  暂停/删除豁免权(1是0否)
	 * @return separateinvoices:拆分服务账单
	 * @throws
	 **/
	public function read(int $id)
	{
		$res = db("client_groups")->get($id);
		return jsonrule(["data" => $res, "status" => 200]);
	}
	/**
	 * 显示编辑资源表单页.
	 *
	 * @param  int  $id
	 * @return \think\Response
	 */
	public function edit($id)
	{
	}
	/**
	 * @title 更新组
	 * @description 接口说明:
	 * @author 上官🔪
	 * @url admin/client_group/:id
	 * @method put
	 * @param .name:id type:int require:1  other: desc:
	 * @param .name:group_name type:string require:1  other: desc:名称
	 * @param .name:group_colour type:string require:1  other: desc:组颜色
	 * @param .name:discount_percent type:int require:1  other: desc:折扣百分比
	 * @param .name:susptermexempt type:int require:1  other: desc:暂停/删除豁免权(1是0否)
	 * @param .name:separateinvoices type:int require:1  other: desc:拆分服务账单(1是0否)
	 * @return
	 * @throws
	 **/
	public function update($id)
	{
		$params = $this->request->only("id,group_name,group_colour,discount_percent,susptermexempt,separateinvoices");
		$rule = ["group_name" => "require", "group_colour" => "require", "discount_percent" => "require|number", "susptermexempt" => "require|in:0,1", "separateinvoices" => "require|in:0,1"];
		$msg = ["group_name.require" => "客户组名称不能为空", "group_colour.require" => "组颜色不能为空", "discount_percent.require" => "折扣百分比不能为空", "susptermexempt.require" => "暂停/删除豁免权不能为空", "separateinvoices.require" => "拆分服务账单不能为空"];
		$validate = new \think\Validate($rule, $msg);
		$validate_result = $validate->check($params);
		if (!$validate_result) {
			return jsonrule(["status" => 406, "msg" => $validate->getError()]);
		}
		$cg = db("client_groups")->where("id", $id)->find();
		$res = db("client_groups")->where("id", $id)->update($params);
		$dec = "";
		if ($cg["group_name"] != $params["group_name"]) {
			$dec .= "客户分组名称由“" . $cg["group_name"] . "”改为“" . $params["group_name"] . ",”";
		}
		if ($cg["group_colour"] != $params["group_colour"]) {
			$dec .= "客户分组颜色由“" . $cg["group_colour"] . "”改为“" . $params["group_colour"] . ",”";
		}
		if (empty($dec)) {
			$dec .= "未做任何修改";
		}
		active_log(sprintf($this->lang["ClientGroup_admin_update"], $id, $dec));
		unset($dec);
		return jsonrule(["msg" => "ok", "status" => 203]);
	}
	/**
	 * @title 删除组
	 * @description
	 * @author 上官🔪
	 * @url admin/client_group/id
	 * @method delete
	 * @param name:id type:int require:1 default:1 other: desc:唯一ID
	 * @return
	 * @throws
	 **/
	public function delete($id)
	{
		$count = \think\Db::name("clients")->where("groupid", $id)->count();
		if ($count > 0) {
			return jsonrule(["status" => 400, "msg" => lang("此用户组存在用户,不可删除")]);
		}
		$cg = db("client_groups")->where("id", $id)->find();
		db("client_groups")->delete($id);
		active_log(sprintf($this->lang["ClientGroup_admin_delete"], $cg["group_name"], $id));
		return jsonrule(["msg" => lang("DELETE SUCCESS"), "status" => 200]);
	}
}