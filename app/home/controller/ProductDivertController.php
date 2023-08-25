<?php

namespace app\home\controller;

/**
 * @title 产品转移前台功能
 * @description 接口说明: 产品转移模块
 */
class ProductDivertController extends ViewBaseController
{
	/**
	 * @title 产品转移根据邮箱或手机号获取用户信息
	 * @description 接口说明:
	 * @author
	 * @url product_divert/postNameToUser
	 * @method POST
	 * @param .name:tranfer_name type:string require:1 default: other: desc:查找到邮箱或者手机号
	 * @return id:用户id(调用转移时需要)
	 * @return username:用户名(展示给客户确认)
	 */
	public function postNameToUser(\think\Request $request)
	{
		$Host = controller("Host");
		$res = $Host->postNameToUser($request);
		return $res;
	}
	public function divertInvoice($param, $divert)
	{
		$uid = $param["uid"];
		if ($param["type"] == "PUSH") {
			$divert_cost = $divert["push_cost"];
			$invoice_obj = "push_invoice_id";
		} elseif ($param["type"] == "PULL") {
			$divert_cost = $divert["pull_cost"];
			$invoice_obj = "pull_invoice_id";
		} else {
			throw new \think\Exception("账单创建方式不存在");
		}
		$inc_data = ["uid" => $uid, "create_time" => time(), "due_time" => time(), "subtotal" => $divert_cost, "total" => $divert_cost, "status" => "Unpaid", "type" => "transfer_fee"];
		$item_data = ["uid" => $uid, "rel_id" => $divert["hostid"], "type" => "transfer_fee", "description" => "描述", "amount" => $divert_cost, "due_time" => strtotime("+365 day")];
		\think\Db::startTrans();
		try {
			$inc_id = \think\Db::name("invoices")->insertGetId($inc_data);
			$item_data["invoice_id"] = $inc_id;
			\think\Db::name("invoice_items")->insert($item_data);
			\think\Db::name("product_divert")->where(["id" => $param["product_divert_id"]])->update([$invoice_obj => $inc_id]);
			\think\Db::commit();
		} catch (\Exception $e) {
			\think\Db::rollback();
			throw new \think\Exception($e->getMessage());
		}
		return ["status" => 200, "data" => 0];
	}
	/**
	 * @title 检测支付状态
	 * @description 接口说明:
	 * @author
	 * @url /
	 * @method GET
	 * @param .name:pd_id type:int require:1 default: other: desc:转移记录id
	 * @return
	 */
	public function checkPayStatus(\think\Request $request)
	{
		$param = $request->param();
		$res = \think\Db::name("product_divert")->alias("p")->leftJoin("invoices i", "p.pull_invoice_id=i.id")->where(["p.id" => $param["id"], "p.pull_userid" => $param["uid"]])->field("i.status as pay_status,p.hostid")->find();
		if (!$res["pay_status"] == "Paid") {
			return json(["status" => 200, "data" => 0]);
		}
		\think\Db::startTrans();
		try {
			\think\Db::name("product_divert")->where(["id" => $param["id"], "pull_userid" => $param["uid"]])->update(["status" => 2]);
			\think\Db::name("host")->where(["id" => $res["hostid"]])->update(["uid" => $param["uid"]]);
			\think\Db::commit();
		} catch (\Exception $e) {
			\think\Db::rollback();
		}
		return json(["status" => 200, "data" => 1]);
	}
}