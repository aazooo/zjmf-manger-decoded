<?php

namespace app\admin\controller;

/**
 * @title 后台 用户余额管理
 */
class CreditController extends AdminBaseController
{
	/**
	 * @title 用户余额列表
	 * @description 接口说明:
	 * @author 上官刀
	 * @url /admin/credit
	 * @method get
	 * @param .name:uid type:int require:0  other: desc:
	 * @param .name:page type:int require:0  other: desc:起始页
	 * @param .name:size type:int require:0  other: desc:长度
	 * @return .username:用户名
	 * @throws
	 */
	public function index()
	{
		$data = $this->request->param();
		$order = isset($data["order"][0]) ? trim($data["order"]) : "id";
		$sort = isset($data["sort"][0]) ? trim($data["sort"]) : "DESC";
		$uid = input("uid");
		$page = input("page/d") ?? config("page");
		$page_size = input("size/d") ?? config("limit");
		$en = ["Credit Applied to Invoice", "Credit Applied to Renew Invoice", "Add Funds Invoice", "Credit Removed from Invoice", "Credit from Refund of Invoice", "Upgrade/Downgrade Credit", "Recharge", "Promotion program activity reward", "Credit Applied Invoice"];
		$cn = ["应用余额至账单", "应用余额至续费账单", "用户充值", "从账单移除余额", "账单退款至余额", "增/减余额", "充值至余额", "推介计划活动奖励", "合并账单"];
		$res = \think\Db::name("credit")->where("uid", $uid)->order($order, $sort)->limit($page_size)->page($page)->select()->toArray();
		$count = db("credit")->where("uid", $uid)->count();
		$user = \think\Db::name("clients")->alias("a")->field("username,credit,b.prefix,b.suffix")->leftJoin("currencies b", "a.currency = b.id")->where("a.id", $uid)->find();
		foreach ($res as $key => &$val) {
			$val["detailed"] = $val["balance"];
			if (strpos($val["description"], "Invoice")) {
				preg_match("/\\d+/", $val["description"], $arr);
				$relid = $arr[0];
				$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/bill-detail?id=" . $relid . "&uid=" . $data["uid"] . "\"><span class=\"el-link--inner\">" . $relid . "</span></a>";
				$value = str_replace($relid, $url, $val["description"]);
				$value = str_replace($en, $cn, $value);
				$val["description"] = $value;
			} else {
				$val["description"] = str_replace($en, $cn, $val["description"]);
			}
		}
		return jsonrule(["data" => $res, "count" => $count, "user" => $user, "status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * @title 创建余额
	 * @description 接口说明:
	 * @author 上官刀
	 * @url /admin/credit
	 * @method post
	 * @param .name:uid type:int require:0  other: desc:
	 * @param .name:amount type:int require:0  other: desc: 金额
	 * @param .name:description type:int require:0  other: desc:描述
	 * @throws
	 */
	public function save()
	{
		$uid = input("uid/d");
		$amount = input("amount/f");
		$description = input("description", "", "trim");
		$flag = true;
		\think\Db::startTrans();
		try {
			db("clients")->where("id", $uid)->setInc("credit", $amount);
			credit_log(["uid" => $uid, "amount" => $amount > 0 ? $amount : 0, "desc" => $description ?: "添加余额"]);
			\think\Db::commit();
		} catch (\Exception $e) {
			$flag = false;
			\think\Db::rollback();
		}
		if ($flag) {
			return jsonrule(["status" => 200, "msg" => lang("ADD SUCCESS")]);
		} else {
			return jsonrule(["status" => 400, "msg" => lang("ADD FAIL")]);
		}
	}
	/**
	 * @title 余额详情
	 * @description 接口说明:
	 * @author 上官刀
	 * @url /admin/credit/:id
	 * @method get
	 * @param .name:id type:int require:0  other: desc:余额id
	 * @throws
	 */
	public function read($id)
	{
		$res = db("credit")->where("id", intval($id))->find();
		return jsonrule(["data" => $res, "status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * @title 更新余额
	 * @description 接口说明:
	 * @author 上官刀
	 * @url /admin/credit/:id
	 * @method put
	 * @param .name:id type:int require:0  other: desc:余额id
	 * @param .name:description type:int require:0  other: desc:余额id
	 * @throws
	 */
	public function update($id)
	{
		$description = input("description", "", "htmlspecialchars");
		$data = ["description" => $description];
		$res = db("credit")->where("id", $id)->update($data);
		if ($res) {
			return jsonrule(["data" => $res, "status" => 200, "msg" => lang("UPDATE SUCCESS")]);
		} else {
			return jsonrule(["status" => 400, "msg" => lang("UPDATE SUCCESS")]);
		}
	}
	/**
	 * @title 减少余额
	 * @description 接口说明:
	 * @author wyh
	 * @url /admin/credit/reduce
	 * @method post
	 * @param .name:uid type:int require:0  other: desc:客户ID
	 * @param .name:amount type:int require:0  other: desc:金额
	 * @throws
	 */
	public function reduce()
	{
		$uid = input("uid/d");
		$amount = input("amount/f");
		$description = input("description", "", "trim");
		\think\Db::startTrans();
		try {
			db("clients")->where("id", $uid)->setDec("credit", $amount);
			credit_log(["uid" => $uid, "amount" => $amount > 0 ? -$amount : 0, "desc" => $description ?: "减少余额"]);
			\think\Db::commit();
		} catch (\Exception $e) {
			\think\Db::rollback();
			return jsonrule(["status" => 400, "msg" => lang("ADD FAIL")]);
		}
		return jsonrule(["status" => 200, "msg" => lang("ADD SUCCESS")]);
	}
	/**
	 * @title 删除余额
	 * @description 接口说明:
	 * @author 上官刀
	 * @url /admin/credit/:id
	 * @method delete
	 * @param .name:id type:int require:0  other: desc:余额id
	 * @throws
	 */
	public function delete($id)
	{
		$res = db("credit")->delete($id);
		if ($res) {
			return jsonrule(["status" => 200, "msg" => lang("DELETE SUCCESS")]);
		} else {
			return jsonrule(["status" => 400, "msg" => lang("DELETE FAIL")]);
		}
	}
}