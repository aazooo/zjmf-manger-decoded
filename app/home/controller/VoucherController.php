<?php

namespace app\home\controller;

/**
 * @title 发票管理
 * @description 接口说明：发票管理
 */
class VoucherController extends CommonController
{
	private $type = ["person" => "个人", "company" => "公司"];
	private $voucher_type = ["common" => "增值税普通发票", "dedicated" => "增值税专用发票"];
	/**
	 * @title 区域三级联动
	 * @author wyh
	 * @time 2020-12-08
	 * @url voucher/arealist
	 * @method GET
	 * @return  area:区域
	 */
	public function getAreaList()
	{
		$areas = \think\Db::name("areas")->field("area_id,pid,name")->where("show", 1)->where("data_flag", 1)->select()->toArray();
		$areas = getStructuredTree($areas);
		$data = ["areas" => $areas];
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 获取货币
	 * @author wyh
	 * @time 2020-12-08
	 * @url voucher/currency
	 * @method GET
	 */
	public function getCurrency()
	{
		$uid = request()->uid;
		$data = ["currency" => getUserCurrency($uid)];
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 发票列表
	 * @author wyh
	 * @time 2020-12-08
	 * @url voucher/voucherlist
	 * @method GET
	 * @param .name:page type:int require:1 default:1 other: desc:第几页
	 * @param .name:limit type:int require:1 default:10 other: desc:每页多少条
	 * @param .name:order type:string require:1 default:10 other: desc:排序字段
	 * @param .name:sort type:int require:1 default:10 other: desc:AESC,DESC
	 * @return  total:总数
	 * @return  voucher:发票信息@
	 * @voucher  id:
	 * @voucher  create_time:申请时间
	 * @voucher  title:发票抬头
	 * @voucher  issue_type:发票性质
	 * @voucher  issue_type_zh:发票性质
	 * @voucher  amount:发票总额
	 * @voucher  status:状态
	 * @voucher  province:邮寄地址
	 * @voucher  city:邮寄地址
	 * @voucher  region:邮寄地址
	 * @voucher  detail:详细地址
	 * @voucher  name:快递
	 * @voucher  notes:备注
	 */
	public function getVoucherList()
	{
		$uid = request()->uid;
		$params = $this->request->param();
		$page = !empty($params["page"]) ? intval($params["page"]) : config("page");
		$limit = !empty($params["limit"]) ? intval($params["limit"]) : config("limit");
		$order = !empty($params["order"]) ? trim($params["order"]) : "a.id";
		$sort = !empty($params["sort"]) ? trim($params["sort"]) : "DESC";
		$voucher_status = config("voucher_status");
		$voucher = \think\Db::name("voucher")->alias("a")->field("a.id,a.invoice_id,a.create_time,b.title,b.issue_type,b.issue_type as issue_type_zh,e.subtotal as amount,a.status,a.status as status_zh,c.province,c.city,c.region,c.detail,d.name,a.notes")->leftJoin("voucher_type b", "a.type_id = b.id")->leftJoin("voucher_post c", "a.post_id = c.id")->leftJoin("express d", "a.express_id = d.id")->leftJoin("invoices e", "a.invoice_id = e.id")->where("a.uid", $uid)->withAttr("issue_type_zh", function ($value, $data) {
			return $this->type[$value];
		})->withAttr("status_zh", function ($value, $data) use($voucher_status) {
			return $voucher_status[$value];
		})->order($order, $sort)->page($page)->limit($limit)->select()->toArray();
		$total = \think\Db::name("voucher")->alias("a")->leftJoin("voucher_type b", "a.type_id = b.id")->leftJoin("voucher_post c", "a.post_id = c.id")->leftJoin("express d", "a.express_id = d.id")->where("a.uid", $uid)->count();
		foreach ($voucher as &$v) {
			$invoice_ids = \think\Db::name("voucher_invoices")->where("voucher_id", $v["id"])->column("invoice_id");
			$invoice_ids = array_unique($invoice_ids);
			$invoices_subtotal = \think\Db::name("invoices")->whereIn("id", $invoice_ids)->where("uid", $uid)->where("delete_time", 0)->sum("subtotal");
			$v["invoices_subtotal"] = $invoices_subtotal;
		}
		$data = ["voucher" => $voucher, "total" => $total];
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 发票详情
	 * @author wyh
	 * @time 2020-12-08
	 * @url voucher/voucherdetail
	 * @method GET
	 * @param .name:id type:int require:1 default:1 other: desc:发票ID
	 * @return  voucher:发票信息@
	 * @voucher  id:
	 * @voucher  create_time:申请时间
	 * @voucher  title:发票抬头
	 * @voucher  issue_type:发票性质
	 * @voucher  issue_type_zh:发票性质
	 * @voucher  amount:发票总额
	 * @voucher  status:状态
	 * @voucher  province:邮寄地址
	 * @voucher  city:邮寄地址
	 * @voucher  region:邮寄地址
	 * @voucher  detail:详细地址
	 * @voucher  name:快递
	 * @voucher  notes:备注
	 * @voucher  price:邮寄快递价格
	 * @return  invoices:账单信息@
	 * @invoices  description:产品名称
	 * @invoices  subtotal:金额
	 * @invoices  taxed:税率
	 * @invoices  taxed_amount:税额
	 * @return  voucher_amount:开发票扣税
	 */
	public function getVoucherDetail()
	{
		$param = $this->request->param();
		$id = intval($param["id"]);
		$uid = request()->uid;
		$type = config("invoice_type");
		unset($type["recharge"]);
		unset($type["combine"]);
		unset($type["voucher"]);
		unset($type["express"]);
		$type = array_keys($type);
		$taxed = configuration("voucher_rate") > 0 ? floatval(configuration("voucher_rate")) : 0;
		$voucher_status = config("voucher_status");
		$voucher = \think\Db::name("voucher")->alias("a")->field("a.id,a.create_time,b.title,b.issue_type,b.issue_type as issue_type_zh,a.amount,a.status,a.status as status_zh,c.province,c.city,c.region,c.detail,d.name,a.notes,d.price")->leftJoin("voucher_type b", "a.type_id = b.id")->leftJoin("voucher_post c", "a.post_id = c.id")->leftJoin("express d", "a.express_id = d.id")->withAttr("issue_type_zh", function ($value, $data) {
			return $this->type[$value];
		})->withAttr("status_zh", function ($value, $data) use($voucher_status) {
			return $voucher_status[$value];
		})->where("a.id", $id)->where("a.uid", $uid)->find();
		$invoice_ids = \think\Db::name("voucher_invoices")->where("voucher_id", $id)->column("invoice_id");
		$invoice_ids = array_unique($invoice_ids);
		$invoices = \think\Db::name("invoices")->field("id,tax as taxed,subtotal,subtotal as taxed_amount")->withAttr("taxed", function ($value, $data) use($taxed) {
			return $taxed . "%";
		})->withAttr("taxed_amount", function ($value, $data) use($taxed) {
			return $taxed / 100 * $data["subtotal"];
		})->whereIn("id", $invoice_ids)->where("uid", $uid)->where("delete_time", 0)->select()->toArray();
		$voucher_amount = 0;
		foreach ($invoices as &$invoice) {
			$voucher_amount += $invoice["taxed_amount"];
			$invoice["taxed_amount"] = round($invoice["taxed_amount"], 2);
			$items = \think\Db::name("invoice_items")->field("id,description")->where("invoice_id", $invoice["id"])->whereIn("type", $type)->select()->toArray();
			$invoice["items"] = $items;
		}
		$data = ["voucher" => $voucher, "invoices" => $invoices, "voucher_amount" => bcsub($voucher_amount, 0, 2)];
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 发票申请列表
	 * @author wyh
	 * @time 2020-12-08
	 * @url voucher/voucherrequest
	 * @method GET
	 * @param .name:keywords type:string require:0  other: desc:搜索关键字
	 * @param .name:page type:int require:1 default:1 other: desc:第几页
	 * @param .name:limit type:int require:1 default:10 other: desc:每页多少条
	 * @param .name:order type:string require:1 default:10 other: desc:排序字段
	 * @param .name:sort type:int require:1 default:10 other: desc:AESC,DESC
	 * @return  total:总数
	 * @return  invoices:账单@
	 * @invoices  id:账单ID
	 * @invoices  subtotal:金额
	 * @invoices  type:类型
	 * @invoices  type_zh:类型中文
	 * @invoices  paid_time:支付时间
	 */
	public function getVoucherRequest()
	{
		$uid = request()->uid;
		$params = $this->request->param();
		$keywords = $params["keywords"];
		$page = !empty($params["page"]) ? intval($params["page"]) : config("page");
		$limit = !empty($params["limit"]) ? intval($params["limit"]) : config("limit");
		$order = !empty($params["order"]) ? trim($params["order"]) : "id";
		$sort = !empty($params["sort"]) ? trim($params["sort"]) : "DESC";
		$type = config("invoice_type_all");
		unset($type["recharge"]);
		unset($type["combine"]);
		unset($type["voucher"]);
		$where = function (\think\db\Query $query) use($uid, $type, $keywords) {
			$query->where("uid", $uid)->where("delete_time", 0)->where("status", "Paid")->whereIn("type", array_keys($type));
			if ($keywords) {
				$query->where("id", $keywords);
			}
		};
		$invoices = \think\Db::name("invoices")->field("id,subtotal,type,type as type_zh,paid_time")->withAttr("type_zh", function ($value, $data) use($type) {
			return $type[$value];
		})->where($where)->order($order, $sort)->select()->toArray();
		$total = \think\Db::name("invoices")->where($where)->count();
		$invoice_filter = [];
		$i = 0;
		foreach ($invoices as $invoice) {
			$voucher_ids = \think\Db::name("voucher_invoices")->where("invoice_id", $invoice["id"])->column("voucher_id");
			$count = \think\Db::name("voucher")->whereIn("id", $voucher_ids)->where("status", "<>", "Reject")->find();
			if (!empty($count)) {
				$i++;
			} else {
				$invoice_filter[] = $invoice;
			}
		}
		$invoice_filter = array_slice($invoice_filter, ($page - 1) * $limit, $limit);
		$data = ["invoices" => $invoice_filter, "total" => $total - $i];
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 开具发票页面
	 * @author wyh
	 * @time 2020-12-08
	 * @url voucher/issuevoucher
	 * @method GET
	 * @param  .name:invoice_ids type:array require:1  other: desc:数组
	 * @return  type:开具类型：person个人,company公司
	 * @return  express:快递信息@
	 * @express  name:快递名称
	 * @express  price:快递价格
	 * @return  post:邮寄地址@
	 * @post  id:地址ID
	 * @post  province:邮寄地址
	 * @post  city:邮寄地址
	 * @post  region:邮寄地址
	 * @post  default:默认1地址,0否
	 * @return  title:抬头信息@
	 * @title  id:
	 * @title  title:抬头
	 * @title  issue_type:开具类型
	 * @return  invoices:账单信息@
	 * @invoices  description:产品名称
	 * @invoices  subtotal:金额
	 * @invoices  taxed:税率
	 * @invoices  taxed_amount:税额
	 * @return  voucher_amount:开发票扣税
	 */
	public function getIssueVoucher()
	{
		$param = $this->request->param();
		$uid = request()->uid;
		$invoice_ids = $param["invoice_ids"];
		if (!is_array($invoice_ids)) {
			$invoice_ids = [$invoice_ids];
		}
		$express = \think\Db::name("express")->field("id,name,price")->select()->toArray();
		$post = \think\Db::name("voucher_post")->field("id,province,city,region,default")->where("uid", $uid)->select()->toArray();
		$title = \think\Db::name("voucher_type")->field("id,title,issue_type")->where("uid", $uid)->select()->toArray();
		$title_filter = [];
		foreach ($title as $v) {
			$title_filter[$v["issue_type"]][] = $v;
		}
		$taxed = configuration("voucher_rate") > 0 ? floatval(configuration("voucher_rate")) : 0;
		$invoices = \think\Db::name("invoices")->field("id,tax as taxed,subtotal,subtotal as taxed_amount,type")->withAttr("taxed", function ($value, $data) use($taxed) {
			return $taxed . "%";
		})->withAttr("taxed_amount", function ($value, $data) use($taxed) {
			return $taxed / 100 * $data["subtotal"];
		})->whereIn("id", $invoice_ids)->where("uid", $uid)->where("delete_time", 0)->select()->toArray();
		$type = config("invoice_type");
		unset($type["recharge"]);
		unset($type["combine"]);
		unset($type["voucher"]);
		unset($type["express"]);
		$type = array_keys($type);
		$voucher_amount = 0;
		foreach ($invoices as &$invoice) {
			$voucher_amount += $invoice["taxed_amount"];
			$invoice["taxed_amount"] = round($invoice["taxed_amount"], 2);
			$items = \think\Db::name("invoice_items")->field("id,description")->where("invoice_id", $invoice["id"])->whereIn("type", $type)->withAttr("description", function ($value) {
				return str_replace("|", " ", $value);
			})->select()->toArray();
			$invoice["items"] = $items;
		}
		$data = ["type" => $this->type, "express" => $express, "post" => $post, "title" => $title_filter, "invoices" => $invoices, "voucher_amount" => bcsub($voucher_amount, 0, 2)];
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 开具发票
	 * @author wyh
	 * @time 2020-12-08
	 * @url voucher/issuevoucher
	 * @method POST
	 * @param  .name:invoice_ids[] type:array require:1  other: desc:数组 账单ID 13131905 13131904
	 * @param  .name:type_id type:int require:1  other: desc:发票类型ID 15
	 * @param  .name:post_id type:int require:1  other: desc:邮寄地址ID 3
	 * @param  .name:express_id type:int require:1  other: desc:快递ID 1
	 */
	public function postIssueVoucher()
	{
		$uid = request()->uid;
		$param = $this->request->param();
		$type_id = intval($param["type_id"]);
		$tmp1 = \think\Db::name("voucher_type")->where("uid", $uid)->where("id", $type_id)->find();
		if (empty($tmp1)) {
			return jsons(["status" => 400, "msg" => "抬头信息错误"]);
		}
		$post_id = intval($param["post_id"]);
		$tmp2 = \think\Db::name("voucher_post")->where("uid", $uid)->where("id", $post_id)->find();
		if (empty($tmp2)) {
			return jsons(["status" => 400, "msg" => "邮寄地址错误"]);
		}
		$express_id = intval($param["express_id"]);
		$tmp3 = \think\Db::name("express")->where("id", $express_id)->find();
		if (empty($tmp3)) {
			return jsons(["status" => 400, "msg" => "快递信息错误"]);
		}
		$invoice_ids = $param["invoice_ids"];
		if (!is_array($invoice_ids)) {
			$invoice_ids = [$invoice_ids];
		}
		$invoice_ids = array_unique($invoice_ids);
		\think\Db::startTrans();
		try {
			$total = 0;
			$invoice_items = [];
			$taxed = configuration("voucher_rate") > 0 ? floatval(configuration("voucher_rate")) : 0;
			$payment = \think\Db::name("clients")->where("id", $uid)->value("defaultgateway");
			$amount = \think\Db::name("invoices")->whereIn("id", $invoice_ids)->sum("subtotal");
			$voucher_price = $amount * $taxed / 100;
			$total += $voucher_price;
			$total += $tmp3["price"];
			$total = $total > 0 ? floatval($total) : 0;
			$invoice_data = ["uid" => $uid, "create_time" => time(), "subtotal" => $total, "credit" => 0, "total" => $total, "status" => "Unpaid", "payment" => $payment, "type" => "voucher", "url" => "invoicelist"];
			$invoice_id = \think\Db::name("invoices")->insertGetId($invoice_data);
			$voucher_data = ["uid" => $uid, "invoice_id" => $invoice_id, "post_id" => $post_id, "type_id" => $type_id, "express_id" => $express_id, "amount" => $total, "create_time" => time(), "check_time" => 0, "update_time" => 0, "status" => "Unpaid", "notes" => ""];
			$voucher_id = \think\Db::name("voucher")->insertGetId($voucher_data);
			$links = [];
			foreach ($invoice_ids as $v) {
				$links[] = ["voucher_id" => $voucher_id, "invoice_id" => $v];
			}
			\think\Db::name("voucher_invoices")->insertAll($links);
			$invoice_items[] = ["uid" => $uid, "type" => "voucher", "rel_id" => $voucher_id, "description" => "开具发票税费", "amount" => $voucher_price, "payment" => $payment];
			$invoice_items[] = ["uid" => $uid, "type" => "express", "rel_id" => $voucher_id, "description" => "发票快递费", "amount" => $tmp3["price"], "payment" => $payment];
			foreach ($invoice_items as &$vvv) {
				$vvv["invoice_id"] = $invoice_id;
			}
			\think\Db::name("invoice_items")->insertAll($invoice_items);
			\think\Db::commit();
		} catch (\Exception $e) {
			\think\Db::rollback();
			return jsons(["status" => 400, "msg" => lang("FAIL MESSAGE") . $e->getMessage()]);
		}
		$data = ["invoice_id" => $invoice_id];
		if ($total == 0) {
			\think\Db::name("invoices")->where("id", $invoice_id)->update(["status" => "Paid", "paid_time" => time(), "update_time" => time()]);
			$invoice_logic = new \app\common\logic\Invoices();
			$invoice_logic->processPaidInvoice($invoice_id);
			return jsons(["status" => 1001, "msg" => lang("BUY_SUCCESS")]);
		} else {
			return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
		}
	}
	/**
	 * @title 发票信息管理列表
	 * @author wyh
	 * @time 2020-12-09
	 * @url voucher/voucherinfolist
	 * @method GET
	 * @param .name:page type:int require:1 default:1 other: desc:第几页
	 * @param .name:limit type:int require:1 default:10 other: desc:每页多少条
	 * @param .name:order type:string require:1 default:10 other: desc:排序字段
	 * @param .name:sort type:int require:1 default:10 other: desc:AESC,DESC
	 * @return  voucher_type:发票信息@
	 * @voucher_type  title:抬头信息
	 * @voucher_type  issue_type:开具类型person个人，company企业
	 * @voucher_type  issue_type_zh:开具类型person个人，company企业
	 * @voucher_type  voucher_type:发票类型
	 * @voucher_type  voucher_type_zh:发票类型
	 * @voucher_type  tax_id:税务登记号
	 */
	public function getVoucherInfoList()
	{
		$uid = request()->uid;
		$params = $this->request->param();
		$page = !empty($params["page"]) ? intval($params["page"]) : config("page");
		$limit = !empty($params["limit"]) ? intval($params["limit"]) : config("limit");
		$order = !empty($params["order"]) ? trim($params["order"]) : "id";
		$sort = !empty($params["sort"]) ? trim($params["sort"]) : "DESC";
		$voucher_type = \think\Db::name("voucher_type")->field("id,title,issue_type,issue_type as issue_type_zh,voucher_type,voucher_type as voucher_type_zh,tax_id")->where("uid", $uid)->withAttr("issue_type_zh", function ($value, $data) {
			return $this->type[$value];
		})->withAttr("voucher_type_zh", function ($value, $data) {
			return $this->voucher_type[$value];
		})->order($order, $sort)->page($page)->limit($limit)->select()->toArray();
		$total = \think\Db::name("voucher_type")->where("uid", $uid)->count();
		$data = ["voucher_type" => $voucher_type ?: [], "total" => $total];
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 发票信息管理页面
	 * @author wyh
	 * @time 2020-12-09
	 * @url voucher/voucherinfo
	 * @method GET
	 * @param  .name:id type:int require:1  other: desc:发票管理信息ID(编辑才传此值)
	 * @return  issue_type:开具类型
	 * @return  voucher_type:发票类型
	 * @return  voucher_info:发票信息@
	 * @voucher_info  title:抬头信息
	 * @voucher_info  issue_type:开具类型person个人，company企业
	 * @voucher_info  voucher_type:发票类型：common普通，dedicated专用
	 * @voucher_info  tax_id:税务登记号
	 * @voucher_info  bank:开户行名称
	 * @voucher_info  account:开户银行账号
	 * @voucher_info  address:公司地址
	 * @voucher_info  phone:联系电话
	 */
	public function getVoucherInfo()
	{
		$param = $this->request->param();
		if (isset($param["id"])) {
			$id = intval($param["id"]);
			$tmp = \think\Db::name("voucher_type")->field("id,title,issue_type,voucher_type,tax_id,bank,account,address,phone")->where("id", $id)->find();
			if (empty($tmp)) {
				return jsons(["status" => 400, "msg" => lang("ID_ERROR")]);
			}
		}
		$data = ["issue_type" => $this->type, "voucher_type" => $this->voucher_type, "voucher_info" => $tmp ?: []];
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 发票信息管理添加、编辑
	 * @author wyh
	 * @time 2020-12-09
	 * @url voucher/voucherinfo
	 * @method POST
	 * @param  .name:id type:int require:1  other: desc:发票管理信息ID(编辑才传此值)
	 * @param  .name:issue_type type:string require:1  other: desc:开具类型person个人,company企业
	 * @param  .name:title type:string require:1  other: desc:发票抬头
	 * @param  .name:voucher_type type:string require:1  other: desc:发票类型：common普通，dedicated专用
	 * @param  .name:tax_id type:string require:1  other: desc:税务登记号
	 * @param  .name:bank type:string require:1  other: desc:开户行名称
	 * @param  .name:account type:string require:1  other: desc:开户银行账号
	 * @param  .name:address type:string require:1  other: desc:公司地址
	 * @param  .name:phone type:string require:1  other: desc:联系电话
	 */
	public function postVoucherInfo()
	{
		$uid = request()->uid;
		$param = $this->request->only(["id", "issue_type", "title", "voucher_type", "tax_id", "bank", "account", "address", "phone"]);
		if (isset($param["id"])) {
			$id = intval($param["id"]);
			$tmp = \think\Db::name("voucher_type")->where("id", $id)->find();
			if (empty($tmp)) {
				return jsons(["status" => 400, "msg" => lang("ID_ERROR")]);
			}
		}
		$validate = new \app\home\validate\VoucherValidate();
		if ($param["issue_type"] == "person") {
			if (!$validate->scene("voucher_info_person")->check($param)) {
				return jsons(["status" => 400, "msg" => $validate->getError()]);
			}
			$data = ["uid" => $uid, "title" => $param["title"], "issue_type" => $param["issue_type"]];
		} else {
			if (!$validate->scene("voucher_info_company")->check($param)) {
				return jsons(["status" => 400, "msg" => $validate->getError()]);
			}
			$data = ["uid" => $uid, "title" => $param["title"], "issue_type" => $param["issue_type"], "voucher_type" => $param["voucher_type"], "tax_id" => $param["tax_id"], "bank" => $param["bank"], "account" => $param["account"], "address" => $param["address"], "phone" => $param["phone"]];
		}
		if ($id) {
			$data["update_time"] = time();
			$res = \think\Db::name("voucher_type")->where("id", $id)->update($data);
		} else {
			$data["create_time"] = time();
			$res = \think\Db::name("voucher_type")->insert($data);
		}
		if ($res) {
			return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
		} else {
			return jsons(["status" => 400, "msg" => lang("FAIL MESSAGE")]);
		}
	}
	/**
	 * @title 发票信息管理删除
	 * @author wyh
	 * @time 2020-12-09
	 * @url voucher/voucherinfo
	 * @method DELETE
	 * @param  .name:id type:int require:1  other: desc:发票管理信息ID(编辑才传此值)
	 */
	public function deleteVoucherInfo()
	{
		$uid = request()->uid;
		$param = $this->request->param();
		$id = intval($param["id"]);
		$tmp = \think\Db::name("voucher_type")->where("uid", $uid)->where("id", $id)->find();
		if (empty($tmp)) {
			return jsons(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$count = \think\Db::name("voucher")->where("uid", $uid)->where("type_id", $id)->count();
		if ($count > 0) {
			return jsons(["status" => 400, "msg" => lang("发票信息已被使用,不可删除")]);
		}
		\think\Db::name("voucher_type")->where("uid", $uid)->where("id", $id)->delete();
		return jsons(["status" => 200, "msg" => lang("DELETE SUCCESS")]);
	}
	/**
	 * @title 收货地址列表
	 * @author wyh
	 * @time 2020-12-09
	 * @url voucher/voucherpostlist
	 * @method GET
	 * @param .name:page type:int require:1 default:1 other: desc:第几页
	 * @param .name:limit type:int require:1 default:10 other: desc:每页多少条
	 * @param .name:order type:string require:1 default:10 other: desc:排序字段
	 * @param .name:sort type:int require:1 default:10 other: desc:AESC,DESC
	 * @return  total:总数
	 * @return  voucher_post:地址信息@
	 * @voucher_post  username:
	 * @voucher_post  phone:
	 * @voucher_post  province:省
	 * @voucher_post  city:市
	 * @voucher_post  region:区
	 * @voucher_post  detail:详细地址
	 * @voucher_post  post:邮编
	 * @voucher_post  default:1默认
	 */
	public function getVoucherPostList()
	{
		$uid = request()->uid;
		$params = $this->request->param();
		$page = !empty($params["page"]) ? intval($params["page"]) : config("page");
		$limit = !empty($params["limit"]) ? intval($params["limit"]) : config("limit");
		$order = !empty($params["order"]) ? trim($params["order"]) : "id";
		$sort = !empty($params["sort"]) ? trim($params["sort"]) : "DESC";
		$voucher_post = \think\Db::name("voucher_post")->field("id,username,phone,province,city,region,detail,post,default")->where("uid", $uid)->order("default", "desc")->order($order, $sort)->page($page)->limit($limit)->select()->toArray();
		$total = \think\Db::name("voucher_post")->where("uid", $uid)->count();
		$data = ["voucher_post" => $voucher_post, "total" => $total];
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 收货地址详情
	 * @author wyh
	 * @time 2020-12-09
	 * @url voucher/voucherpost
	 * @method GET
	 * @param  .name:id type:int require:1  other: desc:收货地址ID(编辑才传此值)
	 * @return  voucher_post:收货地址详情@
	 * @voucher_post  username:
	 * @voucher_post  phone:
	 * @voucher_post  province:省
	 * @voucher_post  city:市
	 * @voucher_post  region:区
	 * @voucher_post  detail:详细地址
	 * @voucher_post  post:邮编
	 * @voucher_post  default:1默认
	 */
	public function getVoucherPost()
	{
		$uid = request()->uid;
		$param = $this->request->param();
		if (isset($param["id"])) {
			$id = intval($param["id"]);
			$tmp = \think\Db::name("voucher_post")->field("id,username,province,city,region,detail,post,default,phone")->where("uid", $uid)->where("id", $id)->find();
			if (empty($tmp)) {
				return jsons(["status" => 400, "msg" => lang("ID_ERROR")]);
			}
		}
		$data = ["voucher_post" => $tmp ?: []];
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 收货地址添加、编辑
	 * @author wyh
	 * @time 2020-12-09
	 * @url voucher/voucherpost
	 * @method POST
	 * @param  .name:id type:int require:1  other: desc:收货地址ID(编辑才传此值)
	 * @param  .name:username type:int require:1  other: desc:收件人
	 * @param  .name:province type:int require:1  other: desc:省
	 * @param  .name:city type:int require:1  other: desc:市
	 * @param  .name:region type:int require:1  other: desc:区
	 * @param  .name:detail type:int require:1  other: desc:详细地址
	 * @param  .name:phone type:int require:1  other: desc:
	 * @param  .name:post type:int require:1  other: desc:邮编
	 * @param  .name:default type:int require:1  other: desc:是否默认:1默认,0否
	 */
	public function postVoucherPost()
	{
		$uid = request()->uid;
		$param = $this->request->param();
		if (isset($param["id"])) {
			$id = intval($param["id"]);
			$tmp = \think\Db::name("voucher_post")->where("uid", $uid)->where("id", $id)->find();
			if (empty($tmp)) {
				return jsons(["status" => 400, "msg" => lang("ID_ERROR")]);
			}
		} else {
			$tmp = ["username" => $param["username"], "province" => $param["province"], "city" => $param["city"], "region" => $param["region"], "detail" => $param["detail"], "phone" => $param["phone"], "post" => $param["post"], "default" => $param["default"]];
			$repeat = serialize($tmp);
			if ($repeat == cache("voucher_address_post_" . $uid)) {
				return jsons(["status" => 200, "msg" => "请求成功"]);
			}
			if (!cache("voucher_address_post_" . $uid)) {
				cache("voucher_address_post_" . $uid, $repeat, 10);
			}
		}
		$validate = new \app\home\validate\VoucherValidate();
		if (!$validate->scene("voucher_post")->check($param)) {
			return jsons(["status" => 400, "msg" => $validate->getError()]);
		}
		$default = \think\Db::name("voucher_post")->where(function (\think\db\Query $query) use($id) {
			if ($id) {
				$query->where("id", "<>", $id);
			}
		})->where("default", 1)->where("uid", $uid)->find();
		$data = ["uid" => $uid, "username" => $param["username"], "province" => $param["province"], "city" => $param["city"], "region" => $param["region"], "detail" => $param["detail"], "phone" => $param["phone"], "post" => $param["post"], "default" => $param["default"]];
		if ($id) {
			$data["update_time"] = time();
			$res = \think\Db::name("voucher_post")->where("id", $id)->update($data);
		} else {
			$data["create_time"] = time();
			$res = \think\Db::name("voucher_post")->insert($data);
		}
		if ($res) {
			if (!empty($default) && $param["default"] == 1) {
				\think\Db::name("voucher_post")->where("id", $default["id"])->update(["default" => 0, "update_time" => time()]);
			}
			return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
		} else {
			return jsons(["status" => 400, "msg" => lang("FAIL MESSAGE")]);
		}
	}
	/**
	 * @title 收货地址删除
	 * @author wyh
	 * @time 2020-12-09
	 * @url voucher/voucherpost
	 * @method DELETE
	 * @param  .name:id type:int require:1  other: desc:收货地址ID
	 */
	public function deleteVoucherPost()
	{
		$param = $this->request->param();
		$uid = request()->uid;
		$id = intval($param["id"]);
		$tmp = \think\Db::name("voucher_post")->where("uid", $uid)->where("id", $id)->find();
		if (empty($tmp)) {
			return jsons(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$count = \think\Db::name("voucher")->where("uid", $uid)->where("post_id", $id)->count();
		if ($count > 0) {
			return jsons(["status" => 400, "msg" => lang("收货地址已被使用,不可删除")]);
		}
		\think\Db::name("voucher_post")->where("id", $id)->where("uid", $uid)->delete();
		$default = \think\Db::name("voucher_post")->where("uid", $uid)->where("default", 1)->find();
		if (empty($default)) {
			$new_default = \think\Db::name("voucher_post")->where("uid", $uid)->order("id", "desc")->find();
			\think\Db::name("voucher_post")->where("uid", $uid)->where("id", $new_default["id"])->update(["default" => 1, "update_time" => time()]);
		}
		return jsons(["status" => 200, "msg" => lang("DELETE SUCCESS")]);
	}
	/**
	 * @title 设置默认收货地址
	 * @author wyh
	 * @time 2020-12-09
	 * @url voucher/voucherdefaultpost
	 * @method POST
	 * @param  .name:id type:int require:1  other: desc:收货地址ID
	 * @param  .name:default type:int require:1  other: desc:1默认，0否
	 */
	public function postVoucherDefaultPost()
	{
		$param = $this->request->param();
		$uid = request()->uid;
		$id = intval($param["id"]);
		$tmp = \think\Db::name("voucher_post")->where("uid", $uid)->where("id", $id)->find();
		if (empty($tmp)) {
			return jsons(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$default = intval($param["default"]);
		$exist = \think\Db::name("voucher_post")->where("id", "<>", $id)->where("default", 1)->where("uid", $uid)->find();
		if ($default == 1) {
			if (!empty($exist)) {
				\think\Db::name("voucher_post")->where("uid", $uid)->where("id", $exist["id"])->update(["default" => 0, "update_time" => time()]);
			}
			\think\Db::name("voucher_post")->where("uid", $uid)->where("id", $id)->update(["default" => 1, "update_time" => time()]);
		} else {
			if (!empty($exist)) {
				\think\Db::name("voucher_post")->where("uid", $uid)->where("id", $id)->update(["default" => 0, "update_time" => time()]);
			} else {
				return jsons(["status" => 400, "msg" => "至少一个默认地址,不可更改"]);
			}
		}
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
	}
}