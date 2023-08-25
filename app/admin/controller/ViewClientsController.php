<?php

namespace app\admin\controller;

class ViewClientsController extends ViewAdminBaseController
{
	public function display($data)
	{
		$arr = preg_split("/\\//", $_SERVER["REDIRECT_URL"]);
		$data["REDIRECT_URL"] = $arr[2];
		return $this->view($arr[2], $data);
	}
	/**
	 * 客户列表
	 * @param Request $request
	 * @return mixed|string
	 */
	public function clients(\think\Request $request)
	{
		$param = $request->param();
		$result["titleList"] = ["ID", "姓名", "手机号/邮箱", "服务", "收入/支出", "余额", "创建时间", "客户等级", "创建分组", "状态", "销售"];
		$result["tip"] = "系统内所有客户信息，点击姓名可查看更多详细信息并对客户进行管理。<a href='https://www.idcsmart.com/wiki_list/401.html' target='_blank'>帮助文档</a>";
		$result["btnText"] = "添加客服";
		$result["modalTitle"] = $result["btnText"];
		$result["page"] = $param["page"] ? $param["page"] : 1;
		$UserManage = controller("UserManage");
		$res = $UserManage->clientList();
		$result["search"] = $res["search"];
		$result["pageSize"] = $this->ajaxPages($res["data"], 10, $result["page"], $res["total"]);
		foreach ($res["list"] as &$item) {
			$item["create_time"] = date("Y-m-d H:i:s", $item["create_time"]);
		}
		$result["data"] = $res["list"];
		return $this->display($result);
	}
	/**
	 * 实名认证
	 * @param Request $request
	 * @return mixed|string
	 */
	public function clientsauthentication(\think\Request $request)
	{
		$param = $request->param();
		$result["titleList"] = ["ID", "姓名", "实名认证名称", "身份证号码", "认证方式", "认证类型", "状态/原因", "提交时间", "操作"];
		$result["tip"] = "客户在进行实名认证时提交的信息记录，可在此下载证件及人工审核。<a href='https://www.idcsmart.com/wiki_list/401.html' target='_blank'>帮助文档</a>";
		$result["page"] = $param["page"] ? $param["page"] : 1;
		$result["search"] = config("client_certifi_status");
		$UserManage = controller("UserManage");
		$res = $UserManage->cerifyLogList();
		$result["pageSize"] = $this->ajaxPages($res["data"]["list"], 10, $result["page"], $res["data"]["total"]);
		foreach ($res["data"]["list"] as &$item) {
			$item["create_time"] = date("Y-m-d H:i:s", $item["create_time"]);
		}
		$result["total"] = $res["data"]["total"];
		$result["data"] = $res["data"]["list"];
		return $this->display($result);
	}
	/**
	 * 客户资源池
	 * @param Request $request
	 * @return mixed|string
	 */
	public function clientsresources(\think\Request $request)
	{
		$param = $request->param();
		$result["titleList"] = ["ID", "客户姓名", "收入", "余额", "创建时间", "添加销售"];
		$result["page"] = $param["page"] ? $param["page"] : 1;
		$common = controller("common");
		$saleList = $common->saleList();
		$result["saleList"] = $saleList["data"];
		$UserManage = controller("UserManage");
		$res = $UserManage->clientListRe();
		$result["pageSize"] = $this->ajaxPages($res["list"], 10, $result["page"], $res["total"]);
		foreach ($res["list"] as &$item) {
			$item["create_time"] = date("Y-m-d H:i:s", $item["create_time"]);
		}
		$result["total"] = $res["total"];
		$result["data"] = $res["list"];
		return $this->display($result);
	}
	/**
	 * 资源方入驻审核
	 * @param Request $request
	 * @return mixed|string
	 */
	public function resourcepool(\think\Request $request)
	{
		$param = $request->param();
		$result["titleList"] = ["ID", "姓名", "手机号/邮箱", "服务", "收入/支出", "余额", "创建时间", "客户等级", "创建分组", "状态", "销售"];
		$result["tip"] = "系统内所有客户信息，点击姓名可查看更多详细信息并对客户进行管理。<a href='#'>帮助文档</a>";
		$result["btnText"] = "添加客服";
		$result["modalTitle"] = $result["btnText"];
		$result["page"] = $param["page"] ? $param["page"] : 1;
		$UserManage = controller("UserManage");
		$res = $UserManage->clientList();
		$result["search"] = $res["search"];
		$result["pageSize"] = $this->ajaxPages($res["data"], 10, $result["page"], $res["total"]);
		foreach ($res["list"] as &$item) {
			$item["create_time"] = date("Y-m-d H:i:s", $item["create_time"]);
		}
		$result["data"] = $res["list"];
		return $this->display($result);
	}
	/**
	 * 我的业绩
	 * @param Request $request
	 * @return mixed|string
	 */
	public function salesstatistics(\think\Request $request)
	{
		$param = $request->param();
		$result["titleList"] = ["客户", "产品", "金额", "提成", "类型"];
		$result["tip"] = "有效查看销售人员的业绩。<a href=\"https://www.idcsmart.com/wiki_list/398.html\" target=\"_blank\">帮助文档</a>";
		$result["page"] = $param["page"] ? $param["page"] : 1;
		$sale = controller("sale");
		$saleUsers = $sale->saleUsers();
		$result["saleUsers"] = $saleUsers["list"];
		$timetype = $sale->getTimetype($request);
		$result["timetype"] = $saleUsers["data"]["time_type"];
		$saleStatistics = $sale->saleStatistics();
		$result["last_month"] = $saleStatistics["last_month"];
		$result["month"] = $saleStatistics["month"];
		$result["today"] = $saleStatistics["today"];
		$result["week"] = $saleStatistics["week"];
		$saleRecordsNew = $sale->saleRecordsNew();
		$result["last"] = $saleRecordsNew["last"];
		$result["ladder"] = $saleRecordsNew["now_ladder"];
		$result["data"] = $saleRecordsNew["record"];
		$result["type"] = $saleRecordsNew["type"];
		$result["month_sale_total"] = $saleRecordsNew["this_month_sale_total"];
		$result["month_commission_total"] = $saleRecordsNew["this_month_commission_total"];
		$result["total"] = $saleRecordsNew["total"];
		$result["pageSize"] = $this->ajaxPages($result["data"], 10, $result["page"], $result["total"]);
		$request->type = 2;
		$saleStatistics2 = $sale->saleStatistics();
		$result["saleStatistics2"] = $saleStatistics2["array"];
		return $this->display($result);
	}
	/**
	 * 推介计划
	 * @param Request $request
	 * @return mixed|string
	 */
	public function affiliates(\think\Request $request)
	{
		$param = $request->param();
		$result["titleList"] = ["ID", "姓名(公司名)", "访问数量", "注册数量", "订购数量", "总佣金", "已提现佣金"];
		$result["seachList"] = [["type" => "text", "label" => "客户名", "name" => "username", "content" => ""], ["type" => "text", "label" => "可提现佣金", "name" => "balance", "content" => ""], ["type" => "text", "label" => "访问量", "name" => "visitors", "content" => ""], ["type" => "text", "label" => "已提现佣金", "name" => "withdrawn", "content" => ""], ["type" => "text", "label" => "注册数量", "name" => "registcount", "content" => ""]];
		$result["page"] = $param["page"] ? $param["page"] : 1;
		$affiliate = controller("affiliate");
		$res = $affiliate->index($request);
		$result["pageSize"] = $this->ajaxPages($res["data"], 10, $result["page"], $res["total"]);
		$result["total"] = $res["total"];
		$result["data"] = $res["data"];
		return $this->display($result);
	}
	/**
	 * 营销推送
	 * @param Request $request
	 * @return mixed|string
	 */
	public function massmailsms(\think\Request $request)
	{
		$param = $request->param();
		$result["tip"] = "根据不同客户的各种状态、销售、语言、国家及注册时长进行选择推送消息。<a href='https://bbs.idcsmart.com/forum.php?mod=viewthread&tid=67&extra=page%3D1%26filter%3Dtypeid%26typeid%3D7' target='_blank'>帮助文档</a>";
		$result["page"] = $param["page"] ? $param["page"] : 1;
		$sendMessageBatch = controller("sendMessageBatch");
		$searchParams = $sendMessageBatch->getSearchParams();
		$result["api_type"] = $searchParams["data"]["api_type"];
		$result["client_status"] = $searchParams["data"]["client_status"];
		$result["clients"] = $searchParams["data"]["clients"];
		$result["country"] = $searchParams["data"]["country"];
		$result["domainstatus"] = $searchParams["data"]["domainstatus"];
		$result["language"] = $searchParams["data"]["language"];
		$result["products"] = $searchParams["data"]["products"];
		$result["send_method"] = $searchParams["data"]["send_method"];
		$commons = controller("common");
		$common = $commons->common();
		$result["data"] = $common["data"];
		return $this->display($result);
	}
	/**
	 * 客户分组与折扣
	 * @param Request $request
	 * @return mixed|string
	 */
	public function configclientgroups(\think\Request $request)
	{
		$param = $request->param();
		$result["seleTab"] = $param["seleTab"] ? $param["seleTab"] : "client";
		$result["navTabs"] = [["label" => "客户分组", "name" => "client"], ["label" => "商品分组", "name" => "goods"], ["label" => "折扣设置", "name" => "discount"]];
		$result["tip"] = "系统内所有客户信息，点击姓名可查看更多详细信息并对客户进行管理。<a href='https://www.idcsmart.com/wiki_list/400.html' target='_blank'>帮助文档</a>";
		if ($result["seleTab"] == "client") {
			$result["titleList"] = ["客户组名称", "组颜色", "操作"];
			$result["btnText"] = "添加客服分组";
			$result["modalTitle"] = $result["btnText"];
			$ClientGroup = controller("ClientGroup");
			$res = $ClientGroup->index();
			$result["data"] = $res["data"];
			$result["editData"] = json_encode($res["data"]);
		} elseif ($result["seleTab"] == "goods") {
			$result["titleList"] = ["分组名", "操作"];
			$result["btnText"] = "添加分组";
			$result["modalTitle"] = $result["btnText"];
			$result["page"] = $param["page"] ? $param["page"] : 1;
			$product = controller("product");
			$res = $product->groupList();
			$result["pageSize"] = $this->ajaxPages($res["list"], 10, $result["page"], $res["total"]);
			$result["data"] = $res["list"];
			$result["editData"] = json_encode($res["list"]);
		} elseif ($result["seleTab"] == "discount") {
		}
		return $this->display($result);
	}
	/**
	 * 实名设置
	 * @param Request $request
	 * @return mixed|string
	 */
	public function configauthentication(\think\Request $request)
	{
		$param = $request->param();
		$result["tip"] = "系统内所有客户信息，点击姓名可查看更多详细信息并对客户进行管理。<a href='https://bbs.idcsmart.com/forum.php?mod=viewthread&tid=67&extra=page%3D1%26filter%3Dtypeid%26typeid%3D7' target='_blank'>帮助文档</a>";
		$result["seleTab"] = $param["seleTab"] ? $param["seleTab"] : "first";
		$result["navTabs"] = [["label" => "设置", "name" => "first"], ["label" => "接口设置", "name" => "second"]];
		$result["page"] = $param["page"] ? $param["page"] : 1;
		$config_certifi = controller("config_certifi");
		$detail = $config_certifi->detail();
		$detail["data"]["certifi_select"] = explode(",", $detail["data"]["certifi_select"]);
		$detail["data"]["edit_certifi_select"] = json_encode($detail["data"]["certifi_select"]);
		$result["data"] = $detail["data"];
		$alipay_three_type = $config_certifi->alipay_three_type();
		$result["alipay_three_type"] = $alipay_three_type["data"];
		$types = $config_certifi->types();
		$result["types"] = $types["data"];
		$type = $config_certifi->type();
		$result["type"] = $type["data"];
		$alipay_biz_code = $config_certifi->alipay_biz_code();
		$result["alipay_biz_code"] = $alipay_biz_code["data"];
		return $this->display($result);
	}
	/**
	 * 自定义客户字段
	 * @param Request $request
	 * @return mixed|string
	 */
	public function configclientscustomfields(\think\Request $request)
	{
		$param = $request->param();
		$result["tip"] = "此处可以设置自定义字段，并在客户个人资料中显示。";
		$set = controller("set");
		$res = $set->getCustomFields();
		$result["type_list"] = $res["data"]["type_list"];
		$result["customfields"] = $res["data"]["customfields"];
		return $this->display($result);
	}
	/**
	 * 推介设置
	 * @param Request $request
	 * @return mixed|string
	 */
	public function configaffiliates(\think\Request $request)
	{
		$param = $request->param();
		$result["tip"] = "推荐提成相关设置，用户激活推介计划后，获取推荐链接，访问链接会生成cookie信息。注册时cookie有效则绑定推荐关系，推荐人将会获得佣金奖励。<a href='https://www.idcsmart.com/wiki_list/359.html'>查看帮助文档</a>";
		$config_general = controller("config_general");
		$affiliate = $config_general->getAffiliate();
		$result["data"] = $affiliate["data"]["config_value"];
		return $this->display($result);
	}
	/**
	 * 客户等级
	 * @param Request $request
	 * @return mixed|string
	 */
	public function configclientslevel(\think\Request $request)
	{
		$param = $request->param();
		$result["titleList"] = ["客户等级", "收入", "购买商品数量", "累计登录次数", "最近登录次数", "续费次数", "最近续费次数", "操作"];
		$result["btnText"] = "添加客服等级";
		$result["modalTitle"] = $result["btnText"];
		$result["page"] = $param["page"] ? $param["page"] : 1;
		$UserLevel = controller("UserLevel");
		$res = $UserLevel->getList();
		$result["pageSize"] = $this->ajaxPages($res["data"]["list"], 10, $result["page"], $res["data"]["total"]);
		$result["data"] = $res["data"]["list"];
		$result["editData"] = json_encode($res["data"]["list"]);
		$result["total"] = $res["data"]["total"];
		return $this->display($result);
	}
	/**
	 * 客户摘要
	 * @param Request $request
	 * @return mixed|string
	 */
	public function clientssummary(\think\Request $request)
	{
		$param = $request->param();
		if (!$param["uid"]) {
			$result["errorMsg"] = "缺少必须参数";
			return $this->display($result);
		}
		$request->client_id = $param["uid"];
		$UserManage = controller("UserManage");
		$res = $UserManage->profile();
		return $this->display($res);
	}
	/**
	 * 个人资料
	 * @param Request $request
	 * @return mixed|string
	 */
	public function clientsprofile(\think\Request $request)
	{
		$param = $request->param();
		if (!$param["uid"]) {
			$result["errorMsg"] = "缺少必须参数";
			return $this->display($result);
		}
		$request->client_id = $param["uid"];
		$common = controller("common");
		$getwaysRes = $common->getGetways();
		$result["getways"] = $getwaysRes["gateway"];
		$clientGroupsRes = $common->getClientGroups();
		$result["client_groups"] = $clientGroupsRes["client_groups"];
		$smsCountryRes = $common->getSmsCountry();
		$result["sms_country"] = $smsCountryRes["sms_country"];
		$UserManage = controller("UserManage");
		$res = $UserManage->profile();
		$custom = $res["custom"];
		$custom_value = $res["custom_value"];
		foreach ($custom as &$item) {
			foreach ($custom_value as $val) {
				if ($val["id"] == $item["id"]) {
					$item["value"] = $val["value"];
					$item["cid"] = $val["id"];
				}
			}
			if ($item["fieldtype"] == "dropdown") {
				$item["fieldoptions"] = explode(",", $item["fieldoptions"]);
			}
		}
		$res["custom"] = $custom;
		return $this->display($res);
	}
	/**
	 * 产品与服务
	 * @param Request $request
	 * @return mixed|string
	 */
	public function clientsservices(\think\Request $request)
	{
		$param = $request->param();
		if (!$param["uid"]) {
			$result["errorMsg"] = "缺少必须参数";
			return $this->display($result);
		}
		$request->currency = $param["currency"] ? $param["currency"] : 1;
		$request->limit = $param["limit"] ? $param["limit"] : 10;
		$result["titleList"] = ["id", "产品/服务", "IP", "金额", "产品类型", "付款周期", "订购时间", "到期时间", "状态", "操作"];
		$result["page"] = $param["page"] ? $param["page"] : 1;
		$result["total"] = 0;
		$UserManage = controller("UserManage");
		$res = $UserManage->hostByUid();
		$result["pageSize"] = $this->ajaxPages($res["hosts"], 10, $result["page"], $result["total"]);
		foreach ($res["hosts"] as &$item) {
			$item["regdate"] = date("Y-m-d H:i:s", $item["regdate"]);
			$item["nextduedate"] = date("Y-m-d H:i:s", $item["nextduedate"]);
		}
		$result["data"] = $res["hosts"];
		return $this->display($result);
	}
	/**
	 * 账单
	 * @param Request $request
	 * @return mixed|string
	 */
	public function clientsinvoices(\think\Request $request)
	{
		$param = $request->param();
		if (!$param["uid"]) {
			$result["errorMsg"] = "缺少必须参数";
			return $this->display($result);
		}
		$result["uid"] = $param["uid"];
		$invoice = controller("invoice");
		$searchPage = $invoice->searchPage();
		$gateway = [["label" => "", "value" => ""]];
		foreach ($searchPage["gateway"] as $key => $cc) {
			$gateway[$key + 1]["label"] = $cc["title"];
			$gateway[$key + 1]["value"] = $cc["name"];
		}
		$status = [["label" => "", "value" => ""]];
		foreach ($searchPage["invoice_payment_status"] as $key => $cc) {
			$status[$key + 1]["label"] = $cc["name"];
			$status[$key + 1]["value"] = $key;
			$status[$key + 1]["color"] = $cc["color"];
		}
		$result["titleList"] = ["账单ID", "账单生成日", "账单预期日", "账单支付日", "总计", "支付方式", "状态", "账单类型", "操作"];
		$result["btnText"] = "添加账单";
		$result["seachList"] = [["type" => "date", "label" => "账单生成日", "name" => "create_time", "content" => ""], ["type" => "date", "label" => "账单逾期日", "name" => "due_time", "content" => ""], ["type" => "select", "label" => "付款方式", "name" => "payment", "content" => "", "list" => $gateway], ["type" => "date", "label" => "账单支付日", "name" => "paid_time", "content" => ""], ["type" => "select", "label" => "付款状态", "name" => "status", "content" => "", "list" => $status], ["type" => "text", "label" => "总计从", "name" => "subtotal_small", "content" => ""], ["type" => "text", "label" => "至", "name" => "subtotal_big", "content" => ""]];
		$result["modalTitle"] = $result["btnText"];
		$result["page"] = $param["page"] ? $param["page"] : 1;
		$result["total"] = 0;
		$UserManage = controller("UserManage");
		$res = $UserManage->userInvoice();
		$result["pageSize"] = $this->ajaxPages($res["invocies"], 10, $result["page"], $result["total"]);
		foreach ($res["invocies"] as &$item) {
			$item["create_time"] = date("Y-m-d H:i:s", $item["create_time"]);
			$item["due_time"] = date("Y-m-d H:i:s", $item["due_time"]);
		}
		$result["data"] = $res["invocies"];
		return $this->display($result);
	}
	/**
	 * 交易记录
	 * @param Request $request
	 * @return mixed|string
	 */
	public function clientstransactions(\think\Request $request)
	{
		$param = $request->param();
		if (!$param["uid"]) {
			$result["errorMsg"] = "缺少必须参数";
			return $this->display($result);
		}
		$result["titleList"] = ["ID", "交易记录号", "交易记录生成日", "付款方式", "描述", "收入", "手续费", "支出", "操作"];
		$result["btnText"] = "添加交易记录";
		$result["page"] = $param["page"] ? $param["page"] : 1;
		$account = controller("account");
		$res = $account->index();
		$result["pageSize"] = $this->ajaxPages($res["data"], 10, $result["page"], $res["page"]["total"]);
		$result["amount_in"] = $res["count"]["CNY"]["amount_in"];
		$result["amount_out"] = $res["count"]["CNY"]["amount_out"];
		$result["fees"] = $res["count"]["CNY"]["fees"];
		$result["surplus"] = $res["count"]["CNY"]["surplus"];
		$data = [];
		foreach ($res["data"] as $key => &$item) {
			$item["pay_time"] = date("Y-m-d H:i:s", $item["pay_time"]);
			$data[$key] = $item;
		}
		$result["data"] = $data;
		$result["editData"] = json_encode($data);
		$result["total"] = $res["page"]["total"];
		$create = $account->create();
		$result["currency"] = $create["data"]["currency"];
		$result["gateways"] = $create["data"]["gateways"];
		return $this->display($result);
	}
	/**
	 * 信用管理
	 * @param Request $request
	 * @return mixed|string
	 */
	public function clientscredit(\think\Request $request)
	{
		$param = $request->param();
		if (!$param["uid"]) {
			$result["errorMsg"] = "缺少必须参数";
			return $this->display($result);
		}
		$request->client_id = $param["uid"];
		$UserManage = controller("UserManage");
		$res = $UserManage->profile();
		return $this->display($res);
	}
	/**
	 * 工单
	 * @param Request $request
	 * @return mixed|string
	 */
	public function clientssupporttickets(\think\Request $request)
	{
		$param = $request->param();
		if (!$param["uid"]) {
			$result["errorMsg"] = "缺少必须参数";
			return $this->display($result);
		}
		$result["titleList"] = ["ID", "主题", "状态", "部门", "提交时间", "上次回复"];
		$result["btnText"] = "新建工单";
		$result["page"] = $param["page"] ? $param["page"] : 1;
		$ticket = controller("ticket");
		$res = $ticket->getClientTicketPage();
		$result["pageSize"] = $this->ajaxPages($res["data"]["ticket_data"], 10, $result["page"], $res["data"]["meta"]["total"]);
		foreach ($res["data"]["ticket_data"] as &$item) {
			$item["create_time"] = date("Y-m-d H:i:s", $item["create_time"]);
		}
		$result["opened_last_month"] = $res["data"]["opened_last_month"];
		$result["opened_last_year"] = $res["data"]["opened_last_year"];
		$result["opened_this_month"] = $res["data"]["opened_this_month"];
		$result["opened_this_year"] = $res["data"]["opened_this_year"];
		$result["data"] = $res["data"]["ticket_data"];
		$result["total"] = $res["data"]["meta"]["total"];
		return $this->display($result);
	}
	/**
	 * 日志
	 * @param Request $request
	 * @return mixed|string
	 */
	public function clientslog(\think\Request $request)
	{
		$param = $request->param();
		if (!$param["uid"]) {
			$result["errorMsg"] = "缺少必须参数";
			return $this->display($result);
		}
		$result["titleList"] = ["ID", "时间", "描述", "用户名", "IP地址"];
		$result["page"] = $param["page"] ? $param["page"] : 1;
		$UserManage = controller("UserManage");
		$res = $UserManage->logRecord();
		$result["pageSize"] = $this->ajaxPages($res["log_list"], 10, $result["page"], $res["data"]["count"]);
		$result["data"] = $res["log_list"];
		$result["total"] = $res["data"]["count"];
		return $this->display($result);
	}
	/**
	 * 通知日志
	 * @param Request $request
	 * @return mixed|string
	 */
	public function clientsnoticelog(\think\Request $request)
	{
		$param = $request->param();
		if (!$param["uid"]) {
			$result["errorMsg"] = "缺少必须参数";
			return $this->display($result);
		}
		$result["navTabs"] = [["label" => "短信日志", "name" => "SMS"], ["label" => "邮件日志", "name" => "email"], ["label" => "站内信日志", "name" => "station"]];
		$result["type"] = $param["type"] ? $param["type"] : "SMS";
		$result["page"] = $param["page"] ? $param["page"] : 1;
		$logRecord = controller("logRecord");
		if ($result["type"] == "SMS") {
			$result["titleList"] = ["ID", "时间", "短信内容", "用户名", "电话", "是否成功", "失败原因", "IP"];
			$res = $logRecord->getSmsLog();
			$result["pageSize"] = $this->ajaxPages($res["data"], 10, $result["page"], $res["count"]);
			foreach ($res["data"] as $key => &$item) {
				$item["create_time"] = date("Y-m-d H:i:s", $item["create_time"]);
			}
			$result["data"] = $res["data"];
			$result["total"] = $res["count"];
		} elseif ($result["type"] == "email") {
			$result["titleList"] = ["ID", "时间", "主题", "收件人", "是否成功", "失败原因", "IP"];
			$res = $logRecord->getEmailLog();
			$result["pageSize"] = $this->ajaxPages($res["data"], 10, $result["page"], $res["count"]);
			foreach ($res["data"] as $key => &$item) {
				$item["create_time"] = date("Y-m-d H:i:s", $item["create_time"]);
			}
			$result["data"] = $res["data"];
			$result["total"] = $res["count"];
		} else {
			$result["titleList"] = ["发送时间", "标题", "用户", "状态"];
			$res = $logRecord->getSystemMessageLog($request);
			$result["pageSize"] = $this->ajaxPages($res["data"]["list"], 10, $result["page"], $res["data"]["count"]);
			$result["data"] = $res["data"]["list"];
			$result["total"] = $res["data"]["count"];
		}
		return $this->display($result);
	}
	/**
	 * 附件
	 * @param Request $request
	 * @return mixed|string
	 */
	public function clientsattach(\think\Request $request)
	{
		$param = $request->param();
		if (!$param["uid"]) {
			$result["errorMsg"] = "缺少必须参数";
			return $this->display($result);
		}
		$result["titleList"] = ["附件名称", "上传时间", "上传人", "文件名", "备注", "操作"];
		$result["btnText"] = "上传";
		$result["modelTitle"] = $result["btnText"];
		$result["page"] = $param["page"] ? $param["page"] : 1;
		$Downloads = controller("Downloads");
		$res = $Downloads->getUserDownList($request);
		$result["pageSize"] = $this->ajaxPages($res["data"], 10, $result["page"], $res["total"]);
		foreach ($res["data"] as &$item) {
			$item["create_time"] = date("Y-m-d H:i:s", $item["create_time"]);
		}
		$result["editData"] = json_encode($res["data"]);
		$result["data"] = $res["data"];
		$result["total"] = $res["total"];
		return $this->display($result);
	}
	/**
	 * 推介计划
	 * @param Request $request
	 * @return mixed|string
	 */
	public function clientsaffiliate(\think\Request $request)
	{
		$param = $request->param();
		if (!$param["uid"]) {
			$result["errorMsg"] = "缺少必须参数";
			return $this->display($result);
		}
		$request->client_id = $param["uid"];
		$UserManage = controller("UserManage");
		$res = $UserManage->profile();
		return $this->display($res);
	}
	/**
	 * 跟进状态
	 * @param Request $request
	 * @return mixed|string
	 */
	public function clientscrm(\think\Request $request)
	{
		$param = $request->param();
		if (!$param["uid"]) {
			$result["errorMsg"] = "缺少必须参数";
			return $this->display($result);
		}
		$request->client_id = $param["uid"];
		$UserManage = controller("UserManage");
		$res = $UserManage->profile();
		return $this->display($res);
	}
	/**
	 * 产品详情
	 * @param Request $request
	 * @return mixed|string
	 */
	public function clientsviewservices(\think\Request $request)
	{
		$param = $request->param();
		if (!$param["uid"]) {
			$result["errorMsg"] = "缺少必须参数";
			return $this->display($result);
		}
		$request->client_id = $param["uid"];
		$common = controller("common");
		$client_groups = $common->getProductList();
		$promo_code = $common->getPromoCode();
		$gateway = $common->getGetways();
		$host_list = $common->getHostList();
		$ClientsServices = controller("ClientsServices");
		$res = $ClientsServices->index($request);
		return $this->display($res);
	}
	/**
	 * 工单详情
	 * @param Request $request
	 * @return mixed|string
	 */
	public function clientssupportticketdetail(\think\Request $request)
	{
		$param = $request->param();
		if (!$param["uid"]) {
			$result["errorMsg"] = "缺少必须参数";
			return $this->display($result);
		}
		$request->client_id = $param["uid"];
		$UserManage = controller("UserManage");
		$res = $UserManage->profile();
		return $this->display($res);
	}
	/**
	 * 添加账单
	 * @param Request $request
	 * @return mixed|string
	 */
	public function viewinvoices(\think\Request $request)
	{
		$param = $request->param();
		if (!$param["uid"]) {
			$result["errorMsg"] = "缺少必须参数";
			return $this->display($result);
		}
		$request->client_id = $param["uid"];
		$UserManage = controller("UserManage");
		$res = $UserManage->profile();
		return $this->display($res);
	}
	/**
	 * 添加订单
	 * @param Request $request
	 * @return mixed|string
	 */
	public function ordersadd(\think\Request $request)
	{
		$param = $request->param();
		if (!$param["uid"]) {
			$result["errorMsg"] = "缺少必须参数";
			return $this->display($result);
		}
		$result["uid"] = $param["uid"];
		$common = controller("common");
		$getwaysRes = $common->getGetways();
		$result["getways"] = $getwaysRes["gateway"];
		$promoCode = $common->getPromoCode();
		$result["promo_code"] = $getwaysRes["promo_code"];
		$getProductList = $common->getProductList();
		$result["client_groups"] = $getProductList["client_groups"];
		$order = controller("order");
		$clients = $order->getClients();
		$result["clients"] = $clients["data"];
		$UserManage = controller("UserManage");
		$res = $UserManage->profile();
		return $this->display($res);
	}
	/**
	 * 新建工单
	 * @param Request $request
	 * @return mixed|string
	 */
	public function createsupporttickets(\think\Request $request)
	{
		$param = $request->param();
		if (!$param["uid"]) {
			$result["errorMsg"] = "缺少必须参数";
			return $this->display($result);
		}
		$request->client_id = $param["uid"];
		$ticket = controller("ticket");
		$res = $ticket->createPage();
		$result["department"] = $res["data"]["department"];
		$result["user"] = $res["data"]["user"];
		$order = controller("order");
		$clients = $order->getClients();
		$result["clients"] = $clients["data"];
		return $this->display($result);
	}
	/**
	 * 邮件详情
	 * @param Request $request
	 * @return mixed|string
	 */
	public function clientsviewemail(\think\Request $request)
	{
		$param = $request->param();
		if (!$param["uid"]) {
			$result["errorMsg"] = "缺少必须参数";
			return $this->display($result);
		}
		$request->client_id = $param["uid"];
		$UserManage = controller("UserManage");
		$res = $UserManage->profile();
		return $this->display($res);
	}
}