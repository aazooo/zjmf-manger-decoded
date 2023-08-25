<?php

namespace app\admin\controller;

/**
 * @title 产品/服务列表页
 * @description 接口说明
 */
class HostController extends GetUserController
{
	/**
	 * @title 产品/服务列表页数据接口，带搜索
	 * @description 接口说明:后台用户产品服务内页
	 * @author 萧十一郎
	 * @url /admin/host/list
	 * @method GET
	 * @param .name:page type:number require:0 default:1 other: desc:页码
	 * @param .name:pagecount type:number require:0 default: other: desc:每页显示条数
	 * @param .name:order type:string require:0 default:id other: desc:排序字段(id,uid,productid,billingcycle,payment,nextduedate,dedicatedip,username,productname)
	 * @param .name:sort type:string require:0 default:ASC other: desc:排序方式
	 * @param .name:product_type type:string require:0 default: other: desc:产品类型(搜索字段)
	 * @param .name:uid type:int require:0 default: other: desc:用户名(搜索字段)
	 * @param .name:name type:string require:0 default: other: desc:产品名(搜索字段)
	 * @param .name:server type:number require:0 default: other: desc:服务器id(搜索字段)
	 * @param .name:product type:number require:0 default: other: desc:产品id(搜索字段)
	 * @param .name:payment type:string require:0 default: other: desc:支付方式(搜索字段)
	 * @param .name:billingcycle type:string require:0 default: other: desc:付款周期(搜索字段)
	 * @param .name:domainstatus type:string require:0 default: other: desc:主机状态(搜索字段)
	 * @param .name:domain type:string require:0 default: other: desc:主机名(搜索字段)
	 * @param .name:ip type:string require:0 default: other: desc:ip(搜索字段)
	 * @param .name:nextduedate type:int require:0 default: other: desc:到期时间
	 * @param .name:start_time type:int require:0 default: other: desc:开始时间
	 * @param .name:end_time type:int require:0 default: other: desc:结束时间
	 * @return base:基础数据(搜索区)@
	 * @base  billingcycle:周期
	 * @base  gateway_list:支付方式
	 * @gateway_list  id:网关id
	 * @gateway_list  module:网关value
	 * @gateway_list  title:网关名
	 * @base  product_list:产品列表@
	 * @product_list  id:分组id
	 * @product_list  groupname:分组名称
	 * @product_list  clild:产品数组@
	 * @clild  id:产品id
	 * @clild  productname:产品名称
	 * @base  product_type:产品类型
	 * @base  server_list:服务器列表
	 * @base  domainstatus:服务器状态
	 * @return list:数据列表@
	 * @list  id:主机id
	 * @list  dedicatedip:独立ip
	 * @list  billingcycle:周期
	 * @list  dedicatedip:主ip地址
	 * @list  assignedips:附加ip地址
	 * @list  nextduedate:到期时间
	 * @list  payment:付款方式
	 * @list  productid:产品id
	 * @list  productname:产品名
	 * @list  productname:状态'Pending','Active','Suspended','Terminated','Cancelled','Fraud','Completed'
	 * @list  uid:用户id
	 * @list  amount:价格
	 * @list  regdate:开通时间
	 * @list  dedicatedip:ip地址
	 * @list  type:产品类型(shared hosting,reseller hosting,server/VPNS,other)
	 * @list  username:用户名
	 * @list  sale_name:显示销售
	 * @return pagination:分页相关数据@
	 * @pagination  count:总数量
	 * @pagination  total_page:总页码
	 * @pagination  pagecount:每页数量
	 * @pagination  page:当前页码
	 * @pagination  orderby:排序字段
	 * @pagination  sorting:排序方式
	 * @return search:搜索参数@
	 * @search  billingcycle:周期
	 * @search  domainstatus:主机状态
	 * @search  payment:支付方式
	 * @search  product:产品id
	 * @search  product_type:产品类型
	 * @search  server:服务器id
	 */
	public function getList(\think\Request $request)
	{
		$param = $request->param();
		$pagecount = intval($param["pagecount"]) ?: configuration("NumRecordstoDisplay");
		$pagecount = $pagecount ?: 0;
		$order = isset($param["order"][0]) ? trim($param["order"]) : "h.id";
		$sort = isset($param["sort"][0]) ? trim($param["sort"]) : "DESC";
		$page = intval($param["page"]) ?: 1;
		$limit_start = ($page - 1) * $pagecount;
		$uid = $param["uid"];
		$name = $param["name"];
		$product_type = $param["product_type"];
		$server = $param["server"];
		$product = $param["product"];
		$payment = $param["payment"];
		$billingcycle = $param["billingcycle"];
		$domainstatus = $param["domainstatus"];
		$domain = $param["domain"];
		$dedicatedip = $param["ip"];
		$start_time = $param["start_time"];
		$where = [];
		if (isset($param["username"])) {
			$username = $param["username"];
			$where[] = ["c.username", "like", "%{$username}%"];
		}
		if (!empty($uid)) {
			$where[] = ["h.uid", "=", $uid];
		}
		if (!empty($name)) {
			$where[] = ["p.name", "=", $name];
		}
		if (!empty($product_type)) {
			$where[] = ["p.type", "=", $product_type];
		}
		if (!empty($server)) {
			$where[] = ["h.serverid", "=", $server];
		}
		if (!empty($product)) {
			$where[] = ["h.productid", "=", $product];
		}
		if (!empty($payment)) {
			$where[] = ["h.payment", "=", $payment];
		}
		if (!empty($billingcycle)) {
			$where[] = ["h.billingcycle", "=", $billingcycle];
		}
		if (!empty($domainstatus) && $domainstatus !== "All") {
			$where[] = ["h.domainstatus", "=", $domainstatus];
		}
		if (!empty($domain)) {
			$where[] = ["h.domain", "like", "%" . $domain . "%"];
		}
		if (!empty($dedicatedip)) {
			$where[] = ["h.dedicatedip|h.assignedips", "like", "%" . $dedicatedip . "%"];
		}
		if ($this->user["id"] != 1 && $this->user["is_sale"]) {
			$where[] = ["h.uid", "in", $this->str];
		}
		if (!empty($param["nextduedate"])) {
			if ($param["nextduedate"] == 1) {
				$where[] = ["h.nextduedate", "egt", strtotime(date("Y-m-d", time()))];
				$where[] = ["h.nextduedate", "elt", strtotime(date("Y-m-d 23:59:59", time()))];
			} elseif ($param["nextduedate"] == 2) {
				$where[] = ["h.nextduedate", "egt", strtotime(date("Y-m-d", time()))];
				$where[] = ["h.nextduedate", "elt", strtotime(date("Y-m-d 23:59:59", time() + 259200))];
			} elseif ($param["nextduedate"] == 3) {
				$where[] = ["h.nextduedate", "egt", strtotime(date("Y-m-d", time()))];
				$where[] = ["h.nextduedate", "elt", strtotime(date("Y-m-d 23:59:59", time() + 604800))];
			} elseif ($param["nextduedate"] == 4) {
				$where[] = ["h.nextduedate", "egt", strtotime(date("Y-m-d", time()))];
				$where[] = ["h.nextduedate", "elt", strtotime(date("Y-m-d 23:59:59", time() + 2592000))];
			} elseif ($param["nextduedate"] == 6) {
				$where[] = ["h.nextduedate", ">", 0];
				$where[] = ["h.nextduedate", "elt", time()];
			} else {
				if ($start_time[0]) {
					$where[] = ["h.nextduedate", "egt", $start_time[0]];
				}
				if ($start_time[1]) {
					$where[] = ["h.nextduedate", "elt", $start_time[1]];
				}
			}
		}
		$total_arr = \think\Db::name("host")->alias("h")->field("h.firstpaymentamount,h.amount,h.billingcycle")->leftJoin("products p", "p.id=h.productid")->leftJoin("clients c", "c.id=h.uid")->where($where)->cursor();
		$total = 0;
		foreach ($total_arr as $val) {
			if ($val["billingcycle"] == "onetime") {
				$val["amount"] = $val["firstpaymentamount"];
			}
			$total = bcadd($total, $val["amount"], 2);
		}
		$host_list = \think\Db::name("host")->field("h.firstpaymentamount")->field("cr.type as crtype,cr.reason,h.id,h.initiative_renew,h.domain,h.uid,h.dedicatedip,h.productid,h.billingcycle,h.domain,h.payment,h.nextduedate,h.assignedips,h.regdate,h.dedicatedip,h.amount,h.domainstatus,c.username,p.name as productname,c.currency,p.type,h.serverid,u.user_nickname")->alias("h")->leftJoin("products p", "p.id=h.productid")->leftJoin("clients c", "c.id=h.uid")->leftJoin("user u", "c.sale_id=u.id")->leftJoin("cancel_requests cr", "cr.relid = h.id")->where($where)->order($order, $sort)->order("h.id", "desc")->limit($limit_start, $pagecount)->select()->toArray();
		$tmp = \think\Db::name("currencies")->select()->toArray();
		$currency = array_column($tmp, null, "id");
		$page_total = 0;
		foreach ($host_list as &$v) {
			if ($v["billingcycle"] == "onetime") {
				$v["amount"] = $v["firstpaymentamount"];
			}
			$page_total = bcadd($page_total, $v["amount"], 2);
			$v["status_color"] = config("public.domainstatus")[$v["domainstatus"]]["color"];
			$v["assignedips"] = !empty(explode(",", $v["assignedips"])[0]) ? explode(",", $v["assignedips"]) : [];
			$v["domainstatus"] = $v["domainstatus"] ? config("public.domainstatus")[$v["domainstatus"]] : ["name" => "未知状态", "color" => "#FF5722"];
			$v["amount"] = $currency[$v["currency"]]["prefix"] . $v["amount"] . $currency[$v["currency"]]["suffix"];
			$v["assignedips"] = array_filter($v["assignedips"]);
			if (!empty($v["crtype"])) {
				if ($v["crtype"] == "Immediate") {
					$v["crtype"] = "立即停用";
				} else {
					$v["crtype"] = "到期时停用";
				}
				$v["cancel_list"] = ["crtype" => $v["crtype"], "reason" => $v["reason"]];
			}
		}
		$count = \think\Db::name("host")->alias("h")->leftJoin("products p", "p.id=h.productid")->leftJoin("clients c", "c.id=h.uid")->leftJoin("cancel_requests cr", "cr.relid = h.id")->where($where)->count();
		$returndata = [];
		$returndata["list"] = $host_list;
		$returndata["pagination"]["pagecount"] = $pagecount;
		$returndata["pagination"]["page"] = $page;
		$returndata["pagination"]["orderby"] = $order;
		$returndata["pagination"]["sorting"] = $sort;
		$returndata["pagination"]["total_page"] = ceil($count / $pagecount);
		$returndata["pagination"]["count"] = $count;
		$returndata["search"]["product_type"] = $product_type ?: "";
		$returndata["search"]["server"] = $server ?: "";
		$returndata["search"]["product"] = $product ?: "";
		$returndata["search"]["payment"] = $payment ?: "";
		$returndata["search"]["billingcycle"] = $billingcycle ?: "";
		$returndata["search"]["domainstatus"] = $domainstatus ?: "";
		$prefix = $currency[$returndata["list"][0]["currency"]]["prefix"];
		$suffix = $currency[$returndata["list"][0]["currency"]]["suffix"];
		$returndata["total"] = $prefix . $total . $suffix;
		$returndata["page_total"] = $prefix . $page_total . $suffix;
		return jsonrule(["status" => 200, "data" => $returndata]);
	}
	/**
	 * @title 获取时间类型
	 * @description 接口说明: 获取时间类型
	 * @author lgd
	 * @url /admin/host/get_timetype
	 * @method GET
	 * @return data:基础数据(搜索区)@
	 * @base  nextduedate:时间周期
	 * @base  name:时间周期
	 */
	public function getTimetype(\think\Request $request)
	{
		$returndata["time_type"] = config("time_type");
		$returndata["product_type"] = config("product_type");
		$returndata["billingcycle"] = config("billing_cycle");
		$returndata["server_list"] = \think\Db::name("servers")->field("id,name")->select()->toArray();
		$product_groups = \think\Db::name("product_groups")->field("id,name as groupname")->select();
		$product_list = [];
		$i = 0;
		foreach ($product_groups as $key => $val) {
			$groupid = $val["id"];
			$product_list[$i] = $val;
			$product_list[$i]["clild"] = \think\Db::name("products")->field("id,name as productname")->where("gid", $groupid)->select()->toArray();
			$i++;
		}
		$returndata["product_list"] = $product_list;
		$returndata["gateway_list"] = gateway_list1("gateways");
		$returndata["domainstatus"] = config("domainstatus");
		$users = [];
		$returndata["clientlist"] = $users;
		return jsonrule(["status" => 200, "data" => $returndata]);
	}
	/**
	 * @title 获取用户信息
	 * @description 接口说明: 获取用户信息
	 * @author xue
	 * @url /admin/host/userInfo
	 * @method post
	 */
	public function userInfo(\think\Request $req)
	{
		$info = \think\Db::name("clients")->field("usertype,username,companyname,email,qq,lastloginip as ip,address1,phone_code,phonenumber,notes")->where("id", $req->uid)->find();
		if (empty($info)) {
			return jsonrule(["status" => 400, "msg" => "用户信息不存在"]);
		}
		if ($info["usertype"] == 1) {
			$info["usertype"] = "普通用户";
		}
		if ($info["usertype"] == 2) {
			$info["usertype"] = "会员";
		}
		return jsonrule(["status" => 200, "data" => $info]);
	}
}