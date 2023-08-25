<?php

namespace app\admin\controller;

/**
 * @title 后台账单管理
 * @group 后台账单管理
 */
class InvoiceController extends GetUserController
{
	/**
	 * @title 搜索页面invoice/paid
	 * @description 接口说明:
	 * @author wyh
	 * @url admin/invoice/search_page
	 * @method get
	 * @param .name:uid type:int require:0 default:1 other: desc:可选参数,用户ID
	 */
	public function searchPage()
	{
		$users = [];
		$list = \think\Db::name("user")->field("id as value,user_nickname as label")->where("is_sale", 1)->select()->toArray();
		$salelist = $list;
		$type_arr = config("invoice_type_all");
		$arr = [];
		foreach ($type_arr as $k => $v) {
			$arr[$k] = $v;
		}
		$other_pay = [["id" => 0, "name" => "creditPay", "title" => "余额支付", "status" => 1], ["id" => -1, "name" => "creditLimitPay", "title" => "信用额支付", "status" => 1]];
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "gateway" => array_merge(gateway_list1(), $other_pay), "invoice_payment_status" => config("invoice_payment_status"), "user" => $users, "type_arr" => $arr, "salelist" => $salelist]);
	}
	/**
	 * @title 账单列表
	 * @description 接口说明:
	 * @param .name:page type:int require:0  other: desc:页码
	 * @param .name:limit type:int require:0  other: desc:长度
	 * @param .name:order type:string require:0  other: desc:排序字段
	 * @param .name:sort type:string require:0  other: desc:排序规则(asc/desc)
	 * @param .name:uid type:string require:0  other: desc:客户名
	 * @param .name:description type:string require:0  other: desc:描述
	 * @param .name:payment type:string require:0  other: desc:支付方式
	 * @param .name:status type:int require:0  other: desc:状态
	 * @param .name:total_small type:int require:0  other: desc:总计小值
	 * @param .name:total_big type:int require:0  other: desc:总计大值
	 * @param .name:create_time type:int require:0  other: desc:账单生成日
	 * @param .name:due_time type:int require:0  other: desc:账单逾期日
	 * @param .name:paid_time type:int require:0  other: desc:账单支付日
	 * @param .name:total type:float require:0  other: desc:金额
	 * @param .name:last_capture_attempt type:int require:0  other: desc:last_capture_attempt
	 * @param .name:sale_id type:int require:0 default:1 other: desc:销售ID
	 * @return .username:用户名
	 * @return .invoice_num:账单#
	 * @return .create_time:
	 * @return .due_time:到期时间
	 * @return .subtotal:总计
	 * @return .payment:付款方式
	 * @return .status:状态
	 * @return .credit:使用余额
	 * @return email:邮件信息@!
	 * @email id:邮件模板id name:模板名
	 * @throws
	 * @author wyh
	 * @url admin/invoice/index
	 * @method get
	 */
	public function index()
	{
		session_write_close();
		$page = input("page") ?? config("page");
		$limit = input("limit") ?? config("limit");
		$order = input("order");
		$sort = input("sort") ?? "desc";
		$params = $this->request->param();
		$data = ["page" => $page, "size" => $limit, "order" => $order, "sort" => $sort];
		$validate = new \app\admin\validate\InvoiceValidate();
		if (!$validate->check($data)) {
			return jsonrule($validate->getError(), 400);
		}
		if ($this->user["id"] != 1 && $this->user["is_sale"]) {
			$where[] = ["i.uid", "in", $this->str];
		}
		$fun = function (\think\db\Query $query) use($params) {
			$query->where("i.delete_time", 0)->where("i.is_delete", 0);
			if (isset($params["total_small"])) {
				$total_small = floatval($params["total_small"]);
				if (isset($params["total_big"])) {
					$total_big = floatval($params["total_big"]);
					$query->whereBetween("i.subtotal", [$total_small, $total_big]);
				} else {
					$query->where("i.subtotal", ">=", $total_small);
				}
			} elseif (isset($params["total_big"])) {
				$total_big = floatval($params["total_big"]);
				$query->whereBetween("i.subtotal", [0, $total_big]);
			}
			if (isset($params["total"])) {
				$username = $params["total"];
				$query->where("i.subtotal", "like", "%{$username}%");
			}
			if (isset($params["uid"]) && !empty($params["uid"])) {
				$username = $params["uid"];
				$query->where("c.id", $username);
			}
			if (isset($params["payment"])) {
				$payment = $params["payment"];
				if (!empty($payment)) {
					if ($payment == "creditLimitPay") {
						$query->where("i.use_credit_limit", 1);
					} elseif ($payment == "creditPay") {
						$query->where("i.credit", ">", 0);
					} else {
						$query->where("i.payment", $payment)->where("i.credit", 0)->where("i.use_credit_limit", 0);
					}
				}
			}
			if (isset($params["status"]) && $params["status"] != "All") {
				$status = $params["status"];
				if (!empty($status)) {
					if ($status == "Overdue") {
						$query->where("i.due_time", "<=", strtotime(date("Y-m-d")));
					} else {
						$query->where("i.status", $status);
					}
				}
			}
			if (isset($params["create_time"])) {
				$create_time = is_string($params["create_time"]) ? strtotime($params["create_time"]) : $params["create_time"];
				$query->where("i.create_time", ">=", $create_time[0])->where("i.create_time", "<=", $create_time[1]);
			}
			if (isset($params["due_time"])) {
				$due_time = is_string($params["due_time"]) ? strtotime($params["due_time"]) : $params["due_time"];
				$query->where("i.due_time", ">=", $due_time[0])->where("i.due_time", "<=", $due_time[1]);
			}
			if (isset($params["paid_time"])) {
				$paid_time = is_string($params["paid_time"]) ? strtotime($params["paid_time"]) : $params["paid_time"];
				$query->where("i.paid_time", ">=", $paid_time[0])->where("i.paid_time", "<=", $paid_time[1]);
			}
			if (isset($params["type"]) && $params["type"]) {
				$type = $params["type"];
				$query->where("i.type", $type);
			}
			if (isset($params["sale_id"]) && $params["sale_id"]) {
				$type = $params["sale_id"];
				$query->where("c.sale_id", $type);
			}
			if (!empty($params["lineitem_desc"])) {
				$invoice_id = \think\Db::name("invoice_items")->whereLike("description", "%" . $params["lineitem_desc"] . "%")->column("invoice_id");
				if (empty($invoice_id)) {
					$invoice_id = [0];
				}
				$query->where("i.id", "in", $invoice_id);
			}
			if (isset($params["invoice_id"]) && $params["invoice_id"] !== "") {
				$query->where("i.id", "like", "%" . $params["invoice_id"] . "%");
			}
		};
		$rows = $this->getInvoice($fun, $where, $page, $limit, $order, $sort);
		$sums1 = 0;
		foreach ($rows as $key => $value) {
			$sums1 = bcadd($value["sub"], $sums1, 2);
		}
		$sums2 = $this->getInvoiceTotalprice($fun, $where);
		$pages = ["total" => $this->getInvoiceCount($fun, $where)];
		session_write_close();
		return jsonrule(["data" => $rows, "page" => $pages, "status" => 200, "price" => $sums1, "totalprice" => $sums2, "msg" => lang("SUCCESS MESSAGE")]);
	}
	private function getInvoiceTotalprice($fun, $where)
	{
		$rows = \think\Db::name("invoices")->alias("i")->field("i.subtotal")->leftjoin("clients c", "c.id=i.uid")->leftJoin("currencies cu", "cu.id = c.currency")->where("i.type", "neq", "upgrade")->group("i.id")->where($fun)->where($where)->select()->toArray();
		$sums2 = 0;
		foreach ($rows as $key => $value) {
			$sums2 = bcadd($value["subtotal"], $sums2, 2);
		}
		return $sums2;
	}
	private function getInvoice($fun, $where, $page, $limit, $order, $sort)
	{
		$type_arr = config("invoice_type_all");
		$gateways = gateway_list("gateways", 0);
		$invoice_payment_status = config("invoice_payment_status");
		$sale = array_column(get_sale(), "user_nickname", "id");
		$rows = \think\Db::name("invoices")->alias("i")->field("c.companyname,c.username,c.sale_id,i.id,i.uid,i.invoice_num,i.paid_time,
            i.create_time,i.due_time,i.subtotal,i.subtotal as sub,i.total,cu.prefix,cu.suffix,
            i.status,i.payment,i.credit,i.type,i.use_credit_limit")->leftjoin("clients c", "c.id=i.uid")->leftJoin("currencies cu", "cu.id = c.currency")->group("i.id")->withAttr("subtotal", function ($value, $data) {
			return $data["prefix"] . $value . $data["suffix"];
		})->withAttr("payment", function ($value, $data) use($gateways) {
			if ($data["status"] == "Paid") {
				if ($data["use_credit_limit"] == 1) {
					return "信用额支付";
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
		})->withAttr("status", function ($value) use($invoice_payment_status) {
			return $invoice_payment_status[$value] ?? ["name" => "未知！", "color" => "#f56c6c"];
		})->withAttr("type", function ($value) use($type_arr) {
			return $type_arr[$value];
		})->withAttr("sale_id", function ($value) use($sale) {
			return $sale[$value];
		})->where($where)->where($fun)->page($page)->limit($limit)->order($order ?? "i.id", $sort)->select()->toArray();
		return $rows;
	}
	private function getInvoiceCount($fun, $where)
	{
		$count = \think\Db::name("invoices")->alias("i")->leftjoin("clients c", "c.id=i.uid")->leftJoin("currencies cu", "cu.id = c.currency")->group("i.id")->where($fun)->where($where)->count();
		return $count;
	}
	/**
	 * @title 标记为已支付
	 * @description 接口说明:标记为已支付
	 * @author wyh
	 * @url admin/invoice/paid
	 * @method get
	 * @param .name:ids type:int require:0 default:1 other: desc:账单ID：ids或者ids[]
	 */
	public function paid()
	{
		$params = $this->request->param();
		$ids = $params["ids"];
		if (!is_array($ids)) {
			$ids = [$ids];
		}
		if (empty($ids[0])) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$error_msg_id = [];
		$success_total = 0;
		foreach ($ids as $id) {
			$invoice = \think\Db::name("invoices")->field("uid,status,payment,type,subtotal,total,credit,notes")->where("id", $id)->find();
			$invoice_status = $invoice["status"];
			$uid = $invoice["uid"] ?? 0;
			$client = \think\Db::name("clients")->where("id", $uid)->find();
			$client_cerdit = $client["credit"] ?? "0";
			$currency = $client["currency"] ?? 1;
			$client_surplus = getSurplus($id);
			$account_data = ["uid" => $uid, "currency" => \think\Db::name("currencies")->where("id", $currency)->value("code"), "gateway" => $invoice["payment"], "create_time" => time(), "pay_time" => time(), "description" => "人工入账", "amount_in" => $client_surplus, "fees" => 0, "amount_out" => 0, "invoice_id" => $id];
			$time = time();
			$flag = true;
			\think\Db::startTrans();
			try {
				$acc = \think\Db::name("accounts")->insertGetId($account_data);
				$virtual_credit = $invoice["subtotal"] - $invoice["total"] - $invoice["credit"];
				$invoice_data = ["paid_time" => $time, "status" => "Paid", "update_time" => $time];
				\think\Db::name("invoices")->where("id", $id)->where("delete_time", 0)->update($invoice_data);
				if ($virtual_credit > 0) {
					$virtual = \think\Db::name("clients")->where("id", $invoice["uid"])->where("credit", ">=", $virtual_credit)->setDec("credit", $virtual_credit);
					if (empty($virtual)) {
						\think\Db::name("accounts")->where("id", $acc)->update(["amount_in" => $invoice["subtotal"]]);
						\think\Db::name("invoices")->where("id", $id)->update(["total" => $invoice["subtotal"]]);
					} else {
						credit_log(["uid" => $invoice["uid"], "desc" => "Credit Applied to Invoice #" . $id, "amount" => $virtual_credit, "relid" => $id, "notes" => $invoice["notes"]]);
					}
				}
				active_log_final(sprintf($this->lang["Invoice_admin_addPay"], $invoice["uid"], $id), $invoice["uid"], 6, $id);
				\think\Db::commit();
			} catch (\Exception $e) {
				\think\Db::rollback();
				$error_msg_id[] = $id;
				$flag = false;
			}
			if ($flag) {
				$invoice_logic = new \app\common\logic\Invoices();
				$invoice_logic->is_admin = true;
				$invoice_logic->processPaidInvoice($id);
				active_log_final(sprintf($this->lang["Invoice_admin_paid"], $uid, $id, $acc), $uid, 6, $id);
			}
		}
		return jsonrule(["status" => 200, "msg" => lang("标记成功")]);
	}
	/**
	 * @title 标记为未支付
	 * @description 接口说明: 标记为未支付
	 * @author wyh
	 * @url admin/invoice/unpaid
	 * @method get
	 * @param .name:ids type:int require:0 default:1 other: desc:订单ID：ids或者ids[]
	 */
	public function unpaid()
	{
		$params = $this->request->param();
		$ids = $params["ids"];
		if (!is_array($ids)) {
			$ids = [$ids];
		}
		if (empty($ids[0])) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		foreach ($ids as $id) {
			$invoice = \think\Db::name("invoices")->field("uid,status")->where("id", $id)->find();
			$invoice_status = $invoice["status"];
			$uid = $invoice["uid"] ?? 0;
			active_log_final(sprintf($this->lang["Invoice_admin_unpaid"], $uid, $id), $uid, 6, $id);
		}
		\think\Db::name("invoices")->whereIn("id", $ids)->where("delete_time", 0)->update(["status" => "Unpaid"]);
		foreach ($ids as $v) {
			hook("invoice_mark_unpaid", ["invoiceid" => $v]);
		}
		return jsonrule(["status" => 200, "msg" => lang("UPDATE SUCCESS")]);
	}
	/**
	 * @title 标记为被取消
	 * @description 接口说明:  标记为被取消
	 * @author wyh
	 * @url admin/invoice/cancelled
	 * @method get
	 * @param .name:ids type:int require:0 default:1 other: desc:订单ID：ids或者ids[]
	 */
	public function cancelled()
	{
		$params = $this->request->param();
		$ids = $params["ids"];
		if (!is_array($ids)) {
			$ids = [$ids];
		}
		if (empty($ids[0])) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		foreach ($ids as $id) {
			$invoice = \think\Db::name("invoices")->field("uid,status")->where("id", $id)->find();
			$invoice_status = $invoice["status"];
			$uid = $invoice["uid"];
			active_log_final(sprintf($this->lang["Invoice_admin_cancelled"], $uid, $id), $uid, 6, $id);
		}
		\think\Db::name("invoices")->whereIn("id", $ids)->where("delete_time", 0)->update(["status" => "Cancelled"]);
		foreach ($ids as $v) {
			hook("invoice_mark_cancelled", ["invoiceid" => $v]);
		}
		return jsonrule(["status" => 200, "msg" => lang("UPDATE SUCCESS")]);
	}
	/**
	 * @title 删除(可批量)
	 * @description 接口说明:  删除(可批量)
	 * @author wyh
	 * @url admin/invoice/delete
	 * @method delete
	 * @param .name:ids type:int require:0 default:1 other: desc:订单ID：ids或者ids[]
	 */
	public function delete()
	{
		$params = $this->request->param();
		$ids = $params["ids"];
		if (!is_array($ids)) {
			$ids = [$ids];
		}
		foreach ($ids as $id) {
			$invoice = \think\Db::name("invoices")->field("uid,status")->where("id", $id)->find();
			$invoice_status = $invoice["status"];
			$uid = $invoice["uid"];
			active_log_final(sprintf($this->lang["Invoice_admin_delete"], $uid, $id), $uid, 6, $id);
		}
		$res = db("invoices")->whereIn("id", $ids)->delete();
		if ($res) {
			foreach ($ids as $v) {
				hook("invoice_delete", ["invoiceid" => $v]);
			}
			return jsonrule(["status" => 200, "msg" => lang("DELETE SUCCESS")]);
		} else {
			return jsonrule(["status" => 400, "msg" => lang("DELETE FAIL")]);
		}
	}
	/**
	 * @title 复制账单
	 * @description 接口说明: 复制账单
	 * @author wyh
	 * @url admin/invoice/duplicate
	 * @method get
	 * @param .name:ids type:int require:0 default:1 other: desc:订单ID：ids或者ids[]
	 */
	public function duplicate()
	{
		$params = $this->request->param();
		$ids = $params["ids"];
		if (!is_array($ids)) {
			$ids = [$ids];
		}
		\think\Db::startTrans();
		try {
			$invoices = \think\Db::name("invoices")->whereIn("id", $ids)->where("delete_time", 0)->select()->toArray();
			foreach ($invoices as $invoice) {
				$invoice["create_time"] = time();
				$old_id = $invoice["id"];
				unset($invoice["id"]);
				$new_id = \think\Db::name("invoices")->insertGetId($invoice);
				$invoice_items = \think\Db::name("invoice_items")->where("invoice_id", $old_id)->where("delete_time", 0)->select()->toArray();
				$invoice_items_new = [];
				foreach ($invoice_items as $invoice_item) {
					$invoice_item["invoice_id"] = $new_id;
					unset($invoice_item["id"]);
					$invoice_items_new[] = $invoice_item;
				}
				active_log_final(sprintf($this->lang["Invoice_admin_duplicate"], $invoice["uid"], $invoice["id"]), $invoice["uid"], 6, $invoice["id"]);
				\think\Db::name("invoice_items")->insertAll($invoice_items_new);
			}
			\think\Db::commit();
		} catch (\Exception $e) {
			\think\Db::rollback();
			return jsonrule(["status" => 400, "msg" => lang("DUPLICATE FAIL")]);
		}
		return jsonrule(["status" => 200, "msg" => lang("DUPLICATE SUCCESS")]);
	}
	/**
	 * @title 账单摘要
	 * @description 接口说明:账单摘要
	 * @author wyh
	 * @url admin/invoice/summary/:id
	 * @method GET
	 * @param .name:id type:int require:1 default:1 other: desc:账单ID
	 * @return  invoices:账单详情@
	 * @invoices  id:账单ID
	 * @invoices  username:用户名
	 * @invoices  create_time:账单生成日
	 * @invoices  due_time:账单逾期日
	 * @invoices  subtotal:总计
	 * @invoices  surplus:结余
	 * @invoices  last_capture_attempt:Last Capture Attempt,0代表无
	 * @invoices  status:支付状态
	 * @invoices  payment:付款方式
	 * @invoices  credit:余额
	 * @return  invoice_items:账单项目@
	 * @invoice_items  id:项目ID
	 * @invoice_items  description:描述
	 * @invoice_items  amount:金额
	 * @invoice_items  taxed:0默认不打勾，1打勾
	 * @return  email_templates:邮件模板id,name
	 * @return  accounts:交易明细@
	 * @accounts  pay_time:时间
	 * @accounts  gateway:付款方式
	 * @accounts  trans_id:付款流水ID
	 * @accounts  amount_in：金额
	 * @accounts  fees:付款手续费
	 */
	public function summary()
	{
		$params = $this->request->param();
		$id = isset($params["id"]) ? intval($params["id"]) : "";
		if (!$id) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$gateways = gateway_list("gateways", 0);
		$invoices = \think\Db::name("invoices")->alias("a")->field("b.credit as user_credit,b.is_open_credit_limit,b.credit_limit_balance,a.notes,a.uid")->field("a.subtotal as sub,a.use_credit_limit,a.id,a.uid,a.type,b.username,a.create_time,a.due_time,a.paid_time,a.subtotal,a.total,a.status,a.credit,a.payment,a.last_capture_attempt")->leftJoin("clients b ", "a.uid = b.id")->withAttr("payment", function ($value, $data) use($gateways, $id) {
			$count = \think\Db::name("accounts")->where("invoice_id", $id)->where("delete_time", 0)->count();
			if ($data["use_credit_limit"] == 1 && $this->getInterfacePay($id, $data["uid"]) == 0 && $data["credit"] == 0) {
				return "信用额支付";
			}
			$gateway = \think\Db::name("accounts")->where("invoice_id", $id)->where("refund", 0)->order("id", "desc")->value("gateway");
			if ($data["credit"] > 0 && $count > 0) {
				if (!empty($gateway)) {
					foreach ($gateways as $v) {
						if ($v["name"] == $gateway) {
							return "部分余额支付+" . $v["title"];
						}
					}
				} else {
					foreach ($gateways as $v) {
						if (!empty($gateway)) {
							if ($v["name"] == $gateway) {
								return "部分余额支付+" . $v["title"];
							}
						} else {
							if ($v["name"] == $value) {
								return "部分余额支付+" . $v["title"];
							}
						}
					}
				}
			}
			if ($data["credit"] > 0) {
				return "余额支付";
			}
			if ($count > 0) {
				foreach ($gateways as $v) {
					if (!empty($gateway)) {
						if ($v["name"] == $gateway) {
							return $v["title"];
						}
					} else {
						if ($v["name"] == $value) {
							return $v["title"];
						}
					}
				}
			}
			foreach ($gateways as $v) {
				if ($v["name"] == $value) {
					return $v["title"];
				}
			}
		})->where("a.id", $id)->where("a.delete_time", 0)->find();
		if (empty($invoices)) {
			return jsonrule(["status" => 400, "msg" => lang("账单不存在")]);
		}
		$uid = $invoices["uid"];
		$virtual_credit = $invoices["subtotal"] - $invoices["total"] - $invoices["credit"];
		$credit_limit_is_pay = 1;
		if ($invoices["credit"] > 0 || $this->getInterfacePay($id, $uid)) {
			$credit_limit_is_pay = 0;
			$invoices["use_credit_limit"] = 0;
		}
		if ($invoices["status"] != "Paid" || $invoices["status"] == "Refunded") {
			$credit_limit = $this->getUseCreditLimit($invoices["uid"]);
			$exists_pay = ["credit" => ["is_pay" => $invoices["use_credit_limit"] == 1 ? 0 : $invoices["user_credit"] > 0 ? 1 : 0, "money" => $invoices["user_credit"] > 0 ? $invoices["user_credit"] : 0], "credit_limit" => ["is_pay" => $invoices["is_open_credit_limit"] && $invoices["sub"] <= $credit_limit && $virtual_credit == 0 ? $credit_limit_is_pay : 0, "money" => $credit_limit ?: 0]];
		}
		$currencyId = priorityCurrency($uid);
		$currency = \think\Db::name("currencies")->field("id,code,prefix,suffix")->where("id", $currencyId)->find();
		$amount = \think\Db::name("invoice_items")->where("invoice_id", $id)->where("delete_time", 0)->sum("amount");
		$invoices["notes"] = htmlspecialchars_decode($invoices["notes"]);
		$invoices["subtotal"] = $currency["prefix"] . bcsub($amount, 0, 2) . $currency["suffix"];
		$invoice_logic = new \app\common\logic\Invoices();
		$paid = $invoice_logic->refundAndApply($id);
		$surplus = bcsub($amount, $paid);
		$invoices["pay_amount"] = $this->getInterfacePay($id, $uid);
		$invoices["pay_amount"] = $currency["prefix"] . bcsub($invoices["pay_amount"], 0, 2) . $currency["suffix"];
		$invoices["surplus"] = $surplus;
		$invoices["credit_zh"] = $currency["prefix"] . bcsub($invoices["credit"], 0, 2) . $currency["suffix"];
		$invoices["surplus_zh"] = $currency["prefix"] . bcsub($surplus, 0, 2) . $currency["suffix"];
		$invoice_logic = new \app\common\logic\Invoices();
		if ($invoices["use_credit_limit"] == 0) {
			$total = $invoice_logic->refundAndApply($id);
			if ($total == $invoices["subtotal"]) {
				$invoices["status"] = "Paid";
			}
		}
		$invoices["status_zh"] = config("invoice_payment_status")[$invoices["status"]];
		$invoice_items = \think\Db::name("invoice_items")->field("id,description,amount,taxed,rel_id,type")->where("invoice_id", $id)->where("delete_time", 0)->select()->toArray();
		$billing_cycle = config("coupon_cycle");
		foreach ($invoice_items as &$m) {
			$m = array_map(function ($v) {
				return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
			}, $m);
			if ($m["type"] == "host") {
				$host_data = \think\Db::name("host")->field("domain,dedicatedip,billingcycle")->where("id", $m["rel_id"])->find();
				$new_des = explode("\n", $m["description"]);
				if (count(explode("(", $new_des[0])) <= 2) {
					$new_des[0] = explode("(", $new_des[0])[0] . "(" . $host_data["domain"] . "),IP({$host_data["dedicatedip"]}),购买时长：" . $billing_cycle[$host_data["billingcycle"]] . "(" . explode("(", $new_des[0])[1];
					$new_des = implode("\n", $new_des);
					$m["description"] = $new_des;
				}
			}
		}
		$gateways = gateway_list("gateways", 0);
		$accounts = \think\Db::name("accounts")->alias("a")->field("a.id,a.pay_time,a.gateway,a.trans_id,a.amount_in,a.amount_in as _amount_in,a.amount_out,a.fees,c.prefix,c.suffix,a.invoice_id")->leftJoin("currencies c", "a.currency = c.code")->withAttr("amount_in", function ($value, $data) use($currency) {
			if ($value > 0) {
				if (!$data["prefix"]) {
					return $currency["prefix"] . $value . $currency["suffix"];
				}
				return $data["prefix"] . $value . $data["suffix"];
			} else {
				if (!$data["prefix"]) {
					return $currency["prefix"] . -$data["amount_out"] . $currency["suffix"];
				}
				return $data["prefix"] . -$data["amount_out"] . $data["suffix"];
			}
		})->withAttr("gateway", function ($value) use($gateways) {
			foreach ($gateways as $v) {
				if ($value == $v["name"]) {
					return $v["title"];
				}
			}
			return $value;
		})->withAttr("fees", function ($value, $data) use($currency) {
			if (!$data["prefix"]) {
				return $currency["prefix"] . $value . $currency["suffix"];
			}
			return $data["prefix"] . $value . $data["suffix"];
		})->where("a.invoice_id", $id)->where("a.delete_time", 0)->select()->toArray();
		$refund_accounts = \think\Db::name("accounts")->field("id,currency,pay_time as create_time,trans_id,amount_in")->where("invoice_id", $id)->where("delete_time", 0)->where("refund", 0)->where("gateway", "<>", "")->column("id");
		$credit_log_uses = \think\Db::name("credit")->field("id,create_time as pay_time,amount,amount as amount_in,description as gateway,relid")->withAttr("amount_in", function ($value) use($currency) {
			return $currency["prefix"] . $value . $currency["suffix"];
		})->withAttr("gateway", function ($value) use($id) {
			if ($value == "Credit Applied to Invoice #{$id}") {
				return lang("已使用余额");
			}
			if ($value == "Credit Removed from Invoice #{$id}") {
				return lang("已移除余额");
			}
			if ($value == "Credit Applied to Renew Invoice #{$id}") {
				return lang("余额支付续费账单");
			}
			return $value;
		})->where("description", "like", "%Credit Applied to Invoice #{$id}")->whereOr("description", "like", "%Credit Removed from Invoice #{$id}")->whereOr("description", "like", "%Credit Applied to Renew Invoice #{$id}")->whereOr(function (\think\db\Query $query) use($id) {
			$query->where("description", "Credit Applied")->where("relid", $id);
		})->select()->toArray();
		if ($accounts) {
			$accounts = array_column($accounts, null, "id");
			foreach ($accounts as $key => $val) {
				$accounts[$key]["type"] = "account";
				$amount_out = \think\Db::name("accounts")->where("refund", $val["id"])->where("delete_time", 0)->sum("amount_out");
				$amount_in = $val["_amount_in"];
				$diff = bcsub($amount_in, $amount_out, 2);
				$accounts[$key]["diff_amount"] = $diff;
			}
		}
		foreach ($credit_log_uses as $k => $v) {
			$v["id"] = "";
			$v["trans_id"] = "";
			$v["fees"] = "";
			$v["diff_amount"] = $currency["prefix"] . $v["amount"] . $currency["suffix"];
			$v["type"] = "credit";
			array_push($accounts, $v);
		}
		if ($invoices["use_credit_limit"] && $invoices["status"] == "Paid") {
			array_push($accounts, ["pay_time" => $invoices["paid_time"], "gateway" => "信用额支付", "trans_id" => "", "amount_in" => $invoices["subtotal"], "type" => "credit_limit"]);
		}
		foreach ($accounts as $key => $val) {
			$accounts[$key]["is_refund"] = 0;
			if ($val["type"] == "credit") {
				$credit = $invoice_logic->creditRefund($id);
				if ($credit > 0 && ($val["gateway"] == "已使用余额" || $val["gateway"] == "余额支付续费账单")) {
					$accounts[$key]["is_refund"] = 1;
				}
			} elseif ($val["type"] == "account") {
				$accounts[$key]["is_refund"] = $val["id"] && $val["diff_amount"] > 0 && in_array($val["id"], $refund_accounts) ? 1 : 0;
			}
			if ($invoices["type"] == "credit_limit") {
				$accounts[$key]["is_refund"] = 0;
			}
		}
		foreach ($invoice_items as &$invoice_item) {
			if ($invoice_item["type"] == "host") {
				$host = \think\Db::name("host")->find($invoice_item["rel_id"]);
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
				$invoice_item["description"] .= "\n" . implode("\n", $tmp_arr);
			}
		}
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "invoices" => $invoices, "invoice_items" => $invoice_items, "accounts" => array_values($accounts), "exists_pay" => $exists_pay ?? []]);
	}
	private function getInterfacePay($id, $uid)
	{
		$gateways = gateway_list();
		$accounts = \think\Db::name("accounts")->alias("a")->field("a.id,a.pay_time,a.gateway,a.trans_id,a.amount_in,a.amount_out,a.fees")->withAttr("gateway", function ($value) use($gateways) {
			foreach ($gateways as $v) {
				if ($value == $v["name"]) {
					return $v["title"];
				}
			}
			return $value;
		})->where("a.invoice_id", $id)->where("a.delete_time", 0)->select()->toArray();
		$money = 0;
		foreach ($accounts as $key => $val) {
			if (isset($val["amount_in"])) {
				$money += $val["amount_in"];
				$money -= $val["amount_out"];
			}
		}
		return $money;
	}
	/**
	 * @title 新增付款页面
	 * @author wyh
	 * @url admin/invoice/addpay_page/:id
	 * @method GET
	 * @param .name:id type:int require:0  other: desc:账单ID
	 * @return  id:账单ID
	 * @return  sruplus:结余
	 * @return  gateway:支付方式
	 */
	public function addPayPage()
	{
		$params = $this->request->param();
		$id = isset($params["id"]) ? intval($params["id"]) : "";
		if (!$id) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$surplus = getSurplus($id);
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "id" => $id, "surplus" => $surplus]);
	}
	/**
	 * @title 新增付款
	 * @author wyh
	 * @url admin/invoice/addpay
	 * @method POST
	 * @param .name:id type:int require:0  other: desc:账单id
	 * @param .name:pay_time type:int require:0  other: desc:支付时间
	 * @param .name:gateway type:string require:0  other: desc:支付方式
	 * @param .name:trans_id type:string require:0  other: desc:付款流水号
	 * @param .name:amount type:int require:0  other: desc:金额
	 * @param .name:fees type:int require:0  other: desc:费用
	 * @param .name:email type:int require:0  other: desc:发送确认邮件,1选中默认,0否
	 * @return
	 */
	public function addPay()
	{
		$params = \request()->only("id,pay_time,gateway,trans_id,amount,fees,email");
		$id = isset($params["id"]) ? intval($params["id"]) : "";
		if (!$id) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$validate = new \app\admin\validate\InvoiceValidate();
		if (!$validate->scene("pay")->check($params)) {
			return jsonrule(["msg" => $validate->getError(), "status" => 400]);
		}
		$time = time();
		$invoice = \think\Db::name("invoices")->field("uid,type,subtotal,total,credit,use_credit_limit,status")->where("id", $id)->where("delete_time", 0)->find();
		if (empty($invoice)) {
			return jsonrule(["status" => 400, "msg" => lang("账单不存在")]);
		}
		if ($invoice["use_credit_limit"] && ($invoice["status"] == "Paid" || $invoice["status"] == "Refunded")) {
			return jsonrule(["status" => 400, "msg" => "已使用信用额完成支付,无法手动入账"]);
		}
		$amount = $params["amount"] > 0 ? $params["amount"] : 0;
		if (getSurplus($id) < $amount) {
			return jsonrule(["status" => 400, "msg" => "您不可以使用比总账单的金额(结余)还多的金额"]);
		}
		$uid = $invoice["uid"];
		$currency = \think\Db::name("clients")->where("id", $uid)->value("currency");
		$code = \think\Db::name("currencies")->where("id", $currency)->value("code");
		$fees = $params["fees"] > 0 ? $params["fees"] : 0;
		$account_data = ["uid" => $uid, "currency" => $code ?? "", "gateway" => $params["gateway"], "create_time" => $time, "pay_time" => $params["pay_time"] ?? $time, "description" => "人工入账", "amount_in" => $amount, "fees" => $fees, "trans_id" => $params["trans_id"] ?? "", "invoice_id" => $id];
		$invoice_type = $invoice["type"];
		$subtotal = $invoice["subtotal"];
		$update = true;
		\think\Db::startTrans();
		try {
			\think\Db::name("accounts")->insert($account_data);
			$sum = \think\Db::name("accounts")->where("invoice_id", $id)->where("delete_time", 0)->sum("amount_in");
			$virtual_credit = bcsub($invoice["subtotal"], $invoice["total"] + $invoice["credit"], 2);
			$sum = $sum + $virtual_credit + $invoice["credit"];
			$invoice_data = ["paid_time" => $params["pay_time"] ?? $time, "status" => "Unpaid", "update_time" => time()];
			if ($invoice_type == "recharge") {
				if ($subtotal <= $sum) {
					$invoice_data = ["paid_time" => $params["pay_time"] ?? $time, "status" => "Paid", "update_time" => time()];
				}
				if ($invoice_data["status"] == "Unpaid") {
					credit_log(["uid" => $uid, "desc" => "Invoice #{$id} Recharge", "amount" => $amount, "relid" => $id]);
				}
			} else {
				if ($subtotal <= $sum) {
					$client_credit = bcsub($sum, $subtotal, 2);
					$invoice_data = ["paid_time" => $params["pay_time"] ?? $time, "status" => "Paid", "update_time" => $time];
					\think\Db::name("clients")->where("id", $uid)->setInc("credit", $client_credit);
					if ($client_credit > 0) {
						credit_log(["uid" => $uid, "desc" => "Invoice #{$id} Overpayment", "amount" => $client_credit, "relid" => $id]);
					}
					\think\Db::name("accounts")->where("invoice_id", $id)->where("refund_credit", 0)->where("delete_time", 0)->update(["refund_credit" => 1]);
				}
			}
			\think\Db::name("invoices")->where("id", $id)->where("delete_time", 0)->update($invoice_data);
			if ($virtual_credit > 0) {
				$virtual = \think\Db::name("clients")->where("id", $invoice["uid"])->where("credit", ">=", $virtual_credit)->setDec("credit", $virtual_credit);
				if (empty($virtual)) {
					active_log_final(sprintf($this->lang["Order_admin_clients_updatecredit_fail"], $invoice["uid"]), $invoice["uid"], 6, $id);
					throw new \Exception("余额不足");
				}
				credit_log(["uid" => $invoice["uid"], "desc" => "Credit Applied to Invoice #" . $id, "amount" => $virtual_credit, "relid" => $id]);
				\think\Db::name("invoices")->where("id", $id)->update(["credit" => $invoice["subtotal"] - $invoice["total"]]);
			}
			$time = date("Y-m-d", $account_data["pay_time"]);
			$payment = \think\Db::name("plugin")->where("name", $params["gateway"])->value("title");
			$is_email = $params["gateway"] ? "发送" : "不发送";
			$text = " 新增交易明细：金额‘{$amount}’,时间‘{$time}’,付款方式‘{$payment}’,付款流水号‘{$account_data["trans_id"]}’,发送邮件‘{$is_email}’";
			active_log_final(sprintf($this->lang["Invoice_admin_addPay"], $invoice["uid"], $id) . $text, $invoice["uid"], 6, $id);
			\think\Db::commit();
		} catch (\Exception $e) {
			$update = false;
			\think\Db::rollback();
			return jsonrule(["status" => 400, "msg" => lang("ADD FAIL") . ":" . $e->getMessage()]);
		}
		$is_email = false;
		if ($update && !empty($params["email"])) {
			$hostid = \think\Db::name("invoice_items")->alias("rel_id")->where("invoice_id", $id)->find();
			if ($hostid["rel_id"]) {
				$message_template_type = array_column(config("message_template_type"), "id", "name");
				$sms = new \app\common\logic\Sms();
				$client = check_type_is_use($message_template_type[strtolower("Invoice_Payment_Confirmation")], $invoice["uid"], $sms);
				if ($client) {
					$host = \think\Db::name("host")->alias("a")->field("a.id,a.orderid,o.amount")->join("orders o", "a.orderid=o.id")->where("a.id", $hostid["rel_id"])->find();
					$params = ["order_id" => $host["orderid"], "order_total_fee" => $host["amount"]];
					$sms->sendSms($message_template_type[strtolower("Invoice_Payment_Confirmation")], $client["phone_code"] . $client["phonenumber"], $params, false, $invoice["uid"]);
				}
			}
			$is_email = true;
		}
		if ($update) {
			$invoice_logic = new \app\common\logic\Invoices();
			$invoice_logic->is_admin = true;
			$invoice_logic->processPaidInvoice($id, $is_email);
			return jsonrule(["status" => 200, "msg" => lang("ADD SUCCESS")]);
		} else {
			return jsonrule(["status" => 400, "msg" => lang("ADD FAIL")]);
		}
	}
	/**
	 * @title 选项页面
	 * @author wyh
	 * @url admin/invoice/option_page/:id
	 * @method GET
	 * @param .name:id type:int require:0  other: desc:账单id
	 * @return
	 */
	public function optionPage()
	{
		$params = $this->request->param();
		$id = isset($params["id"]) ? intval($params["id"]) : "";
		if (!$id) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$invoice = \think\Db::name("invoices")->field("create_time,due_time,invoice_num,taxrate,taxrate2,status,payment,paid_time")->where("id", $id)->find();
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "id" => $id, "invoice" => $invoice, "pay_status" => config("invoice_payment_status")]);
	}
	/**
	 * @title 选项页面提交
	 * @author wyh
	 * @url admin/invoice/option
	 * @method POST
	 * @param .name:id type:int require:1  other: desc:账单id
	 * @param .name:create_time type:int require:0  other: desc:账单生成日
	 * @param .name:invoice_num type:int require:0  other: desc:账单 #
	 * @param .name:due_time type:int require:0  other: desc:账单逾期日
	 * @param .name:gateway type:int require:1  other: desc:付款方式
	 * @param .name:taxrate type:int require:0  other: desc:税费
	 * @param .name:taxrate2 type:int require:0  other: desc:税费
	 * @param .name:status type:int require:1  other: desc:状态
	 * @return
	 */
	public function option()
	{
		try {
			return \think\Db::transaction(function () {
				$params = request()->only(["id", "create_time", "invoice_num", "due_time", "gateway", "taxrate", "taxrate2", "status", "notes", "paid_time"]);
				$id = isset($params["id"]) ? intval($params["id"]) : "";
				if (!$id) {
					return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
				}
				$validate = new \app\admin\validate\InvoiceValidate();
				if (!$validate->scene("option")->check($params)) {
					return jsonrule(["msg" => $validate->getError(), "status" => 400]);
				}
				$invoce = \think\Db::name("invoices")->where("id", $id)->find();
				if (!empty($params["notes"]) && $params["notes"] != $invoce["notes"]) {
					active_log_final(sprintf($this->lang["Invoice_admin_notes"], $invoce["uid"], $id, $invoce["notes"], $params["notes"]), $invoce["uid"], 6, $id);
				}
				if ($params["status"] == "Paid" && empty($invoce["paid_time"])) {
					$paid_time = time();
				} else {
					if ($params["status"] == "Paid" && $params["paid_time"] > 0) {
						$paid_time = $params["paid_time"];
					} else {
						if ($params["status"] == "Paid" && !empty($invoce["paid_time"])) {
							$paid_time = $invoce["paid_time"];
						} else {
							$paid_time = 0;
						}
					}
				}
				$option_data = ["create_time" => $params["create_time"], "invoice_num" => $params["invoice_num"], "due_time" => $params["due_time"], "payment" => $params["gateway"], "taxrate" => $params["taxrate"], "taxrate2" => $params["taxrate2"], "status" => $params["status"], "notes" => $params["notes"], "paid_time" => $paid_time, "update_time" => time()];
				$hook_data = ["invoiceid" => $id, "content" => html_entity_decode($params["notes"], ENT_QUOTES)];
				hook("invoice_notes", $hook_data);
				\think\Db::name("invoices")->where("id", $id)->update($option_data);
				$text = " ";
				$ctime_old = date("Y-m-d", $invoce["create_time"]);
				$ctime_new = date("Y-m-d", $params["create_time"]);
				$dtime_old = date("Y-m-d", $invoce["due_time"]);
				$dtime_new = date("Y-m-d", $params["due_time"]);
				$status_old = lang(strtoupper($invoce["status"]));
				$status_new = lang(strtoupper($params["status"]));
				$payment_old = \think\Db::name("plugin")->where("name", $invoce["payment"])->value("title");
				$payment_new = \think\Db::name("plugin")->where("name", $params["gateway"])->value("title");
				if ($invoce["create_time"] != $params["create_time"]) {
					$text .= "账单生成日‘{$ctime_old}’变更为‘{$ctime_new}’，";
				}
				if ($invoce["due_time"] != $params["due_time"]) {
					$text .= "账单逾期日‘{$dtime_old}’变更为‘{$dtime_new}’，";
				}
				if ($invoce["status"] != $params["status"]) {
					$text .= "状态‘{$status_old}’变更为‘{$status_new}’，";
				}
				if ($invoce["payment"] != $params["gateway"]) {
					$text .= "支付方式‘{$payment_old}’变更为‘{$payment_new}’，";
				}
				active_log_final(sprintf($this->lang["Invoice_admin_option"], $invoce["uid"], $id) . $text, $invoce["uid"], 6, $id);
				return jsonrule(["status" => 200, "msg" => lang("UPDATE SUCCESS")]);
			});
		} catch (\Throwable $e) {
			return ["status" => 400, "msg" => $e->getMessage()];
		}
	}
	/**
	 * @title 余额页面
	 * @author wyh
	 * @url admin/invoice/add_pay_invoice_page/:id
	 * @method GET
	 * @param .name:id type:int require:1  other: desc:账单ID
	 * @return .surplus:结余(默认放在添加付款金额到账单的输入框中)
	 */
	public function addPayInvoicePage()
	{
		$params = $this->request->param();
		$id = isset($params["id"]) ? intval($params["id"]) : "";
		if (!$id) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$invoices = \think\Db::name("invoices")->field("uid,subtotal,total")->where("id", $id)->find();
		$client = \think\Db::name("clients")->field("credit,currency")->where("id", $invoices["uid"])->find();
		$currency = \think\Db::name("currencies")->field("prefix,suffix")->where("id", $client["currency"])->find();
		$prefix = $currency["prefix"];
		$suffix = $currency["suffix"];
		$client_credit = $prefix . $client["credit"] . $suffix;
		$surplus = getSurplus($id);
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "client_credit" => $client_credit, "surplus" => $surplus, "invoice_credit" => $prefix . bcsub($invoices["subtotal"], $invoices["total"], 2) . $suffix]);
	}
	/**
	 * @title 添加付款金额到账单
	 * @author wyh
	 * @url admin/invoice/add_pay_invoice
	 * @method POST
	 * @param .name:id type:int require:1  other: desc:账单ID
	 * @param .name:amount type:int require:1  other: desc:金额
	 * @return
	 */
	public function addPayInvoice()
	{
		$params = \request()->only(["id", "amount"]);
		$id = isset($params["id"]) ? intval($params["id"]) : "";
		if (!$id) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$amount = isset($params["amount"]) ? floatval($params["amount"]) : 0;
		if ($amount < 0) {
			return jsonrule(["status" => 400, "msg" => "应用余额至少为0"]);
		}
		$invoices = \think\Db::name("invoices")->field("uid,credit,subtotal,total,status")->where("id", $id)->where("delete_time", 0)->find();
		$credit = $invoices["credit"];
		bcscale(2);
		$client = \think\Db::name("clients")->field("id,credit")->where("id", $invoices["uid"])->find();
		if ($client["credit"] < $amount) {
			return jsonrule(["msg" => "账户余额不足", "stauts" => 400]);
		}
		$invoice_logic = new \app\common\logic\Invoices();
		$paid = $invoice_logic->refundAndApply($id);
		$surplus = bcsub($invoices["subtotal"], $paid);
		if ($surplus < $amount) {
			return jsonrule(["status" => 400, "msg" => "您不可以使用比总账单的金额(结余)还多的余额"]);
		}
		$invoice_credit = bcadd($credit, $amount);
		$invoice_total = $invoices["subtotal"] - $invoice_credit;
		$invoice_data = ["credit" => $invoice_credit, "total" => $invoice_total];
		$total = bcsub($surplus, $amount, 2);
		\think\Db::startTrans();
		try {
			\think\Db::name("clients")->where("id", $invoices["uid"])->setDec("credit", $amount);
			\think\Db::name("invoices")->where("id", $id)->where("delete_time", 0)->update($invoice_data);
			if ($amount > 0) {
				credit_log(["uid" => $client["id"], "desc" => "Credit Applied to Invoice #" . $id, "amount" => $amount, "relid" => $id]);
			}
			\think\Db::commit();
			active_log_final(sprintf($this->lang["Invoice_admin_addPayInvoice"], $invoices["uid"], $id), $invoices["uid"], 6, $id);
		} catch (\Exception $e) {
			\think\Db::rollback();
			return jsonrule(["status" => 400, "msg" => lang("ADD FAIL")]);
		}
		if ($total == 0) {
			$invoice_data2["status"] = "Paid";
			$invoice_data2["paid_time"] = time();
			\think\Db::name("invoices")->where("id", $id)->update($invoice_data2);
			$invoice_logic = new \app\common\logic\Invoices();
			$invoice_logic->is_admin = true;
			$invoice_logic->processPaidInvoice($id);
		}
		return jsonrule(["status" => 200, "msg" => lang("ADD SUCCESS")]);
	}
	/**
	 * @title 向账单使用信用额
	 * @description 接口说明:向账单使用信用额
	 * @author xj
	 * @url  admin/invoice/apply_credit_limit
	 * @method POST
	 * @param .name:id type:int require:1  other: desc:账单ID
	 * @param .name:amount type:int require:1  other: desc:金额
	 */
	public function applyCreditLimit()
	{
		try {
			$param = \request()->param();
			$invoices = \think\Db::name("invoices")->field("uid,credit,subtotal,total,status")->where("id", $param["id"])->where("delete_time", 0)->find();
			$uid = $invoices["uid"];
			$invoiceid = $param["id"];
			$this->checkInvoice($uid, $invoiceid);
			if ($invoices["credit"] > 0) {
				throw new \think\Exception("当前账单使用了余额,不可使用信用额支付");
			}
			if ($this->getInterfacePay($invoiceid, $uid)) {
				throw new \think\Exception("当前账单使用了接口支付,不可使用信用额支付");
			}
			$model = \think\Db::name("clients")->where("id", $uid)->value("is_open_credit_limit");
			if (!$model) {
				throw new \think\Exception("信用额未开通,不可使用信用额支付");
			}
			if ($this->getUseCreditLimit($uid) < $invoices["total"]) {
				throw new \think\Exception("当前信用额余额不足,不可使用信用额支付");
			}
			$update_invoice = ["paid_time" => time(), "status" => "Paid", "use_credit_limit" => 1, "payment_status" => "Paid"];
			hook("invoice_paid", ["invoice_id" => $invoiceid]);
			try {
				\think\Db::startTrans();
				\think\Db::name("invoices")->where("id", $invoiceid)->update($update_invoice);
				if ($invoices["total"] > 0) {
					if ($this->getUseCreditLimit($uid) < $invoices["total"]) {
						active_log_final(sprintf($this->lang["Order_admin_clients_updatecreditlimit_fail"], $uid), $uid, 6, $invoiceid);
						throw new \Exception("剩余信用额不足");
					}
					\think\Db::commit();
				}
			} catch (\Throwable $e) {
				\think\Db::rollback();
				return jsons(["status" => 400, "msg" => "支付失败:" . $e->getMessage()]);
			}
			$invoice_logic = new \app\common\logic\Invoices();
			$invoice_logic->processPaidInvoice($invoiceid);
			$result["status"] = 200;
			$result["msg"] = "支付成功";
			$result["data"]["hostid"] = \think\Db::name("invoice_items")->where("invoice_id", $invoiceid)->where("type", "host")->where("delete_time", 0)->column("rel_id");
			return jsonrule($result);
		} catch (\Throwable $e) {
			return jsonrule(["status" => 400, "msg" => $e->getMessage()]);
		}
	}
	private function getUseCreditLimit($uid)
	{
		$model = \think\Db::name("clients")->find($uid);
		$credit_limit = $model["credit_limit"];
		$amount_to_be_settled = \think\Db::name("invoices")->where("status", "Paid")->where("use_credit_limit", 1)->where("invoice_id", 0)->where("is_delete", 0)->where("uid", $uid)->sum("total");
		$unpaid = \think\Db::name("invoices")->where("type", "credit_limit")->where("status", "Unpaid")->where("is_delete", 0)->where("uid", $uid)->sum("total");
		$credit_limit_used = bcadd($amount_to_be_settled, $unpaid, 2);
		return bcsub($credit_limit, $credit_limit_used, 2) > 0 ? bcsub($credit_limit, $credit_limit_used, 2) : number_format(0, 2);
	}
	/**
	 * 检查账单id，是否存在，未支付，并且未过期
	 */
	private function checkInvoice($uid, $invoiceid)
	{
		if (empty($invoiceid)) {
			throw new \think\Exception("未找到支付项目");
		}
		$invoice_data = \think\Db::name("invoices")->where("id", $invoiceid)->where("uid", $uid)->find();
		if (empty($invoice_data)) {
			throw new \think\Exception("账单未找到");
		}
		if ($invoice_data["status"] == "Paid" || $invoice_data["total"] == 0) {
			throw new \think\Exception("账单已支付");
		}
		if (!empty($invoice_data["delete_time"])) {
			throw new \think\Exception("账单已过期");
		}
	}
	/**
	 * @title 从账单中删除付款金额
	 * @author wyh
	 * @url admin/invoice/delete_pay_invoice
	 * @method POST
	 * @param .name:id type:int require:1  other: desc:账单ID
	 * @param .name:amount type:int require:1  other: desc:金额
	 * @return
	 */
	public function deletePayInvoice()
	{
		$params = \request()->only(["id", "amount"]);
		$id = isset($params["id"]) ? intval($params["id"]) : "";
		if (!$id) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$amount = isset($params["amount"]) ? floatval($params["amount"]) : 0;
		if ($amount < 0) {
			return jsonrule(["status" => 400, "msg" => "需删除余额至少为0"]);
		}
		$invoices = \think\Db::name("invoices")->field("uid,credit,subtotal,total")->where("id", $id)->where("delete_time", 0)->find();
		$credit = $invoices["credit"];
		if ($credit < $amount) {
			return jsonrule(["status" => 400, "msg" => lang("账单余额不足")]);
		}
		bcscale(2);
		$invoice_credit = bcsub($credit, $amount);
		$invoice_total = $invoices["total"] + $amount;
		$invoice_data = ["credit" => $invoice_credit, "total" => $invoice_total, "status" => "Refunded"];
		\think\Db::startTrans();
		try {
			\think\Db::name("clients")->where("id", $invoices["uid"])->setInc("credit", $amount);
			\think\Db::name("invoices")->where("id", $id)->where("delete_time", 0)->update($invoice_data);
			credit_log(["uid" => $invoices["uid"], "desc" => "Credit Removed from Invoice #" . $id, "amount" => -$amount, "relid" => $id]);
			\think\Db::commit();
			active_log_final(sprintf($this->lang["Invoice_admin_deletePayInvoice"], $invoices["uid"], $id), $invoices["uid"], 6, $id);
		} catch (\Exception $e) {
			\think\Db::rollback();
			return jsonrule(["status" => 400, "msg" => lang("ADD FAIL")]);
		}
		return jsonrule(["status" => 200, "msg" => lang("ADD SUCCESS")]);
	}
	/**
	 * @title 账单退款View
	 * @author wyh
	 * @url admin/invoice/refund_page
	 * @method GET
	 * @param .name:id type:int require:1  other: desc:账单ID
	 * @return
	 */
	public function refundPage()
	{
		$params = \request()->only(["id"]);
		$id = isset($params["id"]) ? intval($params["id"]) : "";
		if (!$id) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$uid = \think\Db::name("invoices")->where("id", $id)->value("uid");
		$credit = \think\Db::name("clients")->where("id", $uid)->value("credit");
		$accounts = \think\Db::name("accounts")->field("id,currency,pay_time as create_time,trans_id,amount_in")->where("invoice_id", $id)->where("delete_time", 0)->where("refund", 0)->where("gateway", "<>", "")->select()->toArray();
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "accounts" => array_values($accounts), "credit" => $credit]);
	}
	/**
	 * @title 账单退款
	 * @author wyh
	 * @url admin/invoice/refund
	 * @method POST
	 * @param .name:id type:int require:0  other: desc:明细id
	 * @param .name:amount type:int require:0  other: desc:金额（不传传表示全部）
	 * @param .name:type type:int require:0  other: desc:默认addascredit退款至余额,only仅标记为退款,Refunded
	 * @param .name:trans_id type:int require:0  other: desc:当type=only时，显示此字段
	 * @param .name:email type:int require:0  other: desc:(1：发送邮件，0：不)
	 * @return
	 */
	public function refund()
	{
		$params = $this->request->param();
		$id = isset($params["id"]) ? intval($params["id"]) : "";
		if (!$id) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$amount = isset($params["amount"]) ? floatval($params["amount"]) : 0;
		$email = isset($params["email"]) ? intval($params["email"]) : 0;
		$type = isset($params["type"]) ? strtolower($params["type"]) : "addascredit";
		$accounts = \think\Db::name("accounts")->where("id", $id)->where("delete_time", 0)->find();
		$uid = $accounts["uid"];
		$invoice_id = $accounts["invoice_id"];
		$amount_out = \think\Db::name("accounts")->where("refund", $id)->where("delete_time", 0)->sum("amount_out");
		$amount_in = $accounts["amount_in"];
		$diff = bcsub($amount_in, $amount_out, 2);
		$amount = $amount == 0 ? $accounts["amount_in"] : $amount;
		$invoices = \think\Db::name("invoices")->where("id", $invoice_id)->find();
		if ($invoices["type"] == "credit_limit") {
			return jsonrule(["status" => 400, "msg" => lang("信用额还款账单不可退款")]);
		}
		if ($amount_in < $amount) {
			return jsonrule(["status" => 400, "msg" => lang("退款不能大于付款")]);
		}
		if ($diff < $amount) {
			return jsonrule(["status" => 400, "msg" => lang("退款过多")]);
		}
		$lang = get_system_lang();
		if ($type == "addascredit") {
			$data = ["uid" => $uid, "currency" => $accounts["currency"], "gateway" => "退款至余额", "create_time" => time(), "pay_time" => time(), "description" => "产品退款 Transaction ID " . $accounts["trans_id"], "amount_out" => $amount, "invoice_id" => $invoice_id, "refund" => $id];
			$res = \think\Db::name("accounts")->insertGetId($data);
			$account_credit = ["uid" => $uid, "currency" => $accounts["currency"], "create_time" => time(), "pay_time" => time(), "description" => "退款至余额入账 Invoice ID {$invoice_id}", "amount_in" => $amount];
			\think\Db::name("accounts")->insertGetId($account_credit);
			\think\Db::name("clients")->where("id", $uid)->setInc("credit", $amount);
			credit_log(["uid" => $uid, "desc" => "Credit from Refund of Invoice ID {$invoice_id}", "amount" => $amount]);
		} elseif ($type == "only") {
			$data = ["uid" => $uid, "gateway" => "退款至接口", "currency" => $accounts["currency"], "create_time" => time(), "pay_time" => time(), "description" => "产品退款 Transaction ID " . $accounts["trans_id"], "amount_out" => $amount, "trans_id" => isset($params["trans_id"]) ? mb_substr($params["trans_id"], 0, 20) : "", "invoice_id" => $invoice_id, "refund" => $id];
			$res = \think\Db::name("accounts")->insertGetId($data);
		}
		\think\Db::name("invoices")->where("id", $invoice_id)->update(["status" => "Refunded"]);
		$subs = \think\Db::name("invoice_items")->field("rel_id,amount")->where("invoice_id", $invoice_id)->where("type", "combine")->select()->toArray();
		$base = 0;
		foreach ($subs as $sub) {
			if ($amount <= $sub["amount"]) {
				\think\Db::name("accounts")->insert(["uid" => $uid, "gateway" => $accounts["gateway"], "currency" => $accounts["currency"], "create_time" => time(), "pay_time" => time(), "description" => "产品退款 Transaction ID " . $accounts["trans_id"], "amount_out" => $amount, "trans_id" => isset($params["trans_id"]) ? mb_substr($params["trans_id"], 0, 20) : "", "invoice_id" => $sub["rel_id"], "refund" => $id, "delete_time" => 1]);
				\think\Db::name("invoices")->where("id", $sub["rel_id"])->update(["status" => "Refunded"]);
				break;
			} else {
				if ($sub["amount"] <= $amount) {
					\think\Db::name("accounts")->insert(["uid" => $uid, "gateway" => $accounts["gateway"], "currency" => $accounts["currency"], "create_time" => time(), "pay_time" => time(), "description" => "产品退款 Transaction ID " . $accounts["trans_id"], "amount_out" => $sub["amount"], "trans_id" => isset($params["trans_id"]) ? mb_substr($params["trans_id"], 0, 20) : "", "invoice_id" => $sub["rel_id"], "refund" => $id, "delete_time" => 1]);
					\think\Db::name("invoices")->where("id", $sub["rel_id"])->update(["status" => "Refunded"]);
					$amount = $amount - $sub["amount"];
					$base += $sub["amount"];
				}
			}
		}
		$host = \think\Db::name("orders")->alias("a")->field("b.id,a.uid,a.id as orderid,a.amount")->leftJoin("host b", "a.id = b.orderid")->where("a.invoiceid", $invoice_id)->where("a.delete_time", 0)->find();
		if ($res && $email) {
			$email_logic = new \app\common\logic\Email();
			$email_logic->is_admin = true;
			$email_logic->sendEmailBase($host["id"], "账单退款提醒", "invoice", true);
		}
		$message_template_type = array_column(config("message_template_type"), "id", "name");
		$sms = new \app\common\logic\Sms();
		$client = check_type_is_use($message_template_type[strtolower("order_refund")], $host["uid"], $sms);
		if ($client) {
			$params = ["order_id" => $host["id"], "order_total_fee" => $host["amount"]];
			$sms->sendSms($message_template_type[strtolower("order_refund")], $client["phone_code"] . $client["phonenumber"], $params, false, $host["uid"]);
		}
		$text = " 交易明细处";
		active_log_final(sprintf($this->lang["Invoice_admin_refund"], $uid, $invoice_id, $amount) . $text, $uid, 6, $invoice_id);
		$hook_data = ["invoiceid" => $invoice_id, "amount" => $amount];
		hook("invoice_refunded", $hook_data);
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * @title 备注页面
	 * @description 备注页面
	 * @param .name:id type:int require:1  other: desc:账单ID
	 * @author wyh
	 * @url admin/invoice/notes_page
	 * @method GET
	 */
	public function notesPage()
	{
		$params = $this->request->param();
		$id = isset($params["id"]) ? intval($params["id"]) : "";
		if (!$id) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$notes = \think\Db::name("invoices")->where("id", $id)->value("notes");
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "notes" => htmlspecialchars_decode($notes)]);
	}
	/**
	 * @title 备注
	 * @description 备注
	 * @param .name:id type:int require:1  other: desc:账单ID
	 * @param .name:notes type:int require:0  other: desc:备注信息
	 * @author wyh
	 * @url admin/invoice/notes
	 * @method POST
	 */
	public function notes()
	{
		$params = $this->request->param();
		$id = isset($params["id"]) ? intval($params["id"]) : "";
		if (!$id) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$invo = \think\Db::name("invoices")->field("uid,notes")->where("id", $id)->find();
		if (!empty($params["notes"]) && $params["notes"] != $invo["notes"]) {
			active_log_final(sprintf($this->lang["Invoice_admin_notes"], $invo["uid"], $id, $invo["notes"], $params["notes"]), $invo["uid"], 6, $id);
		}
		\think\Db::name("invoices")->where("id", $id)->update(["notes" => $params["notes"], "update_time" => time()]);
		$hook_data = ["invoiceid" => $id, "content" => html_entity_decode($params["notes"], ENT_QUOTES)];
		hook("invoice_notes", $hook_data);
		return jsonrule(["status" => 200, "msg" => lang("UPDATE SUCCESS")]);
	}
	/**
	 * @title 发送账单邮件（摘要页面）
	 * @param .name:invoice_id type:int require:0  other: desc:账单id
	 * @param .name:email_id type:int require:0  other: desc:邮件模板id
	 * @author wyh
	 * @url admin/invoice/email
	 * @method POST
	 * @return
	 */
	public function invoceEmail()
	{
		$invoice_id = \request()->param("invoice_id/d");
		$email_id = \request()->param("email_id/d");
		$eamil = new \app\common\logic\Email();
		$eamil->is_admin = true;
		$invoice = \think\Db::name("invoice_items")->where([["invoice_id", "=", $invoice_id]])->find();
		if (!isset($invoice["rel_id"])) {
			return jsonrule(["status" => 400, "msg" => lang("参数错误")]);
		}
		$relid = $invoice["rel_id"];
		if ($invoice["type"] === "upgrade") {
			$upgrade = \think\Db::name("upgrades")->where([["id", "=", $relid]])->find();
			if (isset($upgrade["relid"])) {
				$relid = $upgrade["relid"];
			}
		}
		$res = $eamil->sendEmail($email_id, $relid);
		$name = \think\Db::name("email_templates")->where("id", $email_id)->value("name");
		if ($res) {
			active_log_final("账单 Invoice ID:{$invoice_id}发送邮件 - " . $name . " - 发送成功", $invoice["uid"], 6, $invoice_id);
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
		} else {
			active_log_final("账单 Invoice ID:{$invoice_id}发送邮件 - " . $name . " - 发送失败", $invoice["uid"], 6, $invoice_id);
			return jsonrule(["status" => 400, "msg" => lang("发送失败")]);
		}
	}
	/**
	 * @title 编辑账单项目
	 * @param .name:id type:dict require:1  other:账单ID
	 * @param .name:uid type:dict require:1  other:客户ID
	 * @param .name:items[] type:dict require:0  other:项目ID
	 * @param .name:description[项目ID] type:dict require:0  other:描述
	 * @param .name:amount[项目ID] type:dict require:1  other:金额
	 * @param .name:taxed[项目ID] type:dict require:1  other:是否收税,1是，0否默认
	 * @param .name:split type:dict require:1  other:将勾选项目拆分到新订单,1是，0否默认
	 * @param .name:add_description type:dict require:1  other:新增描述，必传，没有则传空
	 * @param .name:add_amount type:dict require:1  other:新增金额，必传，没有则传空
	 * @param .name:add_taxed type:dict require:0  other:新增是否收税，非必传
	 * @author wyh
	 * @url admin/invoice/edit_item
	 * @method POST
	 * @return
	 */
	public function editItem()
	{
		$params = $this->request->only(["id", "uid", "items", "description", "amount", "taxed", "split", "add_description", "add_amount", "add_taxed"]);
		$id = isset($params["id"]) ? intval($params["id"]) : "";
		if (!$id) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$invoices = \think\Db::name("invoices")->field("uid,credit")->where("id", $id)->find();
		$uid = $invoices["uid"];
		$credit = $invoices["credit"];
		$items = isset($params["items"]) ? $params["items"] : [];
		$invoice_items = \think\Db::name("invoice_items")->whereIn("id", $items)->select()->toArray();
		$items_id = array_column($invoice_items, "id");
		$split = isset($params["split"]) ? intval($params["split"]) : 0;
		if (isset($params["description"]) && is_array($params["description"])) {
			$description = $params["description"];
			$edit_data = [];
			foreach ($description as $key => $value) {
				$dec = "";
				$invoice_items = \think\Db::name("invoice_items")->where("id", $key)->find();
				$edit_data["description"] = $value;
				if (!empty($value) && $value != $invoice_items["description"]) {
					$dec .= "描述由“" . $edit_data["description"] . "”修改为“" . $value . "”，";
				}
				$edit_data["amount"] = isset($params["amount"][$key]) ? floatval($params["amount"][$key]) : 0;
				if (!empty($edit_data["amount"]) && $edit_data["amount"] != $invoice_items["amount"]) {
					$dec .= "金额由“" . $edit_data["amount"] . "”修改为“" . $edit_data["amount"] . "”，";
				}
				$edit_data["taxed"] = isset($params["taxed"][$key]) ? intval($params["taxed"][$key]) : 0;
				\think\Db::name("invoice_items")->where("id", $key)->update($edit_data);
				if ($dec != "") {
					active_log_final(sprintf($this->lang["Invoice_admin_editItem"], $id, $key, $dec), $uid, 6, $id);
				}
				unset($dec);
			}
		}
		if (isset($params["add_description"]) && !empty($params["add_description"]) || isset($params["add_amount"]) && !empty($params["add_amount"])) {
			$add_description = !empty($params["add_description"]) ? $params["add_description"] : "";
			$add_amount = !empty($params["add_amount"]) ? floatval($params["add_amount"]) : 0;
			$add_taxed = isset($params["add_taxed"]) ? intval($params["add_taxed"]) : 0;
			if (strlen($add_description) > 65565) {
				return jsonrule(["status" => 400, "msg" => lang("描述过长")]);
			}
			$insert_data = ["description" => $add_description, "amount" => $add_amount, "taxed" => $add_taxed, "invoice_id" => $id, "uid" => $uid ?? intval($params["uid"])];
			$it = \think\Db::name("invoice_items")->insertGetId($insert_data);
			if ($add_amount > 0) {
				\think\Db::name("invoices")->where("id", $id)->update(["status" => "Unpaid"]);
			}
			active_log_final(sprintf($this->lang["Invoice_admin_addItem"], $uid, $id, $it), $uid, 6, $id);
			unset($dec);
		}
		$subtotal = $this->getInvoiceSubtotal($id);
		\think\Db::name("invoices")->where("id", $id)->update(["subtotal" => $subtotal, "total" => $credit < 0 ? $subtotal : $subtotal - $credit]);
		if ($split) {
			foreach ($items_id as $v) {
			}
		}
		return jsonrule(["status" => 200, "msg" => lang("UPDATE SUCCESS"), "total" => $subtotal, "credit" => $credit]);
	}
	private function getInvoiceSubtotal($id)
	{
		$subtotal = \think\Db::name("invoice_items")->where("invoice_id", $id)->sum("amount");
		return $subtotal;
	}
	/**
	 * @title 账单项目删除
	 * @param .name:id type:dict require:0  other:账单项目id
	 * @author wyh
	 * @url admin/invoice/delete_item
	 * @method DELETE
	 * @return
	 */
	public function deleteItems()
	{
		$params = $this->request->param();
		$id = isset($params["id"]) ? intval($params["id"]) : "";
		if (!$id) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$invoice_id = \think\Db::name("invoice_items")->where("id", $id)->where("delete_time", 0)->value("invoice_id");
		$invoice_credit = \think\Db::name("invoices")->where("id", $invoice_id)->value("credit");
		$uid = \think\Db::name("invoices")->where("id", $invoice_id)->value("uid");
		\think\Db::startTrans();
		try {
			\think\Db::name("invoice_items")->delete($id);
			$subtotal = \think\Db::name("invoice_items")->where("invoice_id", $invoice_id)->where("delete_time", 0)->sum("amount");
			\think\Db::name("invoices")->where("id", $invoice_id)->update(["subtotal" => $subtotal, "total" => $invoice_credit < 0 ? $subtotal : bcsub($subtotal, $invoice_credit, 2)]);
			active_log_final(sprintf($this->lang["Invoice_admin_deleteItems"], $id, $uid, $invoice_id), $uid, 6, $invoice_id);
			\think\Db::commit();
		} catch (\Exception $e) {
			\think\Db::rollback();
			return jsonrule(["status" => 400, "msg" => lang("DELETE FAIL")]);
		}
		return jsonrule(["status" => 200, "msg" => lang("DELETE SUCCESS")]);
	}
	/**
	 * @title 删除账单流水
	 * @author wyh
	 * @url admin/invoice/delete_account/:id
	 * @method DELETE
	 * @param .name:id type:int require:0  other: desc:流水id
	 * @return
	 */
	public function delAccount()
	{
		$params = $this->request->param();
		$id = isset($params["id"]) ? intval($params["id"]) : "";
		if (!$id) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$invoice_id = \think\Db::name("accounts")->where("id", $id)->value("invoice_id");
		\think\Db::name("accounts")->delete($id);
		$invoice_logic = new \app\common\logic\Invoices();
		$invoices = \think\Db::name("invoices")->field("subtotal,uid")->where("id", $invoice_id)->find();
		$total = $invoice_logic->refundAndApply($invoice_id);
		if ($total < $invoices["subtotal"]) {
			\think\Db::name("invoices")->where("id", $invoice_id)->update(["status" => "Unpaid"]);
			active_log_final("删除了交易明细", $invoices["uid"], 6, $invoice_id);
		}
		hook("after_admin_delete_account", ["account_id" => $id]);
		return jsonrule(["status" => 200, "msg" => lang("DELETE SUCCESS")]);
	}
	/**
	 * @title 生成续费账单
	 * @description 生成续费账单
	 * @param .name:id type:int require:0  other: desc:hostid
	 * @author huanghao
	 * @url /admin/invoices_createnew
	 * @method POST
	 */
	public function createRenew()
	{
		$id = intval(input("post.id"), 0);
		$invoices = new \app\common\logic\Invoices();
		$invoices->is_admin = true;
		$result = $invoices->createRenew($id);
		return jsonrule($result, 201);
	}
	/**
	 * 获取收入数据
	 * @return array
	 */
	private function getInvoiceIncome()
	{
		$currency = getCurrencies();
		$arr = [];
		$start_time = strtotime(date("Y-m-d"));
		$end_time = strtotime("+1 days", $start_time);
		foreach ($currency as $v) {
			$paid_total = db("invoices")->alias("i")->leftjoin("clients c", "i.uid=c.id")->where("c.currency", $v["id"])->where("i.delete_time", 0)->where("i.status", "Paid")->sum("subtotal");
			$arr[$v["code"]]["Paid"] = $v["prefix"] . bcsub($paid_total, 0, 2) . $v["suffix"];
			$unpaid_total = \think\Db::name("invoices")->alias("i")->leftjoin("clients c", "i.uid=c.id")->whereBetweenTime("i.create_time", $start_time, $end_time)->where("c.currency", $v["id"])->where("i.delete_time", 0)->where("i.status", "Unpaid")->sum("subtotal");
			$accounts = \think\Db::name("accounts")->alias("a")->leftJoin("invoices b", "a.invoice_id = b.id")->leftjoin("clients c", "a.uid=c.id")->whereBetweenTime("a.pay_time", $start_time, $end_time)->where("a.delete_time", 0)->where("b.delete_time", 0)->where("c.currency", $v["id"])->where("b.status", "Unpaid")->select()->toArray();
			$amount_in = $amount_out = 0;
			foreach ($accounts as $account) {
				$amount_in += $account["amount_in"];
				$amount_out += $account["amount_out"];
			}
			$unpaid_total = $unpaid_total - $amount_in - $amount_out;
			$arr[$v["code"]]["Unpaid"] = $v["prefix"] . bcsub($unpaid_total, 0, 2) . $v["suffix"];
			$overdue_total = db("invoices")->alias("i")->leftjoin("clients c", "i.uid=c.id")->where("i.due_time", "<=", $start_time)->where("c.currency", $v["id"])->where("i.delete_time", 0)->where("i.status", "Unpaid")->sum("subtotal");
			$accounts_overdue = \think\Db::name("accounts")->alias("a")->leftJoin("invoices b", "a.invoice_id = b.id")->leftjoin("clients c", "a.uid=c.id")->where("a.pay_time", "<=", $start_time)->where("b.due_time", "<=", $start_time)->where("c.currency", $v["id"])->where("a.delete_time", 0)->where("b.delete_time", 0)->where("b.status", "Unpaid")->select()->toArray();
			$amount_in_overdue = $amount_out_overdue = 0;
			foreach ($accounts_overdue as $value) {
				$amount_in_overdue += $value["amount_in"];
				$amount_out_overdue += $value["amount_out"];
			}
			$overdue_total = $overdue_total - $amount_in_overdue + $amount_out_overdue;
			$arr[$v["code"]]["Overdue"] = $v["prefix"] . bcsub($overdue_total, 0, 2) . $v["suffix"];
		}
		return $arr;
	}
	public function invoicePayAfterHandle($invoiceId)
	{
		$invoiceInfo = db("invoices")->where("id", $invoiceId)->where("delete_time", 0)->find();
		$items = db("invoice_items")->where("invoice_id", $invoiceId)->select();
		foreach ($items as $k => $v) {
			if ($v["type"] == "host") {
				if (get_product_condition($v["rel_id"]) == "payment") {
					$Host = new \app\common\logic\Host();
					$Host->is_admin = true;
					$result = $Host->create($v["rel_id"]);
					$logic_run_map = new \app\common\logic\RunMap();
					$model_host = new \app\common\model\HostModel();
					$data_i = [];
					$data_i["host_id"] = $v["rel_id"];
					$data_i["active_type_param"] = [$v["rel_id"], ""];
					$is_zjmf = $model_host->isZjmfApi($data_i["host_id"]);
					if ($result["status"] == 200) {
						$data_i["description"] = " 账单付款后 - 开通 Host ID:{$data_i["host_id"]}的产品成功";
						if ($is_zjmf) {
							$logic_run_map->saveMap($data_i, 1, 400, 1, 1);
						}
						if (!$is_zjmf) {
							$logic_run_map->saveMap($data_i, 1, 200, 1, 1);
						}
					} else {
						$data_i["description"] = " 账单付款后 - 开通 Host ID:{$data_i["host_id"]}的产品失败：{$result["msg"]}";
						if ($is_zjmf) {
							$logic_run_map->saveMap($data_i, 0, 400, 1, 1);
						}
						if (!$is_zjmf) {
							$logic_run_map->saveMap($data_i, 0, 200, 1, 1);
						}
					}
				}
			} elseif ($v["type"] == "renew") {
				$this->renewHandle($v["rel_id"]);
			} elseif ($v["type"] == "recharge") {
				$res4 = db("clients")->where("id", $invoiceId)->setInc("credit", $invoiceInfo["total"]);
				$lang = get_system_lang();
				credit_log(["uid" => $invoiceInfo["uid"], "desc" => sprintf($lang["recharge_ok"], $invoiceId), "amount" => $invoiceInfo["total"], "relid" => $invoiceId]);
			} elseif ($v["type"] == "upgrade") {
				$cart = new \app\common\logic\Cart();
				$cart->doUpgrade($v["rel_id"]);
			}
		}
	}
	private function renewHandle($id)
	{
		if (get_product_condition($id) == "payment") {
			$Host = new \app\common\logic\Host();
			$Host->is_admin = true;
			$res = db("host")->field("id,")->where("id", $id)->find();
			if ($res == "Suspended") {
				$result = $Host->unsuspend($id);
				$logic_run_map = new \app\common\logic\RunMap();
				$model_host = new \app\common\model\HostModel();
				$data_i = [];
				$data_i["host_id"] = $id;
				$data_i["active_type_param"] = [$id, 0, "", 0];
				$is_zjmf = $model_host->isZjmfApi($data_i["host_id"]);
				if ($result["status"] == 200) {
					$data_i["description"] = " 成功续费产品 - 解除暂停 Host ID:{$data_i["host_id"]}的产品成功";
					if ($is_zjmf) {
						$logic_run_map->saveMap($data_i, 1, 400, 3);
					}
					if (!$is_zjmf) {
						$logic_run_map->saveMap($data_i, 1, 300, 3);
					}
				} else {
					$data_i["description"] = " 成功续费产品 - 解除暂停 Host ID:{$data_i["host_id"]}的产品失败：{$result["msg"]}";
					if ($is_zjmf) {
						$logic_run_map->saveMap($data_i, 0, 400, 3);
					}
					if (!$is_zjmf) {
						$logic_run_map->saveMap($data_i, 0, 300, 3);
					}
				}
				$nextTime = getNextTime($res["billingcycle"]);
				$data = ["nextduedate" => $nextTime, "nextinvoicedate" => $nextTime, "update_time" => time(), "id" => $res["id"]];
				$up = db("host")->update($data);
				active_log_final(sprintf($this->lang["Invoice_admin_renewHandle"], $res["uid"], $id), $res["uid"], 6, $id);
				if (empty($up)) {
					trace("续费产品失败", "info");
				}
			}
		}
	}
	/**
	 * @title 合并账单页面
	 * @description 接口说明:合并账单页面,基础数据
	 * @param .name:uid type:int require:1 default:1 other: desc:客户ID
	 * @param .name:ids type:array require:1 default:1 other: desc:账单ID 数组
	 * @return count:多少笔未支付
	 * @return total:总金额
	 * @return  invoices:账单数据@
	 * @items  description:描述
	 * @items  amount:金额
	 * @items  type:类型
	 * @items  type_zh:中文类型
	 * @throws
	 * @author wyh
	 * @time 2020-12-04
	 * @url /admin/get_combine_invoices
	 * @method GET
	 */
	public function getCombineInvoices()
	{
		$param = $this->request->param();
		$uid = intval($param["uid"]);
		$tmp = \think\Db::name("clients")->where("id", $uid)->find();
		if (empty($tmp)) {
			return jsonrule(["status" => 400, "msg" => "用户不存在"]);
		}
		$ids = $param["ids"];
		if ($ids && !is_array($ids)) {
			$ids = [$ids];
		}
		$items = config("invoice_type");
		$where = function (\think\db\Query $query) use($uid, $ids) {
			$query->where("uid", $uid)->where("delete_time", 0)->where("type", "<>", "combine")->where("status", "Unpaid");
			if (!empty($ids)) {
				$query->whereIn("id", $ids);
			}
		};
		$count = \think\Db::name("invoices")->field("id,subtotal,total")->where($where)->count();
		$invoices = \think\Db::name("invoices")->field("id,subtotal,total")->where($where)->select()->toArray();
		$data = [];
		$total = 0;
		foreach ($invoices as &$v) {
			$total += $v["subtotal"];
			$items = \think\Db::name("invoice_items")->field("id,type,description,amount,type as type_zh")->where("invoice_id", $v["id"])->where("uid", $uid)->where("delete_time", 0)->withAttr("type_zh", function ($value, $data) use($items) {
				return $items[$data["type"]] ?: $value;
			})->select()->toArray();
			$v["items"] = $items;
		}
		$data["invoices"] = $invoices;
		$data["total"] = $total;
		$data["count"] = $count;
		$data["currency"] = getUserCurrency($uid);
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 合并账单
	 * @description 接口说明:合并账单,立即支付后与购物车支付逻辑一样:金额为0，购买成功；否则生成账单ID,调支付组件
	 * @param .name:uid type:int require:1 default:1 other: desc:客户ID
	 * @param .name:ids type:array require:1 default:1 other: desc:账单ID 数组
	 * @throws
	 * @author wyh
	 * @time 2020-12-04
	 * @url /admin/combine_invoices
	 * @method POST
	 */
	public function combineInvoices()
	{
		$param = $this->request->param();
		$ids = $param["ids"];
		if ($ids && !is_array($ids)) {
			$ids = [$ids];
		}
		$uid = intval($param["uid"]);
		$client = \think\Db::name("clients")->where("id", $uid)->find();
		if (empty($client)) {
			return jsonrule(["status" => 400, "msg" => "用户不存在"]);
		}
		$payment = $client["defaultgateway"];
		$where = function (\think\db\Query $query) use($uid, $ids) {
			$query->where("uid", $uid)->where("delete_time", 0)->where("status", "Unpaid")->where("type", "<>", "combine");
			if (!empty($ids)) {
				$query->whereIn("id", $ids);
			}
		};
		$invoices = \think\Db::name("invoices")->field("id,subtotal,total,payment")->where($where)->select()->toArray();
		$subtotal = $total = 0;
		foreach ($invoices as $v) {
			$total += $v["total"];
			$subtotal += $v["subtotal"];
		}
		$invoice_data = ["uid" => $uid, "invoice_num" => "", "create_time" => time(), "due_time" => time(), "paid_time" => 0, "last_capture_attempt" => 0, "subtotal" => $subtotal, "credit" => 0, "tax" => 0, "tax2" => 0, "total" => $subtotal, "taxrate" => 0, "taxrate2" => 0, "status" => "Unpaid", "payment" => $payment, "notes" => "", "type" => "combine"];
		\think\Db::startTrans();
		try {
			$ided = \think\Db::name("invoices")->where("type", "combine")->where("uid", $uid)->where("delete_time", 0)->where("status", "Unpaid")->value("id");
			\think\Db::name("invoices")->where("type", "combine")->where("uid", $uid)->where("delete_time", 0)->where("status", "Unpaid")->delete();
			\think\Db::name("invoice_items")->where("type", "combine")->where("invoice_id", $ided)->where("uid", $uid)->where("delete_time", 0)->delete();
			$invoice_id = \think\Db::name("invoices")->insertGetId($invoice_data);
			$insert_all = [];
			foreach ($invoices as $vv) {
				$insert_all[] = ["invoice_id" => $invoice_id, "uid" => $uid, "type" => "combine", "rel_id" => $vv["id"], "description" => "合并账单#Invoice ID " . $vv["id"], "amount" => $vv["subtotal"], "taxed" => 0, "due_time" => 0, "payment" => $vv["payment"], "notes" => "", "delete_time" => 0];
			}
			\think\Db::name("invoice_items")->insertAll($insert_all);
			\think\Db::commit();
		} catch (\Exception $e) {
			\think\Db::rollback();
			return jsons(["status" => 400, "msg" => lang("FAIL MESSAGE")]);
		}
		$data = ["invoice_id" => $invoice_id];
		$hook_data = ["invoiceid" => $invoice_id, "combined_invoice" => array_column($invoices, "id")];
		hook("invoice_combine", $hook_data);
		if ($subtotal == 0) {
			\think\Db::name("invoices")->where("id", $invoice_id)->update(["status" => "Paid", "paid_time" => time(), "update_time" => time()]);
			$invoice_logic = new \app\common\logic\Invoices();
			$invoice_logic->processPaidInvoice($invoice_id);
			return jsons(["status" => 1001, "msg" => lang("BUY_SUCCESS")]);
		} else {
			return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
		}
	}
	/**
	 * @title 续费订单列表
	 * @description 接口说明:
	 * @param .name:page type:int require:0  other: desc:页码
	 * @param .name:limit type:int require:0  other: desc:长度
	 * @param .name:order type:string require:0  other: desc:排序字段
	 * @param .name:sort type:string require:0  other: desc:排序规则(asc/desc)
	 * @param .name:status type:string require:0  other: desc:状态(Pending待审核，Active已激活，Completed已完成,Suspend已暂停,Terminated被删除,Cancelled被取消,Fraud有欺诈)
	 * @param .name:ordernum type:int require:0  other: desc:订单号
	 * @param .name:start_time type:int require:0  other: desc:开始时间
	 * @param .name:end_time type:int require:0  other: desc:结束时间
	 * @param .name:amount type:int require:0  other: desc:金额
	 * @param .name:uid type:int require:0  other: desc:用户
	 * @param .name:payment type:int require:0  other: desc:支付方式
	 * @param .name:sale_id type:int require:0  other: desc:1,
	 * @return  list:列表@
	 * @list  id:编号
	 * @list  uid:用户id
	 * @list  ordernum：订单号
	 * @list  create_time:
	 * @list  username:
	 * @list  payment:付款方式
	 * @list  amount:总计
	 * @list  status:状态
	 * @author 上官🔪
	 * @url admin/invoice/renew
	 * @method get
	 */
	public function renew()
	{
		session_write_close();
		$page = input("page") ?? config("page");
		$limit = input("limit") ?? config("limit");
		$order = input("order");
		$sort = input("sort") ?? "desc";
		$params = $this->request->param();
		$data = ["page" => $page, "size" => $limit, "order" => $order, "sort" => $sort];
		$validate = new \app\admin\validate\InvoiceValidate();
		if (!$validate->check($data)) {
			return jsonrule($validate->getError(), 400);
		}
		$start_time = $params["start_time"] ?? 0;
		$end_time = $params["end_time"] ?? time();
		$fun = function (\think\db\Query $query) use($params) {
			$query->where("p.id", ">", 0);
			if (isset($params["uid"])) {
				$username = $params["uid"];
				$query->where("c.id", $username);
			}
			if (isset($params["amount"])) {
				$amount = $params["amount"];
				$query->where("ii.amount", "like", "%{$amount}%");
			}
			if (isset($params["payment"]) && !empty($params["payment"])) {
				if ($params["payment"] == "creditLimitPay") {
					$query->where("i.use_credit_limit", 1);
				} elseif ($params["payment"] == "creditPay") {
					$query->where("i.credit", ">", 0);
				} else {
					$query->where("i.payment", $params["payment"]);
				}
			}
		};
		$price_total = \think\Db::name("invoices")->alias("i")->leftjoin("clients c", "i.uid=c.id")->leftjoin("currencies cu", "cu.id = c.currency")->leftJoin("invoice_items ii", "ii.invoice_id = i.id")->leftJoin("host h", "ii.rel_id = h.id")->leftJoin("products p", "h.productid = p.id")->field("ii.amount")->where($fun)->where("i.paid_time", ">=", $start_time)->where("i.paid_time", "<=", $end_time)->where("ii.type", "renew")->where("i.status", "Paid");
		if ($this->user["id"] != 1 && $this->user["is_sale"]) {
			$price_total->whereIn("c.id", $this->str);
		}
		$price_total = $price_total->sum("ii.amount");
		$gateways = gateway_list1("gateways", 0);
		$rows = \think\Db::name("invoices")->alias("i")->leftjoin("clients c", "i.uid=c.id")->leftjoin("currencies cu", "cu.id = c.currency")->leftJoin("invoice_items ii", "ii.invoice_id = i.id")->leftJoin("host h", "ii.rel_id = h.id")->leftJoin("products p", "h.productid = p.id")->field("i.type,i.subtotal,c.username,c.companyname,i.id,i.uid,i.status,i.subtotal as sub,i.credit,i.use_credit_limit,i.paid_time,ii.amount,ii.amount as am,i.payment,cu.prefix,cu.suffix,i.delete_time,h.id as hostid,p.name,h.domain,h.dedicatedip,h.productid")->withAttr("payment", function ($value, $data) use($gateways) {
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
		})->withAttr("amount", function ($value, $data) {
			return $data["prefix"] . $value . $data["suffix"];
		})->where($fun)->where("i.paid_time", ">=", $start_time)->where("i.paid_time", "<=", $end_time)->where("ii.type", "renew")->where("i.status", "Paid");
		if ($this->user["id"] != 1 && $this->user["is_sale"]) {
			$rows->whereIn("c.id", $this->str);
		}
		$rows = $rows->page($page)->limit($limit)->order($order, $sort)->select()->toArray();
		$invoice_payment_status = config("invoice_payment_status");
		$price_total_page = 0;
		foreach ($rows as $k => $row) {
			$price_total_page = bcadd($row["am"], $price_total_page, 2);
		}
		$count = \think\Db::name("invoices")->alias("i")->leftjoin("clients c", "i.uid=c.id")->leftJoin("invoice_items ii", "ii.invoice_id = i.id")->leftJoin("host h", "ii.rel_id = h.id")->leftJoin("products p", "h.productid = p.id")->field("c.username,i.id")->where($fun)->where("i.paid_time", ">=", $start_time)->where("i.paid_time", "<=", $end_time)->where("ii.type", "renew")->where("i.status", "Paid");
		if ($this->user["id"] != 1 && $this->user["is_sale"]) {
			$count->whereIn("c.id", $this->str);
		}
		$count = $count->count();
		$arr = ["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "list" => $rows, "count" => $count, "price_total" => $price_total, "price_total_page" => $price_total_page];
		session_write_close();
		return jsonrule($arr);
	}
	/**
	 * @title 账单内页日志
	 * @description 接口说明:
	 * @param .name:invoice_id type:int require:0  other: desc:账单号
	 * @param .name:uid type:int require:0  other: desc:用户id
	 * @url admin/invoice/invoiceLog
	 * @return  data:列表@
	 * @list  id:编号
	 * @list  uid:用户id
	 * @list  user：操作人
	 * @list  create_time:
	 * @list  ipaddr:ip地址
	 * @list  new_desc:描述
	 * @list  port:端口号
	 * @method get
	 */
	public function invoiceLog()
	{
		$params = $this->request->param();
		$list = \think\Db::name("activity_log")->field("create_time,id,ipaddr,description as new_desc,uid,user,port")->where("type", 6)->where("uid", $params["uid"])->where("type_data_id", $params["invoice_id"])->withAttr("ipaddr", function ($value, $data) {
			if (empty($data["port"])) {
				return $value;
			} else {
				return $value .= ":" . $data["port"];
			}
		})->withAttr("create_time", function ($value) {
			return date("Y-m-d H:i:s", $value);
		})->order("create_time", "desc")->select()->toArray();
		return jsonrule(["data" => $list, "status" => 200]);
	}
}