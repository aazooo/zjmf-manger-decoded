<?php

namespace app\home\controller;

/**
 * @title 前台用户
 * @description 接口说明
 */
class UserAffiliateController extends CommonController
{
	/**
	 * @title 推荐计划页面
	 * @description 接口说明:
	 * @author lgd
	 * @url /affpage
	 * @method get
	 */
	public function affpage(\think\Request $request)
	{
		$id = $request->uid;
		$userinfo = db("clients")->field("activation")->where("id", $id)->find();
		return json(["status" => 200, "aff" => $userinfo["activation"], "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * @title 推荐计划激活
	 * @description 接口说明:
	 * @author lgd
	 * @url /activation
	 * @method get
	 */
	public function activation(\think\Request $request)
	{
		$id = $request->uid;
		if (configuration("affiliate_enabled") != 1) {
			return json(["status" => 400, "msg" => "未开启推介计划"]);
		}
		$userinfo = db("clients")->field("activation")->where("id", $id)->find();
		$affiliate_url = $request->domain() . "/aff/";
		if ($userinfo["activation"] == 1) {
			$aid = \think\Db::name("affiliates")->field("id,url_identy")->where("uid", $id)->find();
			if (empty($aid)) {
				db("clients")->where("id", $id)->update(["activation" => 0]);
				return json(["status" => 400, "msg" => "请重新激活"]);
			}
			$url = $affiliate_url . $aid["url_identy"];
			return json(["status" => 400, "url" => $url, "msg" => "你已经激活,无需再激活"]);
		} else {
			$aid = \think\Db::name("affiliates")->field("id")->where("uid", $id)->find();
			if (!empty($aid)) {
				db("clients")->where("id", $id)->update(["activation" => 1]);
				return json(["status" => 400, "msg" => "你已经激活,无需再激活"]);
			}
			$data = ["date" => time(), "uid" => $id, "created_time" => time()];
			$data1 = ["uid" => $id, "create_time" => time()];
			$affiliates_user_temp = \think\Db::name("affiliates_user_temp")->where("affid_uid", $id)->select()->toArray();
			if (is_array($affiliates_user_temp)) {
				$data["registcount"] = count($affiliates_user_temp);
			}
			$aid = \think\Db::name("affiliates")->insertGetId($data);
			$res = \think\Db::name("affiliates_user_setting")->insertGetId($data1);
			$affiliate_bonusde_posit = configuration("affiliate_bonusde_posit");
			if (is_array($affiliates_user_temp) && count($affiliates_user_temp) > 0) {
				foreach ($affiliates_user_temp as $user_temp) {
					$affiliates_user[] = ["uid" => $user_temp["uid"], "affid" => $aid, "create_time" => time()];
				}
				if (is_array($affiliates_user_temp) && count($affiliates_user_temp) > 0) {
					$res = \think\Db::name("affiliates_user")->insertAll($affiliates_user);
				}
				\think\Db::name("affiliates_user_temp")->where("affid_uid", $id)->delete();
			}
			$code = getRandch(8);
			$aff = \think\Db::name("affiliates")->field("uid,balance")->where("id", $aid)->find();
			\think\Db::name("affiliates")->where("id", $aid)->update(["balance" => bcadd($aff["balance"], $affiliate_bonusde_posit, 2), "url_identy" => $code]);
			Db("clients")->where("id", $id)->update(["activation" => 1]);
			$clients = Db("clients")->where("id", $aff["uid"])->find();
			active_logs(sprintf($this->lang["Aff_home_activation"], $clients["id"], $clients["username"], $id), $id);
			active_logs(sprintf($this->lang["Aff_home_activation"], $clients["id"], $clients["username"], $id), $id, "", 2);
			$url = $affiliate_url . $code;
			hook("affiliate_activation", ["uid" => $id, "affid" => $aid]);
			return json(["status" => 200, "url" => $url, "msg" => "激活成功"]);
		}
	}
	/**
	 * @title 推荐计划用户激活页面
	 * @description 接口说明:
	 * @author lgd
	 * @url /affindex
	 * @method get
	 * @return data:基础数据@
	 * @data  visitors:访问数量
	 * @data  registcount:注册数量
	 * @data  url:推荐链接
	 * @data  payamount:购买数量
	 * @data  balance:可提现佣金
	 * @data  withdrawn:已提现佣金
	 * @data  withdrawn:冻结金额
	 * @data  withdrawn:已提现佣金
	 * @data  withdrawn:已提现佣金
	 */
	public function affindex(\think\Request $request)
	{
		$id = $request->uid;
		$data = \think\Db::name("affiliates")->alias("a")->join("clients c", "c.id=a.uid")->leftJoin("currencies cu", "cu.id = c.currency")->field("a.*,cu.suffix,cu.prefix")->where("uid", $id)->find();
		if (empty($data["url_identy"])) {
			$code = getRandch(8);
			\think\Db::name("affiliates")->where("id", $data["id"])->update(["url_identy" => $code]);
			$data["url_identy"] = $code;
		}
		$affiliate_url = $request->domain() . "/aff/";
		$data["url"] = $affiliate_url . $data["url_identy"];
		$affiliate_withdraw = configuration("affiliate_withdraw");
		$flag = true;
		if ($data["balance"] < $affiliate_withdraw) {
			$flag = false;
		}
		$aus = \think\Db::name("affiliates_user_setting")->where("uid", $id)->find();
		$data1 = [];
		if ($aus["affiliate_enabled"] == 1) {
			$affiliate_type = $aus["affiliate_type"];
			if ($affiliate_type == 1) {
				$data1[] = ["affiliate_name" => "推介收益", "affiliate_des" => "推介的用户注册购买产品后返佣金额", "affiliate_type" => "固定金额", "affiliate_bates" => $data["prefix"] . sprintf("%.2f", $aus["affiliate_bates"])];
			} else {
				$data1[] = ["affiliate_name" => "推介收益", "affiliate_des" => "推介的用户注册购买产品后返佣金额", "affiliate_type" => "百分比", "affiliate_bates" => $aus["affiliate_bates"] . "%"];
			}
		} else {
			$data["affiliate_enabled"] = configuration("affiliate_enabled");
			if ($data["affiliate_enabled"]) {
				$affiliate_type = configuration("affiliate_type");
				if ($affiliate_type == 1) {
					$data1[] = ["affiliate_name" => "推介收益", "affiliate_des" => "推介的用户注册购买产品后返佣金额", "affiliate_type" => "固定金额", "affiliate_bates" => $data["prefix"] . sprintf("%.2f", configuration("affiliate_bates"))];
				} else {
					$data1[] = ["affiliate_name" => "推介收益", "affiliate_des" => "推介的用户注册购买产品后返佣金额", "affiliate_type" => "百分比", "affiliate_bates" => configuration("affiliate_bates") . "%"];
				}
			}
		}
		if ($aus["affiliate_is_renew"] == 1) {
			$affiliate_type = $aus["affiliate_renew_type"];
			if ($affiliate_type == 1) {
				$data1[] = ["affiliate_name" => "续费收益", "affiliate_des" => "推介的用户产品续费后返佣金额", "affiliate_type" => "固定金额", "affiliate_bates" => $data["prefix"] . sprintf("%.2f", $aus["affiliate_renew"])];
			} else {
				$data1[] = ["affiliate_name" => "续费收益", "affiliate_des" => "推介的用户产品续费后返佣金额", "affiliate_type" => "百分比", "affiliate_bates" => $aus["affiliate_renew"] . "%"];
			}
		} else {
			$data["affiliate_is_renew"] = configuration("affiliate_is_renew");
			if ($data["affiliate_is_renew"]) {
				$affiliate_type = configuration("affiliate_renew_type");
				if ($affiliate_type == 1) {
					$data1[] = ["affiliate_name" => "续费收益", "affiliate_des" => "推介的用户产品续费后返佣金额", "affiliate_type" => "固定金额", "affiliate_bates" => $data["prefix"] . sprintf("%.2f", configuration("affiliate_renew"))];
				} else {
					$data1[] = ["affiliate_name" => "续费收益", "affiliate_des" => "推介的用户产品续费后返佣金额", "affiliate_type" => "百分比", "affiliate_bates" => configuration("affiliate_renew") . "%"];
				}
			}
		}
		if ($aus["affiliate_is_reorder"] == 1) {
			$affiliate_type = $aus["affiliate_reorder_type"];
			if ($affiliate_type == 1) {
				$data1[] = ["affiliate_name" => "二次购买", "affiliate_des" => "推介的用户二次购买产品后返佣金额", "affiliate_type" => "固定金额", "affiliate_bates" => $data["prefix"] . sprintf("%.2f", $aus["affiliate_reorder"])];
			} else {
				$data1[] = ["affiliate_name" => "二次购买", "affiliate_des" => "推介的用户二次购买产品后返佣金额", "affiliate_type" => "百分比", "affiliate_bates" => $aus["affiliate_reorder"] . "%"];
			}
		} else {
			$data["affiliate_is_reorder"] = configuration("affiliate_is_reorder");
			if ($data["affiliate_is_reorder"]) {
				$affiliate_type = configuration("affiliate_reorder_type");
				if ($affiliate_type == 1) {
					$data1[] = ["affiliate_name" => "二次购买", "affiliate_des" => "推介的用户二次购买产品后返佣金额", "affiliate_type" => "固定金额", "affiliate_bates" => $data["prefix"] . sprintf("%.2f", configuration("affiliate_reorder"))];
				} else {
					$data1[] = ["affiliate_name" => "二次购买", "affiliate_des" => "推介的用户二次购买产品后返佣金额", "affiliate_type" => "百分比", "affiliate_bates" => configuration("affiliate_reorder") . "%"];
				}
			}
		}
		return jsons(["status" => 200, "data" => $data, "datarr" => $data1, "is_withdraw" => $flag, "affiliate_withdraw" => $affiliate_withdraw, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * @title 推荐计划提现
	 * @description 接口说明:
	 * @param .name:num type:float require:1 default:0 other: desc:提现金额
	 * @author lgd
	 * @url /withdraw
	 * @method post
	 */
	public function withdraw(\think\Request $request)
	{
		if ($this->request->isPost()) {
			$id = $request->uid;
			$param = $this->request->param();
			$datas["num"] = isset($param["num"]) ? floatval($param["num"]) : 0;
			if ($datas["num"] <= 0) {
				return jsons(["status" => 400, "msg" => "无效的提现金额！"]);
			}
			$affiliate_is_authentication = configuration("affiliate_is_authentication");
			if ($affiliate_is_authentication == 1) {
				if (!checkCertify($id)) {
					return jsons(["status" => 400, "msg" => "未实名认证不能提现！"]);
				}
			}
			$affiliate_withdraw = configuration("affiliate_withdraw");
			if ($affiliate_withdraw && $datas["num"] < $affiliate_withdraw) {
				return jsons(["status" => 400, "msg" => "提现金额小于最低提现金额"]);
			}
			$data = \think\Db::name("affiliates")->where("uid", $id)->find();
			$balance = $data["balance"];
			if ($balance <= 0 || $balance < $datas["num"]) {
				return jsons(["status" => 400, "msg" => "可以提现余额不足"]);
			}
			\think\Db::startTrans();
			try {
				$now_balance = bcsub($balance, $datas["num"], 2);
				if ($now_balance < 0) {
					\think\Db::rollback();
					return jsons(["status" => 400, "msg" => "可提现余额不足"]);
				}
				\think\Db::name("affiliates")->where("uid", $id)->update(["balance" => $now_balance, "withdraw_ing" => bcadd($data["withdraw_ing"], $datas["num"], 2), "updated_time" => time()]);
				$res = \think\Db::name("affiliates_withdraw")->insertGetId(["uid" => $id, "create_time" => time(), "type" => 0, "num" => $datas["num"], "status" => 1]);
				if ($res) {
					active_logs(sprintf($this->lang["Aff_home_withdraw"], $id, $datas["num"], $res), $id);
					active_logs(sprintf($this->lang["Aff_home_withdraw"], $id, $datas["num"], $res), $id, "", 2);
					\think\Db::commit();
					return jsons(["status" => 200, "msg" => "提现成功,等待后台管理员审核"]);
				} else {
					\think\Db::rollback();
					return jsons(["status" => 400, "msg" => "提现失败"]);
				}
			} catch (\Exception $e) {
				return jsons(["status" => 400, "msg" => $e->getMessage()]);
				\think\Db::rollback();
			}
		}
	}
	/**
	 * @title 推荐计划提现记录
	 * @description 接口说明:
	 * @author lgd
	 * @url /withdrawrecord
	 * @method get
	 * @param .name:page type:int require:0  other: desc:页码
	 * @param .name:limit type:int require:0  other: desc:长度
	 * @param .name:order type:string require:0  other: desc:排序字段
	 * @param .name:sort type:string require:0  other: desc:排序规则(asc/desc)
	 * @param name:keywords type:int  require:0  default: other: desc:关键字
	 * @param name:type type:int  require:0  default: other: desc:类型
	 * @param name:status type:int  require:0  default: other: desc:状态
	 * @param name:search_time type:int  require:0  default: other: desc:时间
	 * @return data:基础数据@
	 * @data  num:金额
	 * @data  type:1余额2仅记录3流水支持
	 * @data  user_nickname:操作人
	 * @data  status:1待审核2审核通过3拒绝
	 * @data  reason:拒绝原因
	 * @data  create_time:时间
	 */
	public function withdrawrecord(\think\Request $request)
	{
		$page = input("page") ?? config("page");
		$limit = input("limit") ?? config("limit");
		$order = input("order") ?? "a.id";
		$sort = input("sort") ?? "desc";
		$param = $request->param();
		$id = $request->uid;
		$rows = \think\Db::name("affiliates_withdraw")->alias("a")->field("a.*,u.user_nickname,cu.suffix")->leftJoin("user u", "a.admin_id=u.id")->join("clients c", "c.id=a.uid")->leftJoin("currencies cu", "cu.id = c.currency")->where("a.uid", $id)->where(function (\think\db\Query $query) use($param) {
			if (!empty($param["keywords"])) {
				$search_desc = $param["keywords"];
				$query->whereOr("a.num", "like", "%{$search_desc}%");
				$query->whereOr("u.user_nickname", "like", "%{$search_desc}%");
				$query->whereOr("a.reason", "like", "%{$search_desc}%");
			}
			if (!empty($param["type"])) {
				$search_desc = $param["type"];
				$query->whereOr("a.type", $search_desc);
			}
			if (!empty($param["status"])) {
				$search_desc = $param["status"];
				$query->whereOr("a.status", $search_desc);
			}
			if (!empty($param["time"])) {
				$start_time = strtotime(date("Y-m-d", $param["search_time"]));
				$end_time = strtotime(date("Y-m-d", $param["search_time"])) + 86400;
				$query->where("create_time", "<=", $end_time);
				$query->where("create_time", ">=", $start_time);
			}
		})->page($page)->limit($limit)->order($order, $sort)->select()->toArray();
		$total = \think\Db::name("affiliates_withdraw")->alias("a")->field("a.*,u.user_nickname,cu.suffix")->leftJoin("user u", "a.admin_id=u.id")->join("clients c", "c.id=a.uid")->leftJoin("currencies cu", "cu.id = c.currency")->where("a.uid", $id)->where(function (\think\db\Query $query) use($param) {
			if (!empty($param["keywords"])) {
				$search_desc = $param["keywords"];
				$query->whereOr("a.num", "like", "%{$search_desc}%");
				$query->whereOr("u.user_nickname", "like", "%{$search_desc}%");
				$query->whereOr("a.reason", "like", "%{$search_desc}%");
			}
			if (!empty($param["type"])) {
				$search_desc = $param["type"];
				$query->whereOr("a.type", $search_desc);
			}
			if (!empty($param["status"])) {
				$search_desc = $param["status"];
				$query->whereOr("a.status", $search_desc);
			}
			if (!empty($param["time"])) {
				$start_time = strtotime(date("Y-m-d", $param["search_time"]));
				$end_time = strtotime(date("Y-m-d", $param["search_time"])) + 86400;
				$query->where("create_time", "<=", $end_time);
				$query->where("create_time", ">=", $start_time);
			}
		})->count();
		return jsons(["status" => 200, "data" => $rows, "msg" => lang("SUCCESS MESSAGE"), "total" => $total]);
	}
	/**
	 * @title 推荐计划购买记录
	 * @description 接口说明:购买记录
	 * @author lgd
	 * @url /affbuyrecord
	 * @method post
	 * @param .name:page type:int require:0  other: desc:页码
	 * @param .name:limit type:int require:0  other: desc:长度
	 * @param .name:order type:string require:0  other: desc:排序字段
	 * @param .name:sort type:string require:0  other: desc:排序规则(asc/desc)
	 * @param name:keywords type:int  require:0  default: other: desc:关键字
	 * @param name:status type:int  require:0  default: other: desc:状态
	 * @return data:基础数据@
	 * @data  create_time:购买时间
	 * @data  subtotal:订购金额
	 * @data  aff_type:方式
	 * @data  type:类型
	 * @data  commission:佣金
	 * @data  commission_bates:佣金比例
	 * @data  commission_bates_type:佣金比例类型1固定2比例
	 * @data  paid_time:确认时间
	 */
	public function affbuyrecord(\think\Request $request)
	{
		$page = input("page") ?? config("page");
		$limit = input("limit") ?? config("limit");
		$order = input("order") ?? "i.id";
		$sort = input("sort") ?? "desc";
		if (!!input("order")) {
			$order = "i." . input("order");
		} else {
			$order = "i.id";
		}
		$param = $request->param();
		$id = $request->uid;
		$uids = getids($id);
		$ladder = getLadder($id, $uids);
		$rs = $this->updateCommission($id, $uids, $ladder);
		$total = \think\Db::name("invoices")->alias("i")->join("clients c", "i.uid=c.id")->leftJoin("currencies cu", "cu.id = c.currency")->leftJoin("orders o", "i.id=o.invoiceid")->field("i.id as invoiceid,i.is_aff,i.aff_commmission_bates_type,i.aff_commission,i.aff_commmission_bates,i.status,i.create_time,i.type,i.subtotal,i.paid_time,c.username,o.id,c.id as uid,cu.prefix,cu.suffix,i.aff_sure_time,i.aff_commission,i.aff_commmission_bates,i.aff_commmission_bates_type,i.is_aff")->where("i.delete_time", 0)->where("i.status", "in", "Paid,Refunded")->where("c.id", "in", $uids)->where(function (\think\db\Query $query) use($param) {
			if (!empty($param["keywords"])) {
				$search_desc = $param["keywords"];
				$query->where("c.username like '%{$search_desc}%' OR i.subtotal like '%{$search_desc}%' OR i.create_time like '%{$search_desc}%'");
			}
			if (!empty($param["type"])) {
				$query->where("i.type", $param["type"]);
			}
		})->order($order, $sort)->select()->toArray();
		$total = $this->getCommission($total, $ladder, $id);
		$rows = \think\Db::name("invoices")->alias("i")->join("clients c", "i.uid=c.id")->leftJoin("currencies cu", "cu.id = c.currency")->leftJoin("orders o", "i.id=o.invoiceid")->field("i.id as invoiceid,i.is_aff,i.aff_commmission_bates_type,i.aff_commission,i.aff_commmission_bates,i.status,i.create_time,i.type,i.subtotal,i.paid_time,c.username,o.id,c.id as uid,cu.prefix,cu.suffix,i.aff_sure_time,i.aff_commission,i.aff_commmission_bates,i.aff_commmission_bates_type,i.is_aff")->where("i.delete_time", 0)->where("i.status", "in", "Paid,Refunded")->where("c.id", "in", $uids)->where(function (\think\db\Query $query) use($param) {
			if (!empty($param["keywords"])) {
				$search_desc = $param["keywords"];
				$query->where("c.username like '%{$search_desc}%' OR i.subtotal like '%{$search_desc}%' OR i.create_time like '%{$search_desc}%'");
			}
			if (!empty($param["type"])) {
				$query->where("i.type", $param["type"]);
			}
		})->page($page)->limit($limit)->order($order, $sort)->select()->toArray();
		$rows = $this->getCommission($rows, $ladder, $id);
		$rows = array_values($rows);
		return jsons(["status" => 200, "data" => $rows, "msg" => lang("SUCCESS MESSAGE"), "total" => count($total)]);
	}
	public function getCommission($rows, $ladder, $id)
	{
		$rows = dealCommissionaff($rows, $ladder, $id);
		foreach ($rows as $k => $value) {
			$rows[$k]["aff_type"] = "推荐链接";
			if ($rows[$k]["is_aff"] == 1 || $value["paid_time"] + configuration("affiliate_delay_commission") * 24 * 60 * 60 <= time()) {
				$rows[$k]["paid_time"] = "ok";
			} else {
				$rows[$k]["paid_time"] = $value["paid_time"] + configuration("affiliate_delay_commission") * 24 * 60 * 60;
			}
			if ($rows[$k]["is_aff"] == 1) {
				$rows[$k]["commission"] = $rows[$k]["aff_commission"];
				$rows[$k]["commission_bates"] = $rows[$k]["aff_commmission_bates"];
				$rows[$k]["commission_bates_type"] = $rows[$k]["aff_commmission_bates_type"];
			}
			$rows[$k]["commission"] = round(floatval($rows[$k]["commission"]), 2);
			switch ($value["type"]) {
				case "renew":
					$rows[$k]["type"] = "续费";
					break;
				case "product":
					$rows[$k]["type"] = "新订购";
					break;
				case "product2":
					$rows[$k]["type"] = "二次订购";
					break;
				case "upgrade":
					$rows[$k]["type"] = "升降级";
					break;
				case "zjmf_flow_packet":
					$rows[$k]["type"] = "流量包订购";
					break;
				case "zjmf_reinstall_times":
					$rows[$k]["type"] = "重装次数";
					break;
			}
		}
		foreach ($rows as $k => $value) {
			if ($rows[$k]["commission_bates"] == "0.00" || $rows[$k]["commission_bates"] == "") {
				unset($rows[$k]);
			}
		}
		return $rows;
	}
	public function updateCommission($id, $uids, $ladder)
	{
		$rows1 = \think\Db::name("invoices")->alias("i")->join("clients c", "i.uid=c.id")->leftJoin("currencies cu", "cu.id = c.currency")->leftJoin("orders o", "i.id=o.invoiceid")->field("i.status,i.is_aff,i.aff_commmission_bates_type,i.aff_commission,i.aff_commmission_bates,i.type,i.subtotal,i.paid_time,c.username,o.id,c.id as uid,o.status,i.create_time,i.id as invoiceid,cu.prefix,cu.suffix")->where("i.delete_time", 0)->where("i.status", "in", "Paid,Refunded")->where("i.is_aff", 0)->where("i.paid_time", "<=", time() - configuration("affiliate_delay_commission") * 24 * 60 * 60)->where("c.id", "in", $uids)->select()->toArray();
		foreach ($rows1 as $k => $value) {
			$arr = [];
			$af = \think\Db::name("affiliates_user")->where("uid", $value["uid"])->find();
			if (!empty($af)) {
				$affi = \think\Db::name("affiliates")->where("id", $af["affid"])->find();
				if (!empty($affi)) {
					\think\Db::startTrans();
					try {
						$arr[] = $value;
						$rows = dealCommissionaffs($arr, $ladder, $affi["uid"]);
						$res = \think\Db::name("invoices")->where("id", $value["invoiceid"])->update(["is_aff" => 1, "aff_sure_time" => time(), "aff_commission" => $rows[0]["commission"], "aff_commmission_bates" => $rows[0]["commission_bates"], "aff_commmission_bates_type" => $rows[0]["commission_bates_type"]]);
						foreach ($rows[0]["child"] as $k => $val) {
							$res = \think\Db::name("invoice_items")->where("id", $val["inid"])->update(["is_aff" => 1, "aff_sure_time" => time(), "aff_commission" => $val["commission"], "aff_commmission_bates" => $val["commission_bates"], "aff_commmission_bates_type" => $val["commission_bates_type"]]);
						}
						$res = \think\Db::name("affiliates")->where("id", $affi["id"])->update(["balance" => bcadd($rows[0]["commission"], $affi["balance"], 2), "updated_time" => time()]);
						\think\Db::commit();
					} catch (\Exception $e) {
						\think\Db::rollback();
					}
				}
			}
		}
		$rows = \think\Db::name("invoices")->alias("i")->join("clients c", "i.uid=c.id")->leftJoin("currencies cu", "cu.id = c.currency")->leftJoin("orders o", "i.id=o.invoiceid")->field("i.status,i.is_aff,i.aff_commmission_bates_type,i.aff_commission,i.aff_commmission_bates,i.type,i.subtotal,i.paid_time,c.username,o.id,c.id as uid,o.status,i.create_time,i.id as invoiceid,cu.prefix,cu.suffix")->where("i.delete_time", 0)->where("i.status", "in", "Paid")->where("i.is_aff", 0)->where("c.id", "in", $uids)->select()->toArray();
		$affi = \think\Db::name("affiliates")->where("uid", $id)->find();
		$sum = 0;
		$rows = dealCommissionaff($rows, $ladder, $affi["uid"]);
		foreach ($rows as $k => $value) {
			$sum = bcadd($value["commission"], $sum, 2);
		}
		\think\Db::startTrans();
		try {
			$res = \think\Db::name("affiliates")->where("id", $affi["id"])->update(["audited_balance" => $sum, "updated_time" => time()]);
			$affi1 = \think\Db::name("affiliates")->where("id", $affi["id"])->find();
			$res = \think\Db::name("affiliates")->where("id", $affi["id"])->update(["sum" => $affi1["audited_balance"] + $affi1["balance"] + $affi1["withdrawn"] + $affi1["withdraw_ing"], "updated_time" => time()]);
			\think\Db::commit();
		} catch (\Exception $e) {
			\think\Db::rollback();
		}
		$total = \think\Db::name("orders")->alias("o")->join("clients c", "o.uid=c.id")->join("invoices i", "i.id=o.invoiceid")->field("i.status,i.type,i.subtotal,i.paid_time,c.username,o.id,o.uid,c.id as uid,o.status,i.create_time,i.id as invoiceid")->where("i.delete_time", 0)->where("i.status", "=", "Paid")->where("c.id", "in", $uids)->select()->toArray();
		$total = getAffBycount($total, $id);
		$affi = \think\Db::name("affiliates")->where("uid", $id)->find();
		if (!empty($affi)) {
			\think\Db::startTrans();
			try {
				$res = \think\Db::name("affiliates")->where("id", $affi["id"])->update(["payamount" => $total, "updated_time" => time()]);
				\think\Db::commit();
			} catch (\Exception $e) {
				\think\Db::rollback();
			}
		}
	}
	/**
	 * @title 客户注册列表
	 * @description 接口说明:
	 * @author lgd
	 * @url /useraffi_list
	 * @method get
	 * @param .name:username type:string require:0  other: desc:用户名
	 * @param .name:page type:int require:0  other: desc:页码
	 * @param .name:limit type:int require:0  other: desc:长度
	 * @param .name:order type:string require:0  other: desc:排序字段
	 * @param .name:sort type:string require:0  other: desc:排序规则(asc/desc)
	 * @return data:用户推介数据@
	 * @data id:用户id
	 * @data create_time:创建时间
	 * @data username:用户名
	 * @data companyname:公司名
	 * @data lastlogin:登录时间
	 * @return total:总条数@
	 */
	public function useraffilist()
	{
		$page = input("page") ?? config("page");
		$limit = input("limit") ?? config("limit");
		$order = input("order") ?? "a.id";
		$sort = input("sort") ?? "desc";
		if (!!input("order")) {
			$order = "c." . input("order");
		} else {
			$order = "a.id";
		}
		$uid = request()->uid;
		$params = $this->request->param();
		$data = \think\Db::name("affiliates")->field("id")->where("uid", $uid)->find();
		$rows = \think\Db::name("affiliates_user")->alias("a")->join("clients c", "a.uid=c.id")->field("c.id,c.create_time,c.username,c.companyname,c.lastlogin,c.email,c.phonenumber")->where("a.affid", $data["id"])->where(function (\think\db\Query $query) use($params) {
			if (!empty($params["username"])) {
				$search_desc = $params["username"];
				$query->where("c.username LIKE '%{$search_desc}%'");
			}
		})->page($page)->limit($limit)->order($order, $sort)->select()->toArray();
		$total = \think\Db::name("affiliates_user")->alias("a")->join("clients c", "a.uid=c.id")->where("a.affid", $data["id"])->where(function (\think\db\Query $query) use($params) {
			if (!empty($params["username"])) {
				$search_desc = $params["username"];
				$query->where("c.username LIKE '%{$search_desc}%'");
			}
		})->count();
		$data = ["rows" => $rows, "total" => $total];
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
}