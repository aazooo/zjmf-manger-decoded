<?php

namespace app\admin\controller;

/**
 * @title 后台取消请求页面
 * @description 接口说明
 */
class CancelRequestController extends AdminBaseController
{
	/**
	 * @title 获取当前待审核的取消请求
	 * @description 接口说明:获取当前待审核的取消请求
	 * @author 萧十一郎
	 * @url /admin/cancel_request/list
	 * @method GET
	 * @return  id:ID
	 * @return  relid:主机id
	 * @return  type:立即取消(Immediate),等待账单周期结束(Endofbilling)
	 * @return  reason:取消原因
	 * @return  username:用户名
	 * @return  hostid:主机id
	 * @return  uid:用户id
	 * @return  domainstatus:主机状态
	 * @return  nextduedate:到期时间
	 * @return  productname:产品名称
	 * @return  groupname:组名称
	 * @return  type_desc:显示类型描述
	 * @return  product_desc:显示产品描述
	 */
	public function getList()
	{
		$data = $this->request->param();
		$order = isset($data["order"]) ? trim($data["order"]) : "c.id";
		$sort = isset($data["sort"]) ? trim($data["sort"]) : "DESC";
		$data = \think\Db::name("cancel_requests")->field("c.id,c.relid,c.type,c.reason,cl.username,h.id as hostid,h.uid,h.domainstatus,h.nextduedate,
                    h.auto_terminate_end_cycle, h.auto_terminate_reason,p.name as productname, 
                    g.name as groupname")->alias("c")->leftJoin("host h", "h.id=c.relid")->leftJoin("clients cl", "cl.id=h.uid")->leftJoin("products p", "p.id=h.productid")->leftJoin("product_groups g", "g.id=p.gid")->order($order, $sort)->select()->toArray();
		foreach ($data as $key => $val) {
			if ($val["domainstatus"] == "Cancelled") {
				unset($data[$key]);
				continue;
			}
			$product_desc = "";
			$product_desc .= $val["groupname"] . " - " . $val["productname"];
			if ($val["type"] == "Immediate") {
				$type_desc = "立即停用";
			} else {
				$type_desc = "到期时停用" . PHP_EOL . "(" . date("Y-m-d", $val["nextduedate"]) . ")";
			}
			$val["product_desc"] = $product_desc;
			$val["type_desc"] = $type_desc;
		}
		$data = $data ?: [];
		return jsonrule(["status" => 200, "data" => $data]);
	}
	/**
	 * @title 删除取消请求
	 * @description 接口说明:删除取消请求
	 * @author 萧十一郎
	 * @url /admin/cancel_request/list
	 * @method delete
	 * @return  id:ID
	 */
	public function deleteList(\think\Request $request)
	{
		$id = intval($request->id);
		if (!empty($id)) {
			\think\Db::name("cancel_requests")->delete($id);
			return jsonrule(["status" => 200, "msg" => "删除成功"]);
		}
	}
	/**
	 * @title 获取已被取消的列表
	 * @description 接口说明:获取已被取消的列表
	 * @author 萧十一郎
	 * @url /admin/cancel_request/cancellist
	 * @method GET
	 * @return  id:ID
	 * @return  relid:主机id
	 * @return  type:立即取消(Immediate),等待账单周期结束(Endofbilling)
	 * @return  reason:取消原因
	 * @return  username:用户名
	 * @return  hostid:主机id
	 * @return  uid:用户id
	 * @return  domainstatus:主机状态
	 * @return  nextduedate:到期时间
	 * @return  productname:产品名称
	 * @return  groupname:组名称
	 * @return  type_desc:显示类型描述
	 * @return  product_desc:显示产品描述
	 */
	public function getCancelList()
	{
		$data = \think\Db::name("cancel_requests")->field("c.id,c.relid,c.type,c.reason,cl.username,h.id as hostid,h.uid,h.domainstatus,h.nextduedate,
                h.auto_terminate_end_cycle, h.auto_terminate_reason,p.name as productname, 
                g.name as groupname")->alias("c")->leftJoin("host h", "h.id=c.relid")->leftJoin("clients cl", "cl.id=h.uid")->leftJoin("products p", "p.id=h.productid")->leftJoin("product_groups g", "g.id=p.gid")->select()->toArray();
		foreach ($data as $key => $val) {
			if ($val["domainstatus"] != "Cancelled") {
				unset($data[$key]);
				continue;
			}
			$product_desc = "";
			$product_desc .= $val["groupname"] . " - " . $val["productname"];
			if ($val["type"] == "Immediate") {
				$type_desc = "立即停用";
			} else {
				$type_desc = "到期时停用" . PHP_EOL . "(" . date("Y-m-d", $val["nextduedate"]) . ")";
			}
			$val["product_desc"] = $product_desc;
			$val["type_desc"] = $type_desc;
		}
		$data = $data ?: [];
		return jsonrule(["status" => 200, "data" => $data]);
	}
}