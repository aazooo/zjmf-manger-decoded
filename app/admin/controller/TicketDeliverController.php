<?php

namespace app\admin\controller;

/**
 * @title 后台工单传递规则
 * @description 接口说明
 */
class TicketDeliverController extends AdminBaseController
{
	/**
	 * @title 添加新规则页面
	 * @description 添加新规则页面
	 * @author xujin
	 * @url         admin/get_ticket_deliver
	 * @method      GET
	 * @time        2020-11-15
	 *  @return id:部门id
	 *  @return name:部门名称
	 */
	public function addPage()
	{
		$data["department"] = \think\Db::name("ticket_department")->field("id,name")->where("is_related_upstream", 1)->select()->toArray();
		$result["status"] = 200;
		$result["msg"] = lang("SUCCESS MESSAGE");
		$result["data"] = $data;
		return jsonrule($result);
	}
	/**
	 * @title 添加新规则
	 * @description 添加新规则
	 * @author xujin
	 * @url         admin/add_ticket_deliver
	 * @method      POST
	 * @time        2020-12-15
	 * @param       .name:departments type:array require:1 default: other: desc:关联的部门
	 * @param       .name:products type:array require:1 default: other: desc:关联的产品
	 * @param       .name:is_open_auto_reply type:int require:0 default: other: desc:开启自动回复
	 * @param       .name:bz type:string require:0 default: other: desc:自动回复内容
	 * @param       .name:mask_keywords type:string require:0 default: other: desc:屏蔽关键字
	 */
	public function add()
	{
		$params = input("post.");
		if (count($params["departments"]) > 0 && is_array($params["departments"])) {
			$departments = \think\Db::name("ticket_department")->field("id")->where("is_related_upstream", 1)->whereIn("id", $params["departments"])->select()->toArray();
			if (count($params["departments"]) != count($departments)) {
				return jsonrule(["status" => 406, "msg" => "部门选择错误"]);
			}
		} else {
			return jsonrule(["status" => 406, "msg" => "未选择部门"]);
		}
		if (count($params["products"]) > 0 && is_array($params["products"])) {
			$products = \think\Db::name("products")->field("id")->where("api_type", "zjmf_api")->whereIn("id", $params["products"])->select()->toArray();
			if (count($params["products"]) != count($products)) {
				return jsonrule(["status" => 406, "msg" => "产品选择错误"]);
			}
		} else {
			return jsonrule(["status" => 406, "msg" => "未选择产品"]);
		}
		$data["is_open_auto_reply"] = !empty($params["is_open_auto_reply"]) ? $params["is_open_auto_reply"] : 0;
		$data["bz"] = !empty($params["bz"]) ? $params["bz"] : "";
		$data["mask_keywords"] = !empty($params["mask_keywords"]) ? $params["mask_keywords"] : "";
		$r = \think\Db::name("ticket_deliver")->insertGetId($data);
		if ($r) {
			$insert = [];
			foreach ($departments as $k => $v) {
				$insert[] = ["tdid" => $r, "dptid" => $v["id"]];
			}
			if (!empty($insert)) {
				\think\Db::name("ticket_deliver_department")->insertAll($insert);
			}
			$insert = [];
			foreach ($products as $k => $v) {
				$insert[] = ["tdid" => $r, "pid" => $v["id"]];
			}
			if (!empty($insert)) {
				\think\Db::name("ticket_deliver_products")->insertAll($insert);
			}
			active_log(sprintf($this->lang["TicketDeliver_admin_add"], $r));
		}
		$result["status"] = 200;
		$result["msg"] = "添加成功";
		return jsonrule($result);
	}
	/**
	 * @title 修改传递规则
	 * @description 修改传递规则
	 * @author xujin
	 * @url         admin/save_ticket_deliver
	 * @method      POST
	 * @time        2020-12-15
	 * @param       .name:id type:int require:1 default: other: desc:规则id
	 * @param       .name:departments type:array require:1 default: other: desc:关联的部门
	 * @param       .name:products type:array require:1 default: other: desc:关联的产品
	 * @param       .name:is_open_auto_reply type:int require:0 default: other: desc:开启自动回复
	 * @param       .name:bz type:string require:0 default: other: desc:自动回复内容
	 * @param       .name:mask_keywords type:string require:0 default: other: desc:屏蔽关键字
	 */
	public function save()
	{
		$params = $this->request->param();
		if (count($params["departments"]) > 0 && is_array($params["departments"])) {
			$departments = \think\Db::name("ticket_department")->field("id")->where("is_related_upstream", 1)->whereIn("id", $params["departments"])->select()->toArray();
			if (count($params["departments"]) != count($departments)) {
				return jsonrule(["status" => 406, "msg" => "部门选择错误"]);
			}
		} else {
			return jsonrule(["status" => 406, "msg" => "未选择部门"]);
		}
		if (count($params["products"]) > 0 && is_array($params["products"])) {
			$products = \think\Db::name("products")->field("id")->where("api_type", "zjmf_api")->whereIn("id", $params["products"])->select()->toArray();
			if (count($params["products"]) != count($products)) {
				return jsonrule(["status" => 406, "msg" => "产品选择错误"]);
			}
		} else {
			return jsonrule(["status" => 406, "msg" => "未选择产品"]);
		}
		$des = "";
		$id = intval($params["id"]);
		$info = \think\Db::name("ticket_deliver")->where("id", $id)->find();
		if (empty($info)) {
			$result["status"] = 406;
			$result["msg"] = "工单传递规则id错误";
			return jsonrule($result);
		}
		\think\Db::name("ticket_deliver_department")->where("tdid", $id)->delete();
		$admins = \think\Db::name("user")->field("id")->whereIn("id", $params["admins"])->select()->toArray();
		$insert = [];
		foreach ($departments as $k => $v) {
			$insert[] = ["tdid" => $id, "dptid" => $v["id"]];
		}
		if (!empty($insert)) {
			\think\Db::name("ticket_deliver_department")->insertAll($insert);
		}
		\think\Db::name("ticket_deliver_products")->where("tdid", $id)->delete();
		$insert = [];
		foreach ($products as $k => $v) {
			$insert[] = ["tdid" => $id, "pid" => $v["id"]];
		}
		if (!empty($insert)) {
			\think\Db::name("ticket_deliver_products")->insertAll($insert);
		}
		$data["is_open_auto_reply"] = !empty($params["is_open_auto_reply"]) ? $params["is_open_auto_reply"] : 0;
		if (isset($params["is_open_auto_reply"]) && $data["is_open_auto_reply"] != $info["is_open_auto_reply"]) {
			if ($data["is_open_auto_reply"] == 1) {
				$des .= "自动回复由“关闭”改为“开启”";
			} else {
				$des .= "自动回复由“开启”改为“关闭”";
			}
		}
		$data["bz"] = !empty($params["bz"]) ? $params["bz"] : "";
		if (isset($params["bz"]) && $data["bz"] != $info["bz"]) {
			if ($data["bz"] != $info["bz"]) {
				$des .= "自动回复内容由“" . $info["bz"] . "”改为“" . $data["bz"] . "”，";
			}
		}
		$data["mask_keywords"] = !empty($params["mask_keywords"]) ? $params["mask_keywords"] : "";
		if (isset($params["mask_keywords"]) && $data["mask_keywords"] != $info["mask_keywords"]) {
			if ($data["mask_keywords"] != $info["mask_keywords"]) {
				$des .= "屏蔽关键字由“" . $info["mask_keywords"] . "”改为“" . $data["mask_keywords"] . "”，";
			}
		}
		$r = \think\Db::name("ticket_deliver")->where("id", $id)->update($data);
		if (empty($desc)) {
			$des .= "没有任何修改";
		}
		active_log(sprintf($this->lang["TicketDeliver_admin_save"], $r, $des));
		$result["status"] = 200;
		$result["msg"] = "修改成功";
		return jsonrule($result);
	}
	/**
	 * @title 删除工单传递规则
	 * @description 删除工单传递规则
	 * @author xujin
	 * @url         admin/delete_ticket_deliver
	 * @method      POST
	 * @time        2020-12-15
	 * @param       .name:id type:int require:1 default: other: desc:规则id
	 * @return      
	 */
	public function delete()
	{
		$id = intval(input("post.id"));
		$info = \think\Db::name("ticket_deliver")->field("id")->where("id", $id)->find();
		if (empty($info)) {
			$result["status"] = 406;
			$result["msg"] = "ID错误";
			return jsonrule($result);
		}
		\think\Db::name("ticket_deliver_department")->where("tdid", $id)->delete();
		\think\Db::name("ticket_deliver_products")->where("tdid", $id)->delete();
		$r = \think\Db::name("ticket_deliver")->where("id", $id)->delete();
		if ($r) {
			active_log("删除工单传递规则成功,ID:{$id}");
		}
		$result["status"] = 200;
		$result["msg"] = "删除成功";
		return jsonrule($result);
	}
	/**
	 * @title 工单传递规则列表
	 * @description 获取工单传递规则列表
	 * @author xujin
	 * @url         admin/list_ticket_deliver
	 * @method      GET
	 * @time        2020-12-15
	 * @param       
	 * @return      .id:工单传递规则id
	 * @return      .is_open_auto_reply:自动回复(0:关,1:开)
	 * @return      .bz:自动回复内容
	 * @return      .mask_keywords:屏蔽关键字
	 * @return      .departments:部门
	 * @return      .products:产品
	 */
	public function getList()
	{
		$data = \think\Db::name("ticket_deliver")->field("id,is_open_auto_reply,bz,mask_keywords")->order("id", "DESC")->select()->toArray();
		$departments = \think\Db::name("ticket_deliver_department")->alias("a")->field("a.id,a.tdid,a.dptid,b.name")->leftJoin("ticket_department b", "a.dptid=b.id")->select()->toArray();
		$products = \think\Db::name("ticket_deliver_products")->alias("a")->field("a.id,a.tdid,a.pid,b.name")->leftJoin("products b", "a.pid=b.id")->select()->toArray();
		foreach ($data as $key => &$value) {
			$value["departments"] = [];
			foreach ($departments as $k => $v) {
				if ($v["tdid"] == $value["id"]) {
					if (empty($v["name"])) {
						$remove_departments[] = $v["id"];
					} else {
						$value["departments"][] = ["id" => $v["dptid"], "name" => $v["name"]];
					}
				}
			}
			$value["products"] = [];
			foreach ($products as $k => $v) {
				if ($v["tdid"] == $value["id"]) {
					if (empty($v["name"])) {
						$remove_products[] = $v["id"];
					} else {
						$value["products"][] = ["id" => $v["pid"], "name" => $v["name"]];
					}
				}
			}
		}
		\think\Db::name("ticket_deliver_department")->whereIn("id", $remove_departments)->delete();
		\think\Db::name("ticket_deliver_products")->whereIn("id", $remove_products)->delete();
		$result["status"] = 200;
		$result["data"] = $data;
		return jsonrule($result);
	}
}