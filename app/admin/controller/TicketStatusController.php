<?php

namespace app\admin\controller;

/**
 * @title 后台工单状态
 * @description 接口说明
 */
class TicketStatusController extends AdminBaseController
{
	protected $default = ["Open", "Answered", "CustomerReply", "Closed"];
	/**
	 * @title 添加工单状态
	 * @description 添加工单状态
	 * @author huanghao
	 * @url         admin/add_ticket_status
	 * @method      POST
	 * @time        2019-11-25
	 * @param       .name:title type:string require:1 default: other: desc:状态标题
	 * @param       .name:color type:string require:0 default: other: desc:颜色css代码
	 * @param       .name:show_active type:int require:0 default:0 other: desc:包括打开的工单
	 * @param       .name:show_await type:int require:0 default:0 other: desc:包括等待回复
	 * @param       .name:auto_close type:int require:0 default:0 other: desc:确定自动关闭
	 * @param       .name:order type:int require:0 default: other:1 desc:排序
	 */
	public function add()
	{
		$params = input("post.");
		$rule = ["title" => "require", "order" => "number"];
		$msg = ["name.require" => "标题不能为空", "order.number" => "排序只能是数字"];
		$validate = new \think\Validate($rule, $msg);
		$validate_result = $validate->check($params);
		if (!$validate_result) {
			return jsonrule(["status" => 406, "msg" => $validate->getError()]);
		}
		$title = trim($params["title"]);
		$exist = \think\Db::name("ticket_status")->where("title")->find();
		if (!empty($exist)) {
			$result["status"] = 200;
			$result["msg"] = "该状态已存在";
			return jsonrule($result);
		}
		$data["title"] = $title;
		$data["color"] = $params["color"] ?: "";
		$data["show_active"] = !empty($params["show_active"]) ? 1 : 0;
		$data["show_await"] = !empty($params["show_await"]) ? 1 : 0;
		$data["auto_close"] = !empty($params["auto_close"]) ? 1 : 0;
		$data["order"] = isset($params["order"]) ? intval($params["order"]) : 0;
		$r = \think\Db::name("ticket_status")->insertGetId($data);
		if ($r) {
			active_log("添加工单状态成功 - 标题:{$data["title"]} - ID:{$r}");
		}
		$result["status"] = 200;
		$result["msg"] = "添加成功";
		return jsonrule($result);
	}
	/**
	 * @title 添加工单状态
	 * @description 添加工单状态
	 * @author huanghao
	 * @url         admin/save_ticket_status
	 * @method      POST
	 * @time        2019-11-25
	 * @param       .name:id type:int require:1 default: other: desc:状态id
	 * @param       .name:title type:string require:1 default: other: desc:状态标题
	 * @param       .name:color type:string require:0 default: other: desc:颜色css代码
	 * @param       .name:show_active type:int require:0 default:0 other: desc:包括打开的工单
	 * @param       .name:show_await type:int require:0 default:0 other: desc:包括等待回复
	 * @param       .name:auto_close type:int require:0 default:0 other: desc:确定自动关闭
	 * @param       .name:order type:int require:0 default: other:1 desc:排序
	 */
	public function save()
	{
		$params = input("post.");
		$rule = ["title" => "require", "order" => "number"];
		$msg = ["name.require" => "标题不能为空", "order.number" => "排序只能是数字"];
		$validate = new \think\Validate($rule, $msg);
		$validate_result = $validate->check($params);
		if (!$validate_result) {
			return jsonrule(["status" => 406, "msg" => $validate->getError()]);
		}
		$id = intval($params["id"]);
		if ($id <= 5) {
			unset($params["title"]);
			unset($params["color"]);
		}
		$info = \think\Db::name("ticket_status")->where("id", $id)->find();
		if (empty($info)) {
			$result["status"] = 406;
			$result["msg"] = "ID错误";
			return jsonrule($result);
		}
		$data = [];
		$des = "";
		if (!empty($params["title"])) {
			$data["title"] = $params["title"] ?: "";
			if ($data["title"] != $info["title"]) {
				$des .= "状态标题由“" . $info["title"] . "”改为“" . $data["title"] . "”，";
			}
		}
		if (!empty($params["color"])) {
			$data["color"] = $params["color"] ?: "";
			if ($data["color"] != $info["color"]) {
				$des .= "状态染色由“" . $info["color"] . "”改为“" . $data["color"] . "”，";
			}
		}
		$arr = ["show_active", "show_await", "auto_close"];
		foreach ($arr as $v) {
			if (isset($params[$v])) {
				$data[$v] = !empty($params[$v]) ? 1 : 0;
			}
		}
		if (!empty($params["order"])) {
			$data["order"] = intval($params["order"]);
			if ($data["order"] != $info["order"]) {
				$des .= "排序由“" . $info["order"] . "”改为“" . $data["order"] . "”，";
			}
		}
		if (!empty($data)) {
			$r = \think\Db::name("ticket_status")->where("id", $id)->update($data);
		}
		if (empty($des)) {
			$des .= "什么都没有修改";
		}
		active_log(sprintf($this->lang["TicketDepartmentStatus_admin_save"], $id, $info["title"], $des));
		$result["status"] = 200;
		$result["msg"] = "修改成功";
		return jsonrule($result);
	}
	/**
	 * @title 删除工单状态
	 * @description 删除工单状态
	 * @author huanghao
	 * @url         admin/delete_ticket_status
	 * @method      POST
	 * @time        2019-11-25
	 * @param       .name:id type:int require:1 default: other: desc:工单状态id
	 * @return      
	 */
	public function delete()
	{
		$id = intval(input("post.id"));
		if ($id <= 5) {
			$result["status"] = 401;
			$result["msg"] = lang("TICKET_STATUS_NOT_DELETE");
			return jsonrule($result);
		}
		$info = \think\Db::name("ticket_status")->field("id,title")->where("id", $id)->find();
		if (empty($info)) {
			$result["status"] = 406;
			$result["msg"] = "ID错误";
			return jsonrule($result);
		}
		$r = \think\Db::name("ticket_status")->where("id", $id)->delete();
		if ($r) {
			\think\Db::name("ticket")->where("status", $info["id"])->update(["status" => 4]);
			active_log("删除工单状态成功 - 标题:{$info["title"]} - ID:{$id}");
		}
		$result["status"] = 200;
		$result["msg"] = "删除成功";
		return jsonrule($result);
	}
	/**
	 * @title 工单状态列表
	 * @description 工单状态列表
	 * @author huanghao
	 * @url         admin/list_ticket_status
	 * @method      GET
	 * @time        2019-11-25
	 * @param       
	 * @return      .id:工单状态id
	 * @return      .title:标题
	 * @return      .color:颜色
	 * @return      .show_active:包括打开的工单
	 * @return      .show_await:包括等待回复
	 * @return      .auto_close:自动关闭
	 * @return      .order:排序
	 */
	public function getList()
	{
		$data = \think\Db::name("ticket_status")->order("order", "asc")->select();
		$result["status"] = 200;
		$result["data"] = $data;
		return jsonrule($result);
	}
	/**
	 * @title 工单状态详情
	 * @description 工单状态详情
	 * @author huanghao
	 * @url         admin/list_ticket_status/:id
	 * @method      GET
	 * @time        2019-11-25
	 * @param       .name:id type:int require:1 default: other: desc:状态id
	 * @return      .id:工单状态id
	 * @return      .title:标题
	 * @return      .color:颜色
	 * @return      .show_active:包括打开的工单
	 * @return      .show_await:包括等待回复
	 * @return      .auto_close:自动关闭
	 * @return      .order:排序
	 */
	public function getDetail($id)
	{
		$id = intval($id);
		$data = \think\Db::name("ticket_status")->where("id", $id)->find();
		if (empty($data)) {
			$result["status"] = 406;
			$result["msg"] = "ID错误";
			return jsonrule($result);
		}
		$result["status"] = 200;
		$result["data"] = $data;
		return jsonrule($result);
	}
}