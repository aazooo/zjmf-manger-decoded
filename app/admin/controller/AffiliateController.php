<?php

namespace app\admin\controller;

/**
 * @title 后台推荐计划
 */
class AffiliateController extends GetUserController
{
	/**
	 * @title 推介计划
	 * @description 接口说明:
	 * @author lgd
	 * @url /admin/aff
	 * @method get
	 * @param .name:username type:string require:0  other: desc:用户名
	 * @param .name:visitors type:float require:0  other: desc:访问量
	 * @param .name:visitors_type type:string require:0  other: desc:1大于访问量小于2
	 * @param .name:balance type:float require:0  other: desc:可提现佣金
	 * @param .name:balance_type type:string require:0  other: desc:1大于访问量小于2
	 * @param .name:withdrawn type:float require:0  other: desc:已提现佣金
	 * @param .name:withdrawn_type type:string require:0  other: desc:1大于访问量小于2
	 * @param .name:registcount type:int require:0  other: desc:注册数量
	 * @param .name:registcount_type type:string require:0  other: desc:1大于访问量小于2
	 * @param .name:page type:int require:0  other: desc:页码
	 * @param .name:limit type:int require:0  other: desc:长度
	 * @param .name:order type:string require:0  other: desc:排序字段
	 * @param .name:sort type:string require:0  other: desc:排序规则(asc/desc)
	 * @return data:基础数据@
	 * @data  id:id
	 * @data  username:姓名
	 * @data  companyname:姓名
	 * @data  visitors:访问数量
	 * @data  registcount:注册数量
	 * @data  url:推荐链接
	 * @data  payamount:订购数量
	 * @data  balance:可提现佣金
	 * @data  sum:总佣金
	 * @data  withdrawn:已提现佣金
	 */
	public function index(\think\Request $request)
	{
		$params = $this->request->param();
		$username = !empty($params["username"]) ? trim($params["username"]) : "";
		$page = input("page") ?? config("page");
		$limit = input("limit") ?? config("limit");
		$order = input("order");
		$sort = input("sort") ?? "desc";
		if (!!input("order")) {
			if (input("order") == "username") {
				$order = "c." . input("order");
			} else {
				$order = "a." . input("order");
			}
		} else {
			$order = "a.id";
		}
		$where = [];
		$data = \think\Db::name("affiliates")->alias("a")->field("a.*,c.username,c.companyname")->join("clients c", "a.uid=c.id")->where($where)->where("c.username LIKE '%{$username}%'")->where(function (\think\db\Query $query) use($params) {
			if (!empty($params["visitors"])) {
				if (!empty($params["visitors_type"])) {
					if ($params["visitors_type"] == 1) {
						$query->where("visitors", ">", $params["visitors"]);
					} else {
						$query->where("visitors", "<", $params["visitors"]);
					}
				}
			}
			if (!empty($params["balance"])) {
				if (!empty($params["balance_type"])) {
					if ($params["balance_type"] == 1) {
						$query->where("balance", ">", $params["balance"]);
					} else {
						$query->where("balance", "<", $params["balance"]);
					}
				}
			}
			if (!empty($params["withdrawn"])) {
				if (!empty($params["withdrawn_type"])) {
					if ($params["withdrawn_type"] == 1) {
						$query->where("withdrawn", ">", $params["withdrawn"]);
					} else {
						$query->where("withdrawn", "<", $params["withdrawn"]);
					}
				}
			}
			if (!empty($params["registcount"])) {
				if (!empty($params["registcount_type"])) {
					if ($params["registcount_type"] == 1) {
						$query->where("registcount", ">", $params["registcount"]);
					} else {
						$query->where("registcount", "<", $params["registcount"]);
					}
				}
			}
		});
		if ($this->user["id"] != 1 && $this->user["is_sale"]) {
			$data->whereIn("c.id", $this->str);
		}
		$data = $data->page($page)->limit($limit)->order($order, $sort)->select()->toArray();
		$total = \think\Db::name("affiliates")->alias("a")->field("a.*,c.username")->join("clients c", "a.uid=c.id")->where($where)->where("c.username LIKE '%{$username}%'")->where(function (\think\db\Query $query) use($params) {
			if (!empty($params["visitors"])) {
				if (!empty($params["visitors_type"])) {
					if ($params["visitors_type"] == 1) {
						$query->where("visitors", ">", $params["visitors"]);
					} else {
						$query->where("visitors", "<", $params["visitors"]);
					}
				}
			}
			if (!empty($params["balance"])) {
				if (!empty($params["balance_type"])) {
					if ($params["balance_type"] == 1) {
						$query->where("balance", ">", $params["balance"]);
					} else {
						$query->where("balance", "<", $params["balance"]);
					}
				}
			}
			if (!empty($params["withdrawn"])) {
				if (!empty($params["withdrawn_type"])) {
					if ($params["withdrawn_type"] == 1) {
						$query->where("withdrawn", ">", $params["withdrawn"]);
					} else {
						$query->where("withdrawn", "<", $params["withdrawn"]);
					}
				}
			}
			if (!empty($params["registcount"])) {
				if (!empty($params["registcount_type"])) {
					if ($params["registcount_type"] == 1) {
						$query->where("registcount", ">", $params["registcount"]);
					} else {
						$query->where("registcount", "<", $params["registcount"]);
					}
				}
			}
		});
		if ($this->user["id"] != 1 && $this->user["is_sale"]) {
			$total->whereIn("c.id", $this->str);
		}
		$total = $total->count();
		return jsonrule(["status" => 200, "total" => $total, "data" => $data]);
	}
	/**
	 * @title 用户推介配置
	 * @description 接口说明:
	 * @param .name:id type:id require:1 default:1 other: desc:用户id
	 * @author lgd
	 * @url /admin/aff/useraffi_page
	 * @method get
	 * @return data:用户推介数据@
	 * @data affiliate_enabled:是否启用推介
	 * @data affiliate_bates:推介计划比例
	 * @data affiliate_type:比例类型1金额2百分比
	 * @data affiliate_is_reorder:是否开启二次订单
	 * @data affiliate_reorder:二次订单比例
	 * @data affiliate_reorder_type:二次订单比例类型 1金额  2百分比
	 * @data affiliate_is_renew:是否开启续费
	 * @data affiliate_renew:续费比例
	 * @data affiliate_renew_type:续费比例类型 1金额  2百分比
	 * @return datauser:用户推介计划@
	 * @datauser  visitors:访问数量
	 * @datauser  registcount:注册数量
	 * @datauser  payamount:订购数量
	 * @datauser  balance:可提现佣金
	 * @datauser  audited_balance:审核中的佣金
	 * @datauser  withdrawn:已提现佣金
	 */
	public function useraffiPage()
	{
		$uid = $this->request->id;
		$rows1 = \think\Db::name("affiliates_user_setting")->where("uid", $uid)->find();
		$data = \think\Db::name("affiliates")->where("uid", $uid)->find();
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $rows1, "datauser" => $data]);
	}
	/**
	 * @title 用户推荐金额修改
	 * @description 接口说明:
	 * @param .name:uid type:id require:1 default:1 other: desc:用户id
	 * @param .name:withdrawn type:float require:0 default:1 other: desc:已提现金额
	 * @param .name:balance type:float require:0 default:1 other: desc:可提现金额
	 * @author lgd
	 * @url /admin/aff/useraffi_balance
	 * @method post
	 */
	public function useraffibalance()
	{
		if ($this->request->isPost()) {
			$id = $this->request->uid;
			$param = $this->request->param();
			if ($param["withdrawn"]) {
				$data["withdrawn"] = isset($param["withdrawn"]) ? floatval($param["withdrawn"]) : 0;
			}
			if ($param["balance"]) {
				$data["balance"] = isset($param["balance"]) ? intval($param["balance"]) : 0;
			}
			$desc = "";
			$spg = \think\Db::name("affiliates")->where("uid", $id)->find();
			if ($spg["withdrawn"] != $param["withdrawn"]) {
				$desc .= " 已提现佣金" . $spg["withdrawn"] . "改为" . $param["withdrawn"];
			}
			if ($spg["balance"] != $param["balance"]) {
				$desc .= " 可提现佣金" . $spg["balance"] . "改为" . $param["balance"];
			}
			if (!empty($data)) {
				\think\Db::startTrans();
				try {
					\think\Db::name("affiliates")->where("uid", $id)->update($data);
					active_log_final(sprintf($this->lang["Aff_admin_useraffibalance"], $spg["id"], $desc), $param["uid"]);
					\think\Db::commit();
				} catch (\Exception $e) {
					return jsons(["status" => 400, "msg" => $e->getMessage()]);
					\think\Db::rollback();
				}
			}
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 用户推荐配置提交
	 * @description 接口说明:
	 * @param .name:id type:id require:0 default:1 other: desc:id
	 * @param .name:uid type:id require:1 default:1 other: desc:用户id
	 * @param .name:affiliate_enabled type:int require:0 default:1 other: desc:系统默认1自定义2
	 * @param .name:affiliate_bates type:float require:0 default:1 other: desc:推介计划比例
	 * @param .name:affiliate_type type:int require:0 default:1 other: desc:比例类型1金额2百分比
	 * @param .name:affiliate_is_reorder type:int require:0 default:1 other: desc:系统默认1自定义2
	 * @param .name:affiliate_reorder type:float require:0 default:1 other: desc:二次订单比例
	 * @param .name:affiliate_reorder_type type:int require:0 default:1 other: desc:二次订单方式1金额2百分比
	 * @param .name:affiliate_is_renew type:int require:0 default:1 other: desc:系统默认1自定义2
	 * @param .name:affiliate_renew type:float require:0 default:1 other: desc:续费比例
	 * @param .name:affiliate_renew_type type:int require:0 default:1 other: desc:续费方式1金额2百分比
	 * @author lgd
	 * @url /admin/aff/useraffi_post
	 * @method post
	 */
	public function useraffiPost()
	{
		if ($this->request->isPost()) {
			$id = $this->request->id;
			if (empty($id)) {
				$param = $this->request->param();
				$data["uid"] = isset($param["uid"]) ? floatval($param["uid"]) : 0;
				$data["affiliate_enabled"] = isset($param["affiliate_enabled"]) ? floatval($param["affiliate_enabled"]) : 0;
				$data["affiliate_bates"] = isset($param["affiliate_bates"]) ? floatval($param["affiliate_bates"]) : 0;
				$data["affiliate_type"] = isset($param["affiliate_type"]) ? floatval($param["affiliate_type"]) : 0;
				$data["affiliate_is_reorder"] = isset($param["affiliate_is_reorder"]) ? intval($param["affiliate_is_reorder"]) : 0;
				$data["affiliate_reorder"] = isset($param["affiliate_reorder"]) ? intval($param["affiliate_reorder"]) : 0;
				$data["affiliate_reorder_type"] = isset($param["affiliate_reorder_type"]) ? floatval($param["affiliate_reorder_type"]) : 0;
				$data["affiliate_is_renew"] = isset($param["affiliate_is_renew"]) ? floatval($param["affiliate_is_renew"]) : 0;
				$data["affiliate_renew"] = isset($param["affiliate_renew"]) ? floatval($param["affiliate_renew"]) : 0;
				$data["affiliate_renew_type"] = isset($param["affiliate_renew_type"]) ? floatval($param["affiliate_renew_type"]) : 0;
				$data["create_time"] = time();
				$res = \think\Db::name("affiliates_user_setting")->insertGetId($data);
				active_log_final(sprintf($this->lang["Aff_admin_useraffiAdd"], $id), $param["uid"]);
			} else {
				$param = $this->request->param();
				$data["affiliate_enabled"] = isset($param["affiliate_enabled"]) ? floatval($param["affiliate_enabled"]) : 0;
				$data["affiliate_bates"] = isset($param["affiliate_bates"]) ? floatval($param["affiliate_bates"]) : 0;
				$data["affiliate_type"] = isset($param["affiliate_type"]) ? floatval($param["affiliate_type"]) : 0;
				$data["affiliate_is_reorder"] = isset($param["affiliate_is_reorder"]) ? floatval($param["affiliate_is_reorder"]) : 0;
				$data["affiliate_reorder"] = isset($param["affiliate_reorder"]) ? floatval($param["affiliate_reorder"]) : 0;
				$data["affiliate_reorder_type"] = isset($param["affiliate_reorder_type"]) ? floatval($param["affiliate_reorder_type"]) : 0;
				$data["affiliate_is_renew"] = isset($param["affiliate_is_renew"]) ? floatval($param["affiliate_is_renew"]) : 0;
				$data["affiliate_renew"] = isset($param["affiliate_renew"]) ? floatval($param["affiliate_renew"]) : 0;
				$data["affiliate_renew_type"] = isset($param["affiliate_renew_type"]) ? floatval($param["affiliate_renew_type"]) : 0;
				$desc = "";
				$spg = \think\Db::name("affiliates_user_setting")->where("uid", $param["uid"])->find();
				if ($spg["affiliate_bates"] != $param["affiliate_bates"]) {
					$desc .= "用户推介计划比例由“" . $spg["affiliate_bates"] . "”改为“" . $param["affiliate_bates"] . "”，";
				}
				if ($spg["affiliate_type"] != $param["affiliate_type"]) {
					$str = "";
					if ($spg["affiliate_type"] == 1) {
						$str = "类型:金额";
					} else {
						$str = "类型:百分比";
					}
					if ($param["affiliate_type"] == 1) {
						$desc .= "用户比例类型由“" . $str . "”改为“金额”，";
					} else {
						$desc .= "用户比例类型由“" . $str . "”改为“百分比”，";
					}
				}
				if ($spg["affiliate_is_reorder"] != $param["affiliate_is_reorder"]) {
					if ($spg["affiliate_is_reorder"] == 1) {
						$desc .= "二次订购由“关闭”改为“开启”，";
					} else {
						$desc .= "二次订购由“开启”改为“关闭”，";
					}
				}
				if ($spg["affiliate_reorder"] != $param["affiliate_reorder"]) {
					$desc .= " 二次订购比例“" . $spg["affiliate_reorder"] . "”改为“" . $param["affiliate_reorder"] . "”，";
				}
				if ($spg["affiliate_is_renew"] != $param["affiliate_is_renew"]) {
					if ($spg["affiliate_is_renew"] == 1) {
						$desc .= "续费由“关闭”改为“开启”，";
					} else {
						$desc .= "续费由“开启”改为“关闭”，";
					}
				}
				if ($spg["affiliate_renew"] != $param["affiliate_renew"]) {
					$desc .= "续费比例由“" . $spg["affiliate_renew"] . "”改为“" . $param["affiliate_renew"] . "”，";
				}
				if ($spg["affiliate_enabled"] != $param["affiliate_enabled"]) {
					if ($spg["affiliate_enabled"] == 1) {
						$desc .= "用户推介计划由“关闭”改为“开启”";
					} else {
						$desc .= "用户推介计划由“开启”改为“关闭”";
					}
				}
				\think\Db::name("affiliates_user_setting")->where("id", $param["id"])->update($data);
				if (empty($desc)) {
					$desc .= "未做任何修改";
				}
				active_log_final(sprintf($this->lang["Aff_admin_useraffiPost"], $spg["id"], $desc), $param["uid"]);
			}
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 客户注册列表
	 * @description 接口说明:
	 * @param .name:id type:id require:1 default:1 other: desc:用户id
	 * @author lgd
	 * @url /admin/aff/useraffi_list
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
		$uid = $this->request->id;
		$params = $this->request->param();
		$data = \think\Db::name("affiliates")->field("id")->where("uid", $uid)->find();
		$rows = \think\Db::name("affiliates_user")->alias("a")->join("clients c", "a.uid=c.id")->field("c.id,c.create_time,c.username,c.companyname,c.lastlogin")->where($where)->where("a.affid", $data["id"])->where(function (\think\db\Query $query) use($params) {
			if (!empty($params["username"])) {
				$search_desc = $params["username"];
				$query->where("c.username LIKE '%{$search_desc}%'");
			}
		})->page($page)->limit($limit)->order($order, $sort)->select()->toArray();
		$total = \think\Db::name("affiliates_user")->alias("a")->join("clients c", "a.uid=c.id")->field("c.id,c.create_time,c.username,c.lastloginip")->where($where)->where("a.affid", $data["id"])->where(function (\think\db\Query $query) use($params) {
			if (!empty($params["username"])) {
				$search_desc = $params["username"];
				$query->where("c.username LIKE '%{$search_desc}%'");
			}
		})->count();
		foreach ($rows as $key => $value) {
			$str = $rows[$key]["companyname"] ? $rows[$key]["username"] . "(" . $rows[$key]["companyname"] . ")" : $rows[$key]["username"];
			$rows[$key]["username"] = "<a class=\"el-link el-link--primary is-underline\"
            href=\"#/customer-view/abstract?id=" . $value["id"] . "\">
            <span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
		}
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $rows, "total" => $total]);
	}
	/**
	 * @title 提现记录
	 * @description 接口说明:
	 * @param .name:id type:id require:1 default:1 other: desc:用户id
	 * @author lgd
	 * @url /admin/aff/useraffi_record
	 * @method get
	 * @param .name:user_nickname type:string require:0  other: desc:用户名
	 * @param .name:status type:int require:0  other: desc:状态1待审核2通过3拒绝
	 * @param .name:page type:int require:0  other: desc:页码
	 * @param .name:limit type:int require:0  other: desc:长度
	 * @param .name:order type:string require:0  other: desc:排序字段
	 * @param .name:sort type:string require:0  other: desc:排序规则(asc/desc)
	 * @return data:基础数据@
	 * @data  num:金额
	 * @data  type:余额1仅记录2流水支持3
	 * @data  user_nickname:操作人
	 * @data  status:1待审核2审核通过3拒绝
	 * @data  reason:拒绝原因
	 * @data  create_time:时间
	 * @data  u.companyname 公司名
	 */
	public function useraffirecord(\think\Request $request)
	{
		$id = $this->request->id;
		$params = $this->request->param();
		$page = input("page") ?? config("page");
		$limit = input("limit") ?? config("limit");
		if (!!input("order")) {
			$order = "a." . input("order");
		} else {
			$order = "a.id";
		}
		$sort = input("sort") ?? "desc";
		$rows = \think\Db::name("affiliates_withdraw")->alias("a")->join("clients c", "c.id=a.uid")->leftJoin("currencies cu", "cu.id = c.currency")->leftJoin("user u", "a.admin_id=u.id")->field("a.id,a.num,a.type,a.create_time,a.status,a.reason,u.user_nickname,cu.suffix,c.companyname")->where(function (\think\db\Query $query) use($params) {
			if (!empty($params["user_nickname"])) {
				$search_desc = $params["user_nickname"];
				$query->where("u.user_nickname LIKE '%{$search_desc}%'");
			}
			if (!empty($params["status"])) {
				$search_desc = $params["status"];
				$query->where("a.status", $search_desc);
			}
		})->where("a.uid", $id)->page($page)->limit($limit)->order($order, $sort)->select()->toArray();
		$total = \think\Db::name("affiliates_withdraw")->alias("a")->join("clients c", "c.id=a.uid")->leftJoin("currencies cu", "cu.id = c.currency")->leftJoin("user u", "a.admin_id=u.id")->field("a.id,a.num,a.type,a.create_time,a.status,a.reason,u.user_nickname,cu.suffix")->where(function (\think\db\Query $query) use($params) {
			if (!empty($params["user_nickname"])) {
				$search_desc = $params["user_nickname"];
				$query->where("u.user_nickname LIKE '%{$search_desc}%'");
			}
			if (!empty($param["status"])) {
				$search_desc = $params["status"];
				$query->where("a.status", $search_desc);
			}
		})->where("a.uid", $id)->count();
		return jsonrule(["status" => 200, "data" => $rows, "msg" => lang("SUCCESS MESSAGE"), "total" => $total]);
	}
	/**
	 * @title 获取时间类型
	 * @description 接口说明: 获取时间类型
	 * @author lgd
	 * @url /admin/aff/get_timetype
	 * @method GET
	 * @return data:基础数据(搜索区)@
	 * @base  nextduedate:时间周期
	 * @base  name:时间周期
	 */
	public function getTimetype(\think\Request $request)
	{
		$returndata["time_type"] = config("time_type2");
		return jsonrule(["status" => 200, "data" => $returndata]);
	}
	/**
	 * @title 订购记录
	 * @description 接口说明:
	 * @param .name:id type:id require:1 default:1 other: desc:用户id
	 * @param .name:page type:int require:0  other: desc:页码
	 * @param .name:limit type:int require:0  other: desc:长度
	 * @param .name:order type:string require:0  other: desc:排序字段
	 * @param .name:sort type:string require:0  other: desc:排序规则(asc/desc)
	 * @param .name:time type:string require:0  other: desc:时间类型
	 * @author lgd
	 * @url /admin/aff/useraffibuy_record
	 * @method get
	 * @return data:基础数据@
	 * @data  uid:客户id
	 * @data  create_time:订购时间
	 * @data  subtotal:金额
	 * @data  type:类型
	 * @data  paid_time:付款时间
	 * @data  commission:佣金
	 * @data  paid_status:状态
	 * @return child:子数据@
	 * @child domainstatus:产品状态
	 * @child name:产品名
	 * @child amount:金额
	 * @child commission:佣金
	 * @child type:类型
	 * @return total:总数@
	 */
	public function useraffibuyrecord(\think\Request $request)
	{
		$param = $request->param();
		$page = input("page") ?? config("page");
		$limit = input("limit") ?? config("limit");
		$order = input("order") ?? "i.id";
		$sort = input("sort") ?? "desc";
		$start_time = $param["start_time"];
		if ($start_time[0]) {
			$where[] = ["i.paid_time", "egt", $start_time[0]];
		}
		if ($start_time[1]) {
			$where[] = ["i.paid_time", "elt", $start_time[1]];
		}
		$id = $this->request->id;
		$uids = getids($id);
		$ladder = getLadder($id, $uids);
		$total = \think\Db::name("invoices")->alias("i")->join("clients c", "i.uid=c.id")->leftJoin("currencies cu", "cu.id = c.currency")->leftJoin("orders o", "i.id=o.invoiceid")->field("i.status,i.type,i.subtotal,i.paid_time,c.username,o.id,c.id as uid,o.status,i.create_time,i.id as invoiceid,cu.prefix,cu.suffix")->where("i.delete_time", 0)->where("i.status", "=", "Paid")->where("c.id", "in", $uids)->select()->toArray();
		$total = dealCommissionaffs($total, $ladder, $id);
		$total = $this->getr($total);
		$rows = \think\Db::name("invoices")->alias("i")->join("clients c", "i.uid=c.id")->leftJoin("currencies cu", "cu.id = c.currency")->leftJoin("orders o", "i.id=o.invoiceid")->field("o.invoiceid,i.status,i.type,i.subtotal,i.paid_time,c.username,o.id,c.id as uid,o.status,i.create_time,i.id as invoiceid,cu.prefix,cu.suffix,i.aff_sure_time,i.aff_commission,i.aff_commmission_bates,i.aff_commmission_bates_type,i.is_aff,c.companyname")->where("i.delete_time", 0)->where("i.status", "=", "Paid")->where("c.id", "in", $uids)->page($page)->limit($limit)->order($order, $sort)->select()->toArray();
		$rows = dealCommissionaffs($rows, $ladder, $id);
		foreach ($rows as $k => $value) {
			if ($rows[$k]["is_aff"] == 1 && $value["paid_time"] + configuration("affiliate_delay_commission") * 24 * 60 * 60 <= time()) {
				$rows[$k]["paid_status"] = "已确认";
			} else {
				$rows[$k]["paid_status"] = "延迟期内";
			}
			if ($rows[$k]["is_aff"] == 1) {
				$rows[$k]["commission"] = $rows[$k]["aff_commission"];
				$rows[$k]["commission_bates"] = $rows[$k]["aff_commmission_bates"];
				$rows[$k]["commission_bates_type"] = $rows[$k]["aff_commmission_bates_type"];
			}
			foreach ($value["child"] as $key => $val) {
				if ($val["is_aff"] == 1) {
					$rows[$k]["child"][$key]["commission"] = $rows[$k]["child"][$key]["aff_commission"];
					$rows[$k]["child"][$key]["commission_bates"] = $rows[$k]["child"][$key]["aff_commmission_bates"];
					$rows[$k]["child"][$key]["commission_bates_type"] = $rows[$k]["child"][$key]["aff_commmission_bates_type"];
				}
			}
			$rows[$k]["child_name"] = $rows[$k]["child"][0]["name"];
			if ($value["type"] == "renew") {
				$rows[$k]["type"] = "续费";
			} elseif ($value["type"] == "product") {
				$rows[$k]["type"] = "新订购";
			} elseif ($value["type"] == "product2") {
				$rows[$k]["type"] = "二次订购";
			} elseif ($value["type"] == "upgrade") {
				$rows[$k]["type"] = "升降级";
			} elseif ($value["type"] == "zjmf_flow_packet") {
				$rows[$k]["type"] = "流量包订购";
			} elseif ($value["type"] == "zjmf_reinstall_times") {
				$rows[$k]["type"] = "重装次数";
			}
		}
		foreach ($rows as $k => $value) {
			$rows[$k]["commission"] = sprintf("%.2f", $rows[$k]["commission"]);
			if ($rows[$k]["commission_bates"] == "0.00" || $rows[$k]["commission_bates"] == 0 || $rows[$k]["commission_bates"] == "") {
				unset($rows[$k]);
			}
		}
		$rows = array_values($rows);
		return jsons(["status" => 200, "data" => $rows, "msg" => lang("SUCCESS MESSAGE"), "total" => count($total)]);
	}
	public function getr($rows)
	{
		foreach ($rows as $k => $value) {
			if ($rows[$k]["is_aff"] == 1 && $value["paid_time"] + configuration("affiliate_delay_commission") * 24 * 60 * 60 <= time()) {
				$rows[$k]["paid_status"] = "已确认";
			} else {
				$rows[$k]["paid_status"] = "延迟期内";
			}
			if ($rows[$k]["is_aff"] == 1) {
				$rows[$k]["commission"] = $rows[$k]["aff_commission"];
				$rows[$k]["commission_bates"] = $rows[$k]["aff_commmission_bates"];
				$rows[$k]["commission_bates_type"] = $rows[$k]["aff_commmission_bates_type"];
			}
			foreach ($value["child"] as $key => $val) {
				if ($val["is_aff"] == 1) {
					$rows[$k]["child"][$key]["commission"] = $rows[$k]["child"][$key]["aff_commission"];
					$rows[$k]["child"][$key]["commission_bates"] = $rows[$k]["child"][$key]["aff_commmission_bates"];
					$rows[$k]["child"][$key]["commission_bates_type"] = $rows[$k]["child"][$key]["aff_commmission_bates_type"];
				}
			}
			$rows[$k]["child_name"] = $rows[$k]["child"][0]["name"];
			if ($value["type"] == "renew") {
				$rows[$k]["type"] = "续费";
			} elseif ($value["type"] == "product") {
				$rows[$k]["type"] = "新订购";
			} elseif ($value["type"] == "product2") {
				$rows[$k]["type"] = "二次订购";
			} elseif ($value["type"] == "upgrade") {
				$rows[$k]["type"] = "升降级";
			} elseif ($value["type"] == "zjmf_flow_packet") {
				$rows[$k]["type"] = "流量包订购";
			} elseif ($value["type"] == "zjmf_reinstall_times") {
				$rows[$k]["type"] = "重装次数";
			}
		}
		foreach ($rows as $k => $value) {
			if ($rows[$k]["commission_bates"] == "0.00" || $rows[$k]["commission_bates"] == "") {
				unset($rows[$k]);
			}
		}
		return $rows;
	}
	/**
	 * @title 产品推荐配置
	 * @description 接口说明:
	 * @param .name:pid type:id require:1 default:1 other: desc:用户id
	 * @author lgd
	 * @url /admin/aff/productaffi_page
	 * @method get
	 * @return data:产品推介数据@
	 * @return affiliate_enabled:是否启用推介
	 * @return affiliate_bates:推介计划比例
	 * @return affiliate_type:比例类型 1金额 2百分比
	 * @return affiliate_is_reorder:是否开启二次订单
	 * @return affiliate_reorder:二次订单比例
	 * @return affiliate_is_renew:是否开启续费
	 * @return affiliate_renew:续费比例
	 */
	public function productaffiPage()
	{
		$pid = $this->request->pid;
		$rows = \think\Db::name("affiliates_product_setting")->where("pid", $pid)->find();
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $rows]);
	}
	/**
	 * @title 产品推荐配置提交
	 * @description 接口说明:
	 * @param .name:id type:id require:0 default:1 other: desc:id
	 * @param .name:uid type:id require:1 default:1 other: desc:用户id
	 * @param .name:affiliate_enabled type:int require:0 default:1 other: desc:是否启用推介
	 * @param .name:affiliate_bates type:float require:0 default:1 other: desc:推介计划比例
	 * @param .name:affiliate_type type:int require:0 default:1 other: desc:比例类型 1金额2百分比
	 * @param .name:affiliate_is_reorder type:int require:0 default:1 other: desc:是否开启二次订单
	 * @param .name:affiliate_reorder type:float require:0 default:1 other: desc:二次订单比例
	 * @param .name:affiliate_reorder_type type:int require:0 default:1 other: desc:二次订单方式1金额2百分比
	 * @param .name:affiliate_is_renew type:int require:0 default:1 other: desc:是否开启续费
	 * @param .name:affiliate_renew type:float require:0 default:1 other: desc:续费比例
	 * @param .name:affiliate_renew_type type:int require:0 default:1 other: desc:续费方式1金额2百分比
	 * @author lgd
	 * @url /admin/aff/productaffi_post
	 * @method post
	 */
	public function productaffiPost()
	{
		if ($this->request->isPost()) {
			$id = $this->request->id;
			if (empty($id)) {
				$param = $this->request->param();
				$data["pid"] = isset($param["pid"]) ? floatval($param["pid"]) : 0;
				$data["affiliate_enabled"] = isset($param["affiliate_enabled"]) ? floatval($param["affiliate_enabled"]) : 0;
				$data["affiliate_bates"] = isset($param["affiliate_bates"]) ? floatval($param["affiliate_bates"]) : 0;
				$data["affiliate_type"] = isset($param["affiliate_type"]) ? intval($param["affiliate_type"]) : 0;
				$data["affiliate_is_reorder"] = isset($param["affiliate_is_reorder"]) ? intval($param["affiliate_is_reorder"]) : 0;
				$data["affiliate_reorder"] = isset($param["affiliate_reorder"]) ? intval($param["affiliate_reorder"]) : 0;
				$data["affiliate_reorder_type"] = isset($param["affiliate_reorder_type"]) ? intval($param["affiliate_reorder_type"]) : 0;
				$data["affiliate_is_renew"] = isset($param["affiliate_is_renew"]) ? intval($param["affiliate_is_renew"]) : 0;
				$data["affiliate_renew"] = isset($param["affiliate_renew"]) ? intval($param["affiliate_renew"]) : 0;
				$data["affiliate_renew_type"] = isset($param["affiliate_renew_type"]) ? intval($param["affiliate_renew_type"]) : 0;
				$data["create_time"] = time();
				$res = \think\Db::name("affiliates_product_setting")->insertGetId($data);
				active_log_final(sprintf($this->lang["Aff_admin_productaffiAdd"], $res));
			} else {
				$param = $this->request->param();
				$data["affiliate_enabled"] = isset($param["affiliate_enabled"]) ? floatval($param["affiliate_enabled"]) : 0;
				if ($data["affiliate_enabled"] == 1) {
					$data["affiliate_bates"] = isset($param["affiliate_bates"]) ? floatval($param["affiliate_bates"]) : 0;
					$data["affiliate_type"] = isset($param["affiliate_type"]) ? intval($param["affiliate_type"]) : 0;
				}
				$data["affiliate_is_reorder"] = isset($param["affiliate_is_reorder"]) ? intval($param["affiliate_is_reorder"]) : 0;
				if ($data["affiliate_is_reorder"] == 1) {
					$data["affiliate_reorder"] = isset($param["affiliate_reorder"]) ? intval($param["affiliate_reorder"]) : 0;
					$data["affiliate_reorder_type"] = isset($param["affiliate_reorder_type"]) ? intval($param["affiliate_reorder_type"]) : 0;
				}
				$data["affiliate_is_renew"] = isset($param["affiliate_is_renew"]) ? intval($param["affiliate_is_renew"]) : 0;
				if ($data["affiliate_is_renew"] == 1) {
					$data["affiliate_renew"] = isset($param["affiliate_renew"]) ? intval($param["affiliate_renew"]) : 0;
					$data["affiliate_renew_type"] = isset($param["affiliate_renew_type"]) ? intval($param["affiliate_renew_type"]) : 0;
				}
				$desc = "";
				$spg = \think\Db::name("affiliates_product_setting")->where("pid", $param["pid"])->find();
				if ($data["affiliate_enabled"] == 1 && $spg["affiliate_bates"] != $param["affiliate_bates"]) {
					$desc .= " 产品推介计划比例" . $spg["affiliate_bates"] . "改为" . $param["affiliate_bates"];
				}
				if ($data["affiliate_enabled"] == 1 && $spg["affiliate_type"] != $param["affiliate_type"]) {
					$str = "";
					if ($spg["affiliate_type"] == 1) {
						$str = "金额";
					} else {
						$str = "百分比";
					}
					if ($param["affiliate_type"] == 1) {
						$desc .= "产品比例类型由“" . $str . "”改为“金额”，";
					} else {
						$desc .= "产品比例类型由“" . $str . "”改为“百分比”，";
					}
				}
				if ($data["affiliate_is_reorder"] == 1 && $spg["affiliate_is_reorder"] != $param["affiliate_is_reorder"]) {
					if ($spg["affiliate_is_reorder"] == 1) {
						$desc .= "二次订购由“关闭”改为“开启”，";
					} else {
						$desc .= "二次订购由“开启”改为“关闭”，";
					}
				}
				if ($data["affiliate_is_reorder"] == 1 && $spg["affiliate_reorder"] != $param["affiliate_reorder"]) {
					$desc .= " 二次订购比例" . $spg["affiliate_reorder"] . "改为" . $param["affiliate_reorder"];
				}
				if ($data["affiliate_is_renew"] == 1 && $spg["affiliate_is_renew"] != $param["affiliate_is_renew"]) {
					if ($spg["affiliate_is_renew"] == 1) {
						$desc .= "续费由“关闭”改为“开启”，";
					} else {
						$desc .= "续费由“开启”改为“关闭”，";
					}
				}
				if ($data["affiliate_is_renew"] == 1 && $spg["affiliate_renew"] != $param["affiliate_renew"]) {
					$desc .= " 续费比例" . $spg["affiliate_renew"] . "改为" . $param["affiliate_renew"];
				}
				if ($spg["affiliate_enabled"] != $param["affiliate_enabled"]) {
					if ($spg["affiliate_enabled"] == 1) {
						$desc .= "产品推介计划由“关闭”改为“开启”";
					} else {
						$desc .= "产品推介计划由“开启”改为“关闭”";
					}
				}
				\think\Db::name("affiliates_product_setting")->where("id", $param["id"])->update($data);
				if (empty($desc)) {
					$desc .= "未做任何修改";
				}
				active_log_final(sprintf($this->lang["Aff_admin_productaffiPost"], $spg["id"], $desc));
			}
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	public function getids($uid)
	{
		$data = \think\Db::name("affiliates")->where("uid", $uid)->find();
		$au = \think\Db::name("affiliates_user")->field("uid")->where("affid", $data["id"])->select()->toArray();
		$uids = 0;
		foreach ($au as $key => $value) {
			if ($key == 0) {
				$uids .= $value["uid"];
			} else {
				$uids .= "," . $value["uid"];
			}
		}
		return $uids;
	}
	/**
	 * @title 推荐计划提现记录
	 * @description 接口说明:
	 * @param .name:id type:id require:1 default:1 other: desc:用户id
	 * @author lgd
	 * @url /admin/aff/affiwithdraw_record
	 * @method post
	 * @param .name:user_nickname type:string require:0  other: desc:用户名
	 * @param .name:status type:int require:0  other: desc:状态1待审核2通过3拒绝
	 * @param .name:page type:int require:0  other: desc:页码
	 * @param .name:limit type:int require:0  other: desc:长度
	 * @param .name:order type:string require:0  other: desc:排序字段
	 * @param .name:sort type:string require:0  other: desc:排序规则(asc/desc)
	 * @return data:基础数据@
	 * @data  id:id
	 * @data  num:金额
	 * @data  type:1余额2仅记录3流水支持
	 * @data  user_nickname:操作人
	 * @data  status:1待审核2审核通过3拒绝
	 * @data  reason:拒绝原因
	 * @data  create_time:时间
	 */
	public function affiwithdrawrecord(\think\Request $request)
	{
		$params = $this->request->param();
		$page = input("page") ?? config("page");
		$limit = input("limit") ?? config("limit");
		if (!!input("order")) {
			if (input("order") == "user_nickname") {
				$order = "u." . input("order");
			} else {
				$order = "a." . input("order");
			}
		} else {
			$order = "a.id";
		}
		$sort = input("sort") ?? "desc";
		$rows = \think\Db::name("affiliates_withdraw")->alias("a")->join("clients c", "c.id=a.uid")->leftJoin("currencies cu", "cu.id = c.currency")->leftJoin("user u", "a.admin_id=u.id")->field("c.id as uid,a.id,a.num,a.type,a.create_time,a.status,a.reason,u.user_nickname,cu.suffix,c.username,c.companyname")->where(function (\think\db\Query $query) use($params) {
			if (!empty($params["user_nickname"])) {
				$search_desc = $params["user_nickname"];
				$query->where("c.username LIKE '%{$search_desc}%'");
			}
			if (!empty($params["status"])) {
				$search_desc = $params["status"];
				$query->where("a.status", $search_desc);
			}
		});
		if ($this->user["id"] != 1 && $this->user["is_sale"]) {
			$rows->whereIn("c.id", $this->str);
		}
		$rows = $rows->page($page)->limit($limit)->order($order, $sort)->select()->toArray();
		$total = \think\Db::name("affiliates_withdraw")->alias("a")->join("clients c", "c.id=a.uid")->leftJoin("currencies cu", "cu.id = c.currency")->leftJoin("user u", "a.admin_id=u.id")->field("c.id as uid,a.id,a.num,a.type,a.create_time,a.status,a.reason,u.user_nickname,cu.suffix,c.username,c.companyname")->where(function (\think\db\Query $query) use($params) {
			if (!empty($params["user_nickname"])) {
				$search_desc = $params["user_nickname"];
				$query->where("c.username LIKE '%{$search_desc}%'");
			}
			if (!empty($params["status"])) {
				$search_desc = $params["status"];
				$query->where("a.status", $search_desc);
			}
		});
		if ($this->user["id"] != 1 && $this->user["is_sale"]) {
			$total->whereIn("c.id", $this->str);
		}
		$total = $total->count();
		foreach ($rows as $key => $value) {
			$str = $rows[$key]["companyname"] ? $rows[$key]["username"] . "(" . $rows[$key]["companyname"] . ")" : $rows[$key]["username"];
			$rows[$key]["username"] = "<a class=\"el-link el-link--primary is-underline\"
            href=\"#/customer-view/promotion_plan?id=" . $value["uid"] . "\">
            <span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
		}
		return jsonrule(["status" => 200, "data" => $rows, "msg" => lang("SUCCESS MESSAGE"), "total" => $total]);
	}
	/**
	 * @title 提现记录审核
	 * @description 接口说明:
	 * @param .name:id type:id require:1 default:1 other: desc:affid
	 * @param .name:status type:int require:0  other: desc:1待审核2审核通过3拒绝
	 * @param .name:reason type:string require:0  other: desc:拒绝原因
	 * @param .name:type type:string require:0  other: desc:1余额2仅记录3流水支持
	 * @param .name:payment type:string require:0 default:1 other: desc:支付方式
	 * @param .name:trans_id type:string require:0 default:1 other: desc:付款流水号
	 * @author lgd
	 * @url /admin/aff/affiwithdrawsh
	 * @method post
	 */
	public function affiwithdrawsh()
	{
		if ($this->request->isPost()) {
			$sessionAdminId = session("ADMIN_ID");
			$param = $this->request->param();
			$type = isset($param["type"]) ? floatval($param["type"]) : 1;
			$status = isset($param["status"]) ? floatval($param["status"]) : 2;
			$payment = $param["payment"] ?? "AliPay";
			$trans_id = $param["trans_id"] ?? "";
			$desc = "";
			$aw = \think\Db::name("affiliates_withdraw")->where("id", $param["id"])->find();
			if ($aw["status"] == 1) {
				if ($status == 2) {
					\think\Db::startTrans();
					try {
						if ($type == 1) {
							db("clients")->where("id", $aw["uid"])->setInc("credit", $aw["num"]);
							credit_log(["uid" => $aw["uid"], "amount" => $aw["num"], "desc" => lang("AFFIWITHDRAW")]);
							$desc .= " " . lang("AFF_CREATE_TYPE");
						} elseif ($type == 3) {
							$cli = db("clients")->alias("c")->leftJoin("currencies cu", "cu.id = c.currency")->field("cu.code")->where("c.id", $aw["uid"])->find();
							$currency = $cli["currency"] ?? "CNY";
							$time = time();
							$accountsData = ["uid" => $aw["uid"], "currency" => $currency, "gateway" => $payment, "create_time" => $time, "pay_time" => time(), "amount_out" => $aw["num"], "fees" => "", "amount_in" => 0, "rate" => 1, "trans_id" => $trans_id, "invoice_id" => 0, "refund" => 0, "description" => lang("AFFIWITHDRAW")];
							\think\Db::name("accounts")->insert($accountsData);
							$desc .= " " . lang("AFF_ACCOUNT_TYPE");
						}
						\think\Db::name("affiliates_withdraw")->where("id", $param["id"])->update(["status" => 2, "type" => $type, "admin_id" => $sessionAdminId, "update_time" => time()]);
						$affi = \think\Db::name("affiliates")->field("balance,withdrawn,withdraw_ing")->where("uid", $aw["uid"])->find();
						\think\Db::name("affiliates")->where("uid", $aw["uid"])->update(["withdraw_ing" => bcsub($affi["withdraw_ing"], $aw["num"], 2), "withdrawn" => bcadd($affi["withdrawn"], $aw["num"], 2), "updated_time" => time()]);
						$desc .= " 正在提现余额变化" . $affi["balance"] . "改为" . bcsub($affi["balance"], $aw["num"], 2);
						$desc .= " 已提现变化" . $affi["withdrawn"] . "改为" . bcadd($affi["withdrawn"], $aw["num"], 2);
						active_log_final(sprintf($this->lang["Aff_admin_withdrawsh"], $param["id"], $aw["uid"], $desc), $aw["uid"]);
						\think\Db::commit();
					} catch (\Exception $e) {
						return jsons(["status" => 400, "msg" => $e->getMessage()]);
						\think\Db::rollback();
					}
				} elseif ($status == 3) {
					$rows = \think\Db::name("affiliates_withdraw")->where("id", $param["id"])->update(["status" => 3, "reason" => $param["reason"], "admin_id" => $sessionAdminId, "update_time" => time()]);
					$data = \think\Db::name("affiliates")->where("uid", $aw["uid"])->find();
					\think\Db::name("affiliates")->where("uid", $aw["uid"])->update(["balance" => bcadd($data["balance"], $aw["num"], 2), "withdraw_ing" => bcsub($data["withdraw_ing"], $aw["num"], 2), "updated_time" => time()]);
					active_log_final(sprintf($this->lang["Aff_admin_withdrawsh"], $param["id"], $aw["uid"], $param["reason"]), $aw["uid"]);
				} else {
					return jsonrule(["status" => 400, "msg" => lang("STATUS_PARAM_ERROR")]);
				}
				return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
			} else {
				return jsonrule(["status" => 400, "msg" => lang("STATUS_ERROR_EX")]);
			}
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 推介计划支付方式
	 * @description 接口说明:推介计划支付方式
	 * @author lgd
	 * @url /admin/aff/gateway_list
	 * @method get
	 * @return  gateway:支付方式@
	 */
	public function gatewaylist(\think\Request $request)
	{
		return jsonrule(["status" => 200, "gateway" => gateway_list(), "msg" => lang("SUCCESS MESSAGE")]);
	}
	public function test()
	{
		$rows = \think\Db::name("orders")->alias("o")->join("clients c", "o.uid=c.id")->leftJoin("currencies cu", "cu.id = c.currency")->rightJoin("invoices i", "i.id=o.invoiceid")->field("i.status,i.type,i.subtotal,i.paid_time,c.username,o.id,o.uid,c.id as uid,o.status,o.create_time,o.invoiceid,o.amount,o.payment,cu.prefix,cu.suffix")->where("i.delete_time", 0)->where("i.status", "=", "Paid")->where("i.is_aff", 0)->where(function (\think\db\Query $query) {
			$time = time() - configuration("affiliate_delay_commission") * 24 * 60 * 60;
			$start_time = strtotime(date("Y-m-d", $time));
			$end_time = strtotime(date("Y-m-d", $time)) + 86400;
			$query->where("i.paid_time", "<=", $end_time);
			$query->where("i.paid_time", ">=", $start_time);
		})->select()->toArray();
		foreach ($rows as $k => $value) {
			$arr = [];
			$af = \think\Db::name("affiliates_user")->where("uid", $value["uid"])->find();
			if (!empty($af)) {
				$affi = \think\Db::name("affiliates")->where("id", $af["affid"])->find();
				if (!empty($affi)) {
					\think\Db::startTrans();
					try {
						$arr[] = $value;
						$uids = $this->getids($affi["uid"]);
						$ladder = getLadder($affi["uid"], $uids);
						$rows = dealCommissionaff($arr, $ladder, $affi["uid"]);
						$res = \think\Db::name("invoices")->where("id", $value["invoiceid"])->update(["is_aff" => 1, "aff_sure_time" => time(), "aff_commission" => $rows[0]["commission"], "aff_commmission_bates" => $rows[0]["commission_bates"], "aff_commmission_bates_type" => $rows[0]["commission_bates_type"]]);
						$res = \think\Db::name("affiliates")->where("id", $affi["id"])->update(["balance" => bcadd($rows[0]["commission"], $affi["balance"], 2), "updated_time" => time()]);
						\think\Db::commit();
						active_log_final(sprintf($this->lang["Aff_cron_update_balance"], $affi["uid"], $affi["balance"], bcadd($rows[0]["commission"], $affi["balance"], 2)), $affi["uid"]);
					} catch (\Exception $e) {
						\think\Db::rollback();
					}
				}
			}
		}
	}
}