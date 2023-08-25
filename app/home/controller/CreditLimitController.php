<?php

namespace app\home\controller;

/**
 * @title 前台 用户信用额管理
 */
class CreditLimitController extends \cmf\controller\HomeBaseController
{
	/**
	 * @title 用户信用额列表
	 * @description 接口说明:
	 * @author xj
	 * @url /credit_limit
	 * @method get
	 * @param .name:uid type:int require:0  other: desc:
	 * @return .username:用户名
	 * @throws
	 */
	public function index()
	{
		$data = $this->request->param();
		$uid = input("uid");
		$user = \think\Db::name("clients")->alias("a")->field("username,phonenumber,email,is_open_credit_limit,credit_limit,repayment_date,bill_generation_date,bill_repayment_period,credit_limit_create_time,b.prefix,b.suffix")->leftJoin("currencies b", "a.currency = b.id")->where("a.id", $uid)->find();
		$user["certify"] = checkCertify($uid);
		$user["is_open_credit_limit"] = configuration("shd_credit_limit") == 1 ? configuration("credit_limit") == 1 ? $user["is_open_credit_limit"] : 0 : 0;
		$day = \intval(date("d"));
		$user["bill_generation_date"] = $user["bill_generation_date"] > 0 ? $user["bill_generation_date"] : 1;
		if ($day < $user["bill_generation_date"]) {
			$year = \intval(date("Y"));
			$month = \intval(date("m"));
			$days = date("t", strtotime($year . "-" . $month));
			if ($days < $user["bill_generation_date"]) {
				if ($day < $days) {
					$user["next_bill_generation_date"] = $month . "-" . $days;
				} else {
					if ($month == 12) {
						$year++;
						$month++;
						$days = date("t", strtotime($year . "-" . $month));
						if ($days < $user["bill_generation_date"]) {
							$user["next_bill_generation_date"] = $month . "-" . $days;
						} else {
							$user["next_bill_generation_date"] = $month . "-" . $user["bill_generation_date"];
						}
					} else {
						$month++;
						$days = date("t", strtotime($year . "-" . $month));
						if ($days < $user["bill_generation_date"]) {
							$user["next_bill_generation_date"] = $month . "-" . $days;
						} else {
							$user["next_bill_generation_date"] = $month . "-" . $user["bill_generation_date"];
						}
					}
				}
			} else {
				$user["next_bill_generation_date"] = $month . "-" . $user["bill_generation_date"];
			}
		} else {
			$year = \intval(date("Y"));
			$month = \intval(date("m"));
			if ($month == 12) {
				$year++;
				$month++;
				$days = date("t", strtotime($year . "-" . $month));
				if ($days < $user["bill_generation_date"]) {
					$user["next_bill_generation_date"] = $month . "-" . $days;
				} else {
					$user["next_bill_generation_date"] = $month . "-" . $user["bill_generation_date"];
				}
			} else {
				$month++;
				$days = date("t", strtotime($year . "-" . $month));
				if ($days < $user["bill_generation_date"]) {
					$user["next_bill_generation_date"] = $month . "-" . $days;
				} else {
					$user["next_bill_generation_date"] = $month . "-" . $user["bill_generation_date"];
				}
			}
		}
		$user["amount_to_be_settled"] = \think\Db::name("invoices")->where("status", "Paid")->where("use_credit_limit", 1)->where("invoice_id", 0)->where("is_delete", 0)->where("uid", $uid)->sum("total");
		$unpaid = \think\Db::name("invoices")->where("type", "credit_limit")->where("status", "Unpaid")->where("is_delete", 0)->where("uid", $uid)->sum("total");
		$user["credit_limit_used"] = number_format($user["amount_to_be_settled"] + $unpaid, 2, ".", "");
		$user["credit_limit_balance"] = number_format($user["credit_limit"] - $user["credit_limit_used"] > 0 ? $user["credit_limit"] - $user["credit_limit_used"] : 0, 2, ".", "");
		$user["amount_to_be_settled"] = bcadd($user["amount_to_be_settled"], 0, 2);
		if ($user["credit_limit_used"] >= $user["credit_limit"]) {
			$user["credit_limit_used_percent"] = 100;
		} else {
			$user["credit_limit_used_percent"] = round($user["credit_limit_used"] / $user["credit_limit"] * 100, 0);
		}
		$user["this_month_bill"] = \think\Db::name("invoices")->where("type", "credit_limit")->where("create_time", ">=", strtotime(date("Y-m")))->where("is_delete", 0)->where("credit_limit_prepayment", 0)->where("uid", $uid)->order("create_time", "desc")->find();
		return jsonrule(["user" => $user, "status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * @title 用户信用额使用记录
	 * @description 接口说明:
	 * @param .name:uid type:int require:0  other: desc:用户ID
	 * @param .name:page type:int require:0  other: desc:页码
	 * @param .name:limit type:int require:0  other: desc:页长
	 * @param .name:order type:mix require:0  other: desc:排序字段
	 * @param .name:sort type:string require:0  other: desc:排序desc/asc
	 * @param .name:payment type:string require:0  other: desc:按付款方式搜索
	 * @param .name:status type:string require:0  other: desc:按支付状态搜索
	 * @param .name:create_time type:string require:0  other: desc:按账单生成日搜索
	 * @param .name:due_time type:string require:0  other: desc:按账单逾期日搜索
	 * @param .name:paid_time type:string require:0  other: desc:按账单支付日搜索
	 * @param .name:subtotal_small type:string require:0  other: desc:按总计搜索(小值)
	 * @param .name:subtotal_big type:string require:0  other: desc:按总计搜索(大值)
	 * @return  data:账单列表@
	 * @data id:账单ID
	 * @data create_time:账单生成日
	 * @data due_time:账单逾期日
	 * @data paid_time:账单支付日
	 * @data subtotal:总计
	 * @data payment:付款方式
	 * @data status:状态(Paid:已支付,Unpaid:未支付,Draft:已草稿,Overdue:已逾期,Cancelled:被取消,Refunded:已退款,Collections:已收藏)
	 * @throws
	 * @author xj
	 * @url /credit_limit/list
	 * @method get
	 */
	public function list()
	{
		$params = $this->request->param();
		$uid = isset($params["uid"]) ? intval($params["uid"]) : "";
		if (!$uid) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$page = input("get.page", 1, "intval");
		$limit = input("get.limit", 10, "intval");
		$order = input("get.order", "id");
		$sort = input("get.sort", "DESC");
		$page = $page >= 1 ? $page : config("page");
		$limit = $limit >= 1 ? $limit : config("limit");
		if (!in_array($order, ["id", "create_time", "due_time", "paid_time", "subtotal", "payment", "status"])) {
			$order = "id";
		}
		if (!in_array($sort, ["asc", "desc"])) {
			$sort = "asc";
		}
		$where = $this->get_search($params);
		$count = \think\Db::name("invoices")->alias("i")->leftjoin("clients c", "c.id=i.uid")->leftJoin("currencies cu", "cu.id = c.currency")->leftJoin("invoice_items t", "t.invoice_id = i.id")->field("i.id,i.create_time,i.due_time,i.paid_time,i.subtotal,i.payment,i.status,cu.prefix,cu.suffix,i.type")->where($where)->where("i.use_credit_limit", 1)->where("i.uid", $uid)->count();
		$type_arr = ["renew" => "续费", "product" => "产品", "recharge" => "充值", "setup" => "初装费", "upgrade" => "产品升降级", "credit_limit" => "信用额"];
		$gateways = gateway_list();
		$invoices = \think\Db::name("invoices")->alias("i")->leftjoin("clients c", "c.id=i.uid")->leftJoin("currencies cu", "cu.id = c.currency")->leftJoin("invoice_items t", "t.invoice_id = i.id")->field("i.id,i.create_time,i.due_time,i.paid_time,i.subtotal,i.subtotal as sub,i.total,i.credit,i.payment,i.status,cu.prefix,cu.suffix,i.type,i.use_credit_limit")->where($where)->where("i.use_credit_limit", 1)->where("i.uid", $uid)->withAttr("payment", function ($value, $data) use($gateways) {
			if ($data["status"] == "Paid") {
				if ($data["use_credit_limit"] == 1) {
					return "信用额";
				} else {
					if ($data["sub"] == $data["credit"]) {
						return "余额支付";
					} else {
						if ($data["sub"] > $data["credit"] && $data["credit"] > 0) {
							foreach ($gateways as $v) {
								if ($v["name"] == $value) {
									return "部分余额支付+" . $v["title"];
								}
							}
						} else {
							foreach ($gateways as $v) {
								if ($v["name"] == $value) {
									return $v["title"];
								}
							}
						}
					}
				}
			} else {
				foreach ($gateways as $v) {
					if ($v["name"] == $value) {
						return $v["title"];
					}
				}
			}
		})->withAttr("paid_time", function ($value, $data) {
			if ($data["status"] == "Unpaid" || $data["status"] == "Cancelled") {
				return "";
			} else {
				return $value;
			}
		})->withAttr("subtotal", function ($value, $data) {
			return $data["prefix"] . $value . $data["suffix"];
		})->withAttr("type", function ($value) use($type_arr) {
			return $type_arr[$value];
		})->order($order, $sort)->page($page)->limit($limit)->group("i.id")->select()->toArray();
		$status = config("invoice_payment_status");
		$gateway = gateway_list();
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "count" => $count, "invoices" => $invoices, "invoice_status" => $status]);
	}
	/**
	 * @title 用户账单列表
	 * @description 接口说明:
	 * @param .name:uid type:int require:0  other: desc:用户ID
	 * @param .name:page type:int require:0  other: desc:页码
	 * @param .name:limit type:int require:0  other: desc:页长
	 * @param .name:order type:mix require:0  other: desc:排序字段
	 * @param .name:sort type:string require:0  other: desc:排序desc/asc
	 * @param .name:payment type:string require:0  other: desc:按付款方式搜索
	 * @param .name:status type:string require:0  other: desc:按支付状态搜索
	 * @param .name:create_time type:string require:0  other: desc:按账单生成日搜索
	 * @param .name:due_time type:string require:0  other: desc:按账单逾期日搜索
	 * @param .name:paid_time type:string require:0  other: desc:按账单支付日搜索
	 * @param .name:subtotal_small type:string require:0  other: desc:按总计搜索(小值)
	 * @param .name:subtotal_big type:string require:0  other: desc:按总计搜索(大值)
	 * @return  data:账单列表@
	 * @data id:账单ID
	 * @data create_time:账单生成日
	 * @data due_time:账单逾期日
	 * @data paid_time:账单支付日
	 * @data subtotal:总计
	 * @data payment:付款方式
	 * @data status:状态(Paid:已支付,Unpaid:未支付,Draft:已草稿,Overdue:已逾期,Cancelled:被取消,Refunded:已退款,Collections:已收藏)
	 * @throws
	 * @author xj
	 * @url /credit_limit/user_invoice
	 * @method get
	 */
	public function userInvoice()
	{
		$params = $this->request->param();
		$uid = isset($params["uid"]) ? intval($params["uid"]) : "";
		if (!$uid) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$page = input("get.page", 1, "intval");
		$limit = input("get.limit", 10, "intval");
		$order = input("get.order", "id");
		$sort = input("get.sort", "DESC");
		$page = $page >= 1 ? $page : config("page");
		$limit = $limit >= 1 ? $limit : config("limit");
		if (!in_array($order, ["id", "create_time", "due_time", "paid_time", "subtotal", "payment", "status"])) {
			$order = "id";
		}
		if (!in_array($sort, ["asc", "desc"])) {
			$sort = "asc";
		}
		$where = $this->get_search($params);
		$count = \think\Db::name("invoices")->alias("i")->leftjoin("clients c", "c.id=i.uid")->leftJoin("currencies cu", "cu.id = c.currency")->field("i.id,i.create_time,i.due_time,i.paid_time,i.subtotal,i.payment,i.status,cu.prefix,cu.suffix,i.type")->where($where)->where("i.type", "credit_limit")->where("i.uid", $uid)->count();
		$type_arr = ["renew" => "续费", "product" => "产品", "recharge" => "充值", "setup" => "初装费", "upgrade" => "产品升降级", "credit_limit" => "信用额"];
		$gateways = gateway_list();
		$invoices = \think\Db::name("invoices")->alias("i")->leftjoin("clients c", "c.id=i.uid")->leftJoin("currencies cu", "cu.id = c.currency")->field("i.id,i.create_time,i.due_time,i.paid_time,i.subtotal,i.subtotal as sub,i.total,i.credit,i.payment,i.status,cu.prefix,cu.suffix,i.type,i.use_credit_limit")->where($where)->where("i.type", "credit_limit")->where("i.uid", $uid)->withAttr("payment", function ($value, $data) use($gateways) {
			if ($data["status"] == "Paid") {
				if ($data["use_credit_limit"] == 1) {
					return "信用额";
				} else {
					if ($data["sub"] == $data["credit"]) {
						return "余额支付";
					} else {
						if ($data["sub"] > $data["credit"] && $data["credit"] > 0) {
							foreach ($gateways as $v) {
								if ($v["name"] == $value) {
									return "部分余额支付+" . $v["title"];
								}
							}
						} else {
							foreach ($gateways as $v) {
								if ($v["name"] == $value) {
									return $v["title"];
								}
							}
						}
					}
				}
			} else {
				foreach ($gateways as $v) {
					if ($v["name"] == $value) {
						return $v["title"];
					}
				}
			}
		})->withAttr("paid_time", function ($value, $data) {
			if ($data["status"] == "Unpaid" || $data["status"] == "Cancelled") {
				return "";
			} else {
				return $value;
			}
		})->withAttr("subtotal", function ($value, $data) {
			return $data["prefix"] . $value . $data["suffix"];
		})->withAttr("type", function ($value) use($type_arr) {
			return $type_arr[$value];
		})->order($order, $sort)->page($page)->limit($limit)->group("i.id")->select()->toArray();
		$status = config("invoice_payment_status");
		$gateway = gateway_list();
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "count" => $count, "invoices" => $invoices, "invoice_status" => $status]);
	}
	/**
	 * @title 信用额账单列表
	 * @description 接口说明:
	 * @param .name:invoice_id type:int require:0  other: desc:账单ID
	 * @param .name:page type:int require:0  other: desc:页码
	 * @param .name:limit type:int require:0  other: desc:页长
	 * @param .name:order type:mix require:0  other: desc:排序字段
	 * @param .name:sort type:string require:0  other: desc:排序desc/asc
	 * @param .name:payment type:string require:0  other: desc:按付款方式搜索
	 * @param .name:status type:string require:0  other: desc:按支付状态搜索
	 * @param .name:create_time type:string require:0  other: desc:按账单生成日搜索
	 * @param .name:due_time type:string require:0  other: desc:按账单逾期日搜索
	 * @param .name:paid_time type:string require:0  other: desc:按账单支付日搜索
	 * @param .name:subtotal_small type:string require:0  other: desc:按总计搜索(小值)
	 * @param .name:subtotal_big type:string require:0  other: desc:按总计搜索(大值)
	 * @return  data:账单列表@
	 * @data id:账单ID
	 * @data create_time:账单生成日
	 * @data due_time:账单逾期日
	 * @data paid_time:账单支付日
	 * @data subtotal:总计
	 * @data payment:付款方式
	 * @data status:状态(Paid:已支付,Unpaid:未支付,Draft:已草稿,Overdue:已逾期,Cancelled:被取消,Refunded:已退款,Collections:已收藏)
	 * @throws
	 * @author xj
	 * @url /credit_limit/user_invoice_detail
	 * @method get
	 */
	public function creditLimitInvoice()
	{
		$params = $this->request->param();
		$invoice_id = isset($params["id"]) ? intval($params["id"]) : "";
		if (!$invoice_id) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$page = input("get.page", 1, "intval");
		$limit = input("get.limit", 10, "intval");
		$order = input("get.order", "id");
		$sort = input("get.sort", "DESC");
		$page = $page >= 1 ? $page : config("page");
		$limit = $limit >= 1 ? $limit : config("limit");
		if (!in_array($order, ["id", "create_time", "due_time", "paid_time", "subtotal", "payment", "status"])) {
			$order = "id";
		}
		if (!in_array($sort, ["asc", "desc"])) {
			$sort = "asc";
		}
		$detail = \think\Db::name("invoices")->alias("a")->field("b.username,b.companyname,a.status,a.status as status_zh,a.paid_time,a.payment,a.payment as payment_zh,a.subtotal,a.total,a.credit,a.id")->leftJoin("clients b", "a.uid = b.id")->where("a.id", $invoice_id)->where("a.uid", $uid)->where("a.is_delete", 0)->withAttr("status_zh", function ($value, $data) use($invoice_payment_status) {
			return $invoice_payment_status[$data["status"]] ?: "";
		})->withAttr("payment_zh", function ($value, $data) use($gateways) {
			foreach ($gateways as $v) {
				if ($v["name"] == $data["payment"]) {
					return $v["title"];
				}
			}
			return "";
		})->withAttr("credit", function ($value, $data) {
			return bcsub($data["subtotal"], $data["total"], 2);
		})->find();
		$type_arr = ["renew" => "续费", "product" => "产品", "recharge" => "充值", "setup" => "初装费", "upgrade" => "产品升降级", "credit_limit" => "信用额"];
		$gateways = gateway_list();
		$invoices = \think\Db::name("invoices")->alias("i")->leftjoin("clients c", "c.id=i.uid")->leftJoin("currencies cu", "cu.id = c.currency")->leftJoin("invoice_items t", "t.invoice_id = i.id")->leftJoin("host h", "t.rel_id = h.id")->leftJoin("products p", "p.id = h.productid")->field("i.id,i.create_time,i.due_time,i.paid_time,i.subtotal,i.subtotal as sub,i.total,i.credit,i.payment,i.status,cu.prefix,cu.suffix,i.type,i.use_credit_limit,p.name")->where("i.invoice_id", $invoice_id)->where("i.is_delete", 0)->withAttr("payment", function ($value, $data) use($gateways) {
			if ($data["status"] == "Paid") {
				if ($data["use_credit_limit"] == 1) {
					return "信用额";
				} else {
					if ($data["sub"] == $data["credit"]) {
						return "余额支付";
					} else {
						if ($data["sub"] > $data["credit"] && $data["credit"] > 0) {
							foreach ($gateways as $v) {
								if ($v["name"] == $value) {
									return "部分余额支付+" . $v["title"];
								}
							}
						} else {
							foreach ($gateways as $v) {
								if ($v["name"] == $value) {
									return $v["title"];
								}
							}
						}
					}
				}
			} else {
				foreach ($gateways as $v) {
					if ($v["name"] == $value) {
						return $v["title"];
					}
				}
			}
		})->withAttr("paid_time", function ($value, $data) {
			if ($data["status"] == "Unpaid" || $data["status"] == "Cancelled") {
				return "";
			} else {
				return $value;
			}
		})->withAttr("subtotal", function ($value, $data) {
			return $data["prefix"] . $value . $data["suffix"];
		})->withAttr("type", function ($value) use($type_arr) {
			return $type_arr[$value];
		})->order($order, $sort)->group("i.id")->select()->toArray();
		$status = config("invoice_payment_status");
		$gateway = gateway_list();
		foreach ($invoices as $k => &$v) {
			$detail = $this->getInvoicesDetail($v["id"]);
			$v["invoice_items"] = $detail["invoice_items"];
			$v["accounts"] = $detail["accounts"];
		}
		$client_currency = getUserCurrency($uid);
		$data = ["payee" => configuration("company_name"), "detail" => $detail, "invoices" => $invoices, "invoice_status" => $status, "currency" => $client_currency];
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 已用额度明细
	 * @description 接口说明:
	 * @param .name:page type:int require:0  other: desc:页码
	 * @param .name:limit type:int require:0  other: desc:页长
	 * @param .name:order type:mix require:0  other: desc:排序字段
	 * @param .name:sort type:string require:0  other: desc:排序desc/asc
	 * @param .name:payment type:string require:0  other: desc:按付款方式搜索
	 * @param .name:status type:string require:0  other: desc:按支付状态搜索
	 * @param .name:create_time type:string require:0  other: desc:按账单生成日搜索
	 * @param .name:due_time type:string require:0  other: desc:按账单逾期日搜索
	 * @param .name:paid_time type:string require:0  other: desc:按账单支付日搜索
	 * @param .name:subtotal_small type:string require:0  other: desc:按总计搜索(小值)
	 * @param .name:subtotal_big type:string require:0  other: desc:按总计搜索(大值)
	 * @return  data:账单列表@
	 * @data id:账单ID
	 * @data create_time:账单生成日
	 * @data due_time:账单逾期日
	 * @data paid_time:账单支付日
	 * @data subtotal:总计
	 * @data payment:付款方式
	 * @data status:状态(Paid:已支付,Unpaid:未支付,Draft:已草稿,Overdue:已逾期,Cancelled:被取消,Refunded:已退款,Collections:已收藏)
	 * @throws
	 * @author xj
	 * @url /credit_limit/used_detail
	 * @method get
	 */
	public function creditLimitUsed()
	{
		$params = $this->request->param();
		$page = input("get.page", 1, "intval");
		$limit = input("get.limit", 10, "intval");
		$order = input("get.order", "id");
		$sort = input("get.sort", "DESC");
		$page = $page >= 1 ? $page : config("page");
		$limit = $limit >= 1 ? $limit : config("limit");
		if (!in_array($order, ["id", "create_time", "due_time", "paid_time", "subtotal", "payment", "status"])) {
			$order = "id";
		}
		if (!in_array($sort, ["asc", "desc"])) {
			$sort = "asc";
		}
		$invoices1 = \think\Db::name("invoices")->field("id")->where("status", "Paid")->where("use_credit_limit", 1)->where("invoice_id", 0)->where("is_delete", 0)->where("uid", $uid)->select()->toArray();
		$invoices1 = array_column($invoices1, "id");
		$unpaid = \think\Db::name("invoices")->alias("a")->field("a.id")->leftjoin("invoices b", "b.id=a.invoice_id")->where("b.type", "credit_limit")->where("b.status", "Unpaid")->where("a.is_delete", 0)->where("b.is_delete", 0)->where("b.uid", $uid)->select()->toArray();
		$unpaid = array_column($unpaid, "id");
		$invoice_id = array_unique(array_merge($invoices1, $unpaid));
		$type_arr = ["renew" => "续费", "product" => "产品", "recharge" => "充值", "setup" => "初装费", "upgrade" => "产品升降级", "credit_limit" => "信用额"];
		$gateways = gateway_list();
		$invoices = \think\Db::name("invoices")->alias("i")->leftjoin("clients c", "c.id=i.uid")->leftJoin("currencies cu", "cu.id = c.currency")->leftJoin("invoice_items t", "t.invoice_id = i.id")->leftJoin("host h", "t.rel_id = h.id")->leftJoin("products p", "p.id = h.productid")->field("i.id,i.create_time,i.due_time,i.paid_time,i.subtotal,i.subtotal as sub,i.total,i.credit,i.payment,i.status,cu.prefix,cu.suffix,i.type,i.use_credit_limit,p.name")->whereIN("i.invoice_id", $invoice_id)->where("i.is_delete", 0)->withAttr("payment", function ($value, $data) use($gateways) {
			if ($data["status"] == "Paid") {
				if ($data["use_credit_limit"] == 1) {
					return "信用额";
				} else {
					if ($data["sub"] == $data["credit"]) {
						return "余额支付";
					} else {
						if ($data["sub"] > $data["credit"] && $data["credit"] > 0) {
							foreach ($gateways as $v) {
								if ($v["name"] == $value) {
									return "部分余额支付+" . $v["title"];
								}
							}
						} else {
							foreach ($gateways as $v) {
								if ($v["name"] == $value) {
									return $v["title"];
								}
							}
						}
					}
				}
			} else {
				foreach ($gateways as $v) {
					if ($v["name"] == $value) {
						return $v["title"];
					}
				}
			}
		})->withAttr("paid_time", function ($value, $data) {
			if ($data["status"] == "Unpaid" || $data["status"] == "Cancelled") {
				return "";
			} else {
				return $value;
			}
		})->withAttr("subtotal", function ($value, $data) {
			return $data["prefix"] . $value . $data["suffix"];
		})->withAttr("type", function ($value) use($type_arr) {
			return $type_arr[$value];
		})->order($order, $sort)->group("i.id")->select()->toArray();
		$status = config("invoice_payment_status");
		$gateway = gateway_list();
		foreach ($invoices as $k => &$v) {
			$detail = $this->getInvoicesDetail($v["id"]);
			$v["invoice_items"] = $detail["invoice_items"];
			$v["accounts"] = $detail["accounts"];
		}
		$client_currency = getUserCurrency($uid);
		$data = ["payee" => configuration("company_name"), "invoices" => $invoices, "invoice_status" => $status, "currency" => $client_currency];
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	public function get_search($param)
	{
		$where = [["i.delete_time", "=", 0], ["i.is_delete", "=", 0]];
		if (isset($param["hid"]) && $param["hid"] > 0) {
			$where[] = ["h.id", "=", $param["hid"]];
		}
		if (!empty($param["payment"])) {
			$where[] = ["i.payment", "=", $param["payment"]];
		}
		if (!empty($param["status"])) {
			$where[] = ["i.status", "=", $param["status"]];
		}
		if (!empty($params["hostid"])) {
			$where[] = ["t.rel_id", "=", $param["hostid"]];
		}
		if (isset($param["create_time"]) && $param["create_time"] > 0) {
			$where[] = ["i.create_time", ">=", $param["create_time"]];
			$where[] = ["i.create_time", "<", $param["create_time"] + 86400];
		}
		if (isset($param["due_time"])) {
			$due_time = strtotime(date("Y-m-d", $param["due_time"]));
			$where[] = ["i.due_time", ">=", $due_time];
			$where[] = ["i.due_time", "<", $due_time + 86400];
		}
		if (isset($param["paid_time"])) {
			$paid_time = strtotime(date("Y-m-d", $param["paid_time"]));
			$where[] = ["i.paid_time", ">=", $paid_time];
			$where[] = ["i.paid_time", "<", $paid_time + 86400];
		}
		if (!empty($param["subtotal_small"]) && $param["subtotal_small"] > 0) {
			$where[] = ["i.subtotal", ">=", $param["subtotal_small"]];
		}
		if (!empty($param["subtotal_big"]) && $param["subtotal_small"] > 0) {
			$where[] = ["i.subtotal", "<=", $param["subtotal_big"]];
		}
		return $where;
	}
	public function getInvoicesDetail($id)
	{
		$items = config("invoice_type");
		$uid = request()->uid;
		$invoice_payment_status = config("invoice_payment_status");
		$gateways = gateway_list();
		$invoice_items = \think\Db::name("invoice_items")->field("type,description,description2,amount,type as type_zh,rel_id")->where("invoice_id", $id)->where("uid", $uid)->withAttr("type_zh", function ($value, $data) use($items) {
			return $items[$data["type"]] ?: "";
		})->order("rel_id", "desc")->select()->toArray();
		$accounts = \think\Db::name("accounts")->alias("a")->field("a.id,a.pay_time,a.gateway,a.trans_id,a.amount_in,a.amount_out,a.fees")->withAttr("gateway", function ($value) use($gateways) {
			foreach ($gateways as $v) {
				if ($value == $v["name"]) {
					return $v["title"];
				}
			}
			return $value;
		})->where("a.invoice_id", $id)->where("a.delete_time", 0)->select()->toArray();
		$credit_log_uses = \think\Db::name("credit")->field("id,create_time as pay_time,amount as amount_in,description as gateway")->withAttr("gateway", function ($value) use($id) {
			if ($value == "Credit Applied to Invoice #{$id}") {
				return lang("CREDITLIMIT_CREDIT_APPLIED");
			}
			if ($value == "Credit Removed from Invoice #{$id}") {
				return lang("CREDITLIMIT_CREDIT_REMOVED");
			}
			if ($value == "Credit Applied to Renew Invoice #{$id}") {
				return lang("CREDITLIMIT_CREDIT_APPLIED_RENEW");
			}
		})->where("description", "like", "%Credit Applied to Invoice #{$id}")->whereOr("description", "like", "%Credit Removed from Invoice #{$id}")->whereOr("description", "like", "%Credit Applied to Renew Invoice #{$id}")->select()->toArray();
		foreach ($credit_log_uses as $k => $v) {
			$v["id"] = "";
			$v["trans_id"] = "";
			$v["fees"] = "";
			array_push($accounts, $v);
		}
		foreach ($invoice_items as &$invoice_item) {
			$host = \think\Db::name("host")->alias("a")->field("a.id,b.groupid,a.productid")->leftJoin("products b", "a.productid = b.id")->where("a.id", $invoice_item["rel_id"])->find();
			$invoice_item["groupid"] = $host["groupid"] ?: 1;
			$invoice_item["hid"] = $host["id"] ?: 0;
			if ($invoice_item["type"] == "host") {
				$customfields = \think\Db::name("customfields")->where([["relid", "=", $host["productid"], ["type", "=", "product"]]])->select()->toArray();
				$tmp_arr = [];
				if (!empty($customfields[0])) {
					foreach ($customfields as $v) {
						if ($v["showorder"]) {
							$customfieldsvalues = \think\Db::name("customfieldsvalues")->where([["fieldid", "=", $v["id"]], ["relid", "=", $host["id"]]])->value("value");
							$tmp_arr[] = "{$v["fieldname"]}={$customfieldsvalues}";
						}
					}
				}
				if (!empty($invoice_item["description2"])) {
					$invoice_item["description"] = $invoice_item["description2"] . "\n" . implode("\n", $tmp_arr);
				} else {
					$invoice_item["description"] .= "\n" . implode("\n", $tmp_arr);
				}
			}
		}
		$data = ["detail" => $detail, "invoice_items" => $invoice_items, "accounts" => $accounts ?? []];
		return $data;
	}
	/**
	 * @title 提前还款
	 * @description 接口说明:提前还款
	 * @author xj
	 * @url /credit_limit/prepayment
	 * @method POST
	 */
	public function creditLimitPrepayment()
	{
		$params = $this->request->param();
		$uid = request()->uid;
		$uid = \intval($uid);
		$unpaid = \think\Db::name("invoices")->where("type", "credit_limit")->where("status", "Unpaid")->where("is_delete", 0)->where("uid", $uid)->sum("total");
		if ($unpaid > 0) {
			return jsonrule(["status" => 400, "msg" => "当前有未还款信用额账单，不可提前还款"]);
		}
		$amount = \think\Db::name("invoices")->where("status", "Paid")->where("use_credit_limit", 1)->where("invoice_id", 0)->where("is_delete", 0)->where("uid", $uid)->sum("total");
		$amount = floatval($amount);
		if ($amount <= 0 || $amount > 1000000) {
			return jsonrule(["status" => 400, "msg" => "当前没有欠款，不需要提前还款"]);
		}
		$invoices = \think\Db::name("invoices")->where("status", "Paid")->where("use_credit_limit", 1)->where("invoice_id", 0)->where("is_delete", 0)->where("uid", $uid)->column("id");
		$clients = \think\Db::name("clients")->field("defaultgateway,bill_repayment_period")->where("id", $uid)->find();
		$data = ["uid" => $uid, "create_time" => time(), "due_time" => time() + $clients["bill_repayment_period"] * 24 * 3600, "subtotal" => $amount, "total" => $amount, "status" => "Unpaid", "payment" => $clients["defaultgateway"], "type" => "credit_limit", "notes" => "信用额提前还款", "url" => "credit", "credit_limit_prepayment" => 1, "credit_limit_prepayment_invoices" => json_encode($invoices)];
		$data2 = ["uid" => $uid, "type" => "credit_limit", "description" => "信用额提前还款", "amount" => $amount, "due_time" => strtotime("+365 day")];
		$data3 = ["pid" => $id, "uid" => $uid, "amount" => $amount, "remarks" => $params["remarks"], "create_time" => time()];
		\think\Db::startTrans();
		try {
			$invoice_id = \think\Db::name("invoices")->insertGetId($data);
			$data2["invoice_id"] = $invoice_id;
			\think\Db::name("invoice_items")->insertGetId($data2);
			\think\Db::name("invoices")->where("status", "Paid")->where("use_credit_limit", 1)->where("invoice_id", 0)->where("is_delete", 0)->where("uid", $uid)->update(["invoice_id" => $invoice_id]);
			\think\Db::commit();
		} catch (\Exception $e) {
			\think\Db::rollback();
			return jsonrule(["status" => 400, "msg" => lang("ADD FAIL")]);
		}
		return jsonrule(["status" => 200, "msg" => lang("ADD SUCCESS"), "invoiceid" => $invoice_id]);
	}
}