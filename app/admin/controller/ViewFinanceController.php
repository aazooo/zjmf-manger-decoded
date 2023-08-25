<?php

namespace app\admin\controller;

class ViewFinanceController extends ViewAdminBaseController
{
	public function display($data)
	{
		$arr = preg_split("/\\//", $_SERVER["REDIRECT_URL"]);
		return $this->view($arr[2], $data);
	}
	/**
	 * 交易流水
	 */
	public function transactions(\think\Request $request)
	{
		$account = controller("account");
		$res = $account->searchPage();
		$salelist = [["label" => "", "value" => ""]];
		foreach ($res["salelist"] as $key => $cc) {
			$salelist[$key + 1]["label"] = $cc["label"];
			$salelist[$key + 1]["value"] = $cc["value"];
		}
		$gateway = [["label" => "", "value" => ""]];
		foreach ($res["gateway"] as $key => $cc) {
			$gateway[$key + 1]["label"] = $cc["title"];
			$gateway[$key + 1]["value"] = $cc["name"];
		}
		$res = $account->create();
		$currencyList = [["label" => "", "value" => ""]];
		foreach ($res["currency"] as $key => $cc) {
			$currencyList[$key + 1]["label"] = $cc["code"];
			$currencyList[$key + 1]["value"] = $cc["id"];
		}
		$order = controller("order");
		$res = $order->getclients();
		$clients = $res["data"];
		$userlist = [["label" => "", "value" => "", "uid" => ""]];
		foreach ($clients as $key => $cc) {
			$userlist[$key + 1]["label"] = $cc["username"];
			$userlist[$key + 1]["value"] = $cc["username"];
			$userlist[$key + 1]["uid"] = $cc["id"];
		}
		$result["navTabs"] = [["label" => "全部", "name" => ""], ["label" => "充值", "name" => "recharge"], ["label" => "产品", "name" => "host"], ["label" => "续费", "name" => "renew"]];
		$result["titleList"] = ["客户名", "时间", "付款方式", "描述", "金额", "销售", "流水号", "类型", "操作"];
		$paymentsList = [["label" => "", "value" => ""], ["label" => "付款已收到", "value" => "amount_in"], ["label" => "付款已发出", "value" => "amount_out"]];
		$result["seachList"] = [["type" => "select", "label" => "显示", "name" => "payments", "content" => "", "list" => $paymentsList], ["type" => "text", "label" => "描述", "name" => "description", "content" => ""], ["type" => "text", "label" => "金额", "name" => "amount", "content" => ""], ["type" => "text", "label" => "付款流水号", "name" => "turnover", "content" => ""], ["type" => "date", "label" => "开始时间", "name" => "startTime", "content" => ""], ["type" => "date", "label" => "结束时间", "name" => "endTime", "content" => ""], ["type" => "select", "label" => "支付方式", "name" => "payment", "content" => "", "list" => $gatewa], ["type" => "select", "label" => "销售", "name" => "sale_id", "content" => "", "list" => $salelist]];
		$result["tip"] = "详细统计收入、支出记录及总额。";
		$result["btnText"] = "添加交易流水";
		$result["modalTitle"] = $result["btnText"];
		$result["modalList"] = [["type" => "select", "label" => "关联用户", "name" => "username", "content" => "", "tip" => "请填写关联用户", "required" => true, "list" => $userlist], ["type" => "date", "label" => "时间", "name" => "pay_time", "content" => "", "tip" => "请选择时间", "required" => true], ["type" => "text", "label" => "描述", "name" => "description", "content" => "", "tip" => "请填写描述", "required" => true], ["type" => "text", "label" => "付款流水号", "name" => "turnover", "content" => ""], ["type" => "select", "label" => "账单编号", "name" => "billNumber", "content" => ""], ["type" => "select", "label" => "支付方式", "name" => "payment", "content" => "", "list" => $gateway], ["type" => "text", "label" => "收入", "name" => "amount_in", "content" => ""], ["type" => "text", "label" => "支出", "name" => "amount_out", "content" => ""], ["type" => "select", "label" => "货币类型", "name" => "currency", "content" => "", "list" => $currencyList], ["type" => "checkbox", "label" => "预付款", "name" => "prepayments", "content" => "", "list" => [["label" => "添加到用户余额", "value" => "1"]]]];
		$param = $request->param();
		$result["type"] = $param["type"];
		$result["page"] = $param["page"] ? $param["page"] : 1;
		if ($param) {
			foreach ($result["seachList"] as &$sl) {
				foreach ($param as $kk => $gg) {
					if ($sl["name"] == $kk) {
						$sl["content"] = $gg;
					}
				}
			}
		}
		$account = controller("account");
		$res = $account->index();
		$result["pageSize"] = $this->ajaxPages($res["data"], 10, $result["page"], $res["page"]["total"]);
		$result["total"] = $res["page"]["total"];
		$data = $res["data"];
		$count = 0;
		foreach ($data as $key => &$item) {
			$count += $item["amount_in"];
			$item["payTime"] = date("Y-m-d H:i:s", $item["pay_time"]);
			$data[$key] = $item;
		}
		$result["count"] = $count;
		$result["data"] = $data;
		return $this->display($result);
	}
	/**
	 * 账单管理
	 */
	public function invoices(\think\Request $request)
	{
		$invoice = controller("invoice");
		$res = $invoice->searchPage();
		$type = [["label" => "", "value" => ""]];
		foreach ($res["type_arr"] as $key => $cc) {
			$type[$key + 1]["label"] = $cc;
			$type[$key + 1]["value"] = $key;
		}
		$salelist = [["label" => "", "value" => ""]];
		foreach ($res["salelist"] as $key => $cc) {
			$type[$key + 1]["label"] = $cc["label"];
			$type[$key + 1]["value"] = $cc["value"];
		}
		$gateway = [["label" => "", "value" => ""]];
		foreach ($res["gateway"] as $key => $cc) {
			$type[$key + 1]["label"] = $cc["title"];
			$type[$key + 1]["value"] = $cc["name"];
		}
		$order = controller("order");
		$res = $order->getclients();
		$clients = $res["data"];
		$list = [["label" => "", "value" => "", "uid" => ""]];
		foreach ($clients as $key => $cc) {
			$list[$key + 1]["label"] = $cc["username"];
			$list[$key + 1]["value"] = $cc["username"];
			$list[$key + 1]["uid"] = $cc["id"];
		}
		$result["navTabs"] = [["label" => "全部", "name" => ""], ["label" => "充值", "name" => "recharge"], ["label" => "产品", "name" => "host"], ["label" => "续费", "name" => "renew"]];
		$result["titleList"] = ["账单", "客服名", "账单生成日", "账单预期日", "总计", "付款方式", "销售", "状态", "账单类型", "操作"];
		$result["seachList"] = [["type" => "select", "label" => "客户", "name" => "username", "content" => "", "list" => $list], ["type" => "towDate", "label" => "账单生成日", "name" => "createTime", "content" => ""], ["type" => "towDate", "label" => "账单逾期日", "name" => "dueTime", "content" => ""], ["type" => "select", "label" => "支付方式", "name" => "payment", "content" => "", "list" => $gateway], ["type" => "date", "label" => "账单支付日", "name" => "paidTime", "content" => ""], ["type" => "select", "label" => "账单类型", "name" => "type", "content" => "", "list" => $type], ["type" => "select", "label" => "销售", "name" => "sale_id", "content" => "", "list" => $salelist]];
		$result["tip"] = "详细统计收入、支出记录及总额。";
		$result["btnText"] = "添加交易流水";
		$param = $request->param();
		$result["type"] = $_GET["type"];
		$result["page"] = $param["page"] ? $param["page"] : 1;
		if ($param) {
			foreach ($result["seachList"] as &$sl) {
				foreach ($param as $kk => $gg) {
					if ($sl["name"] == $kk) {
						$sl["content"] = $gg;
					}
				}
			}
		}
		$invoice = controller("invoice");
		$res = $invoice->index();
		$result["pageSize"] = $this->ajaxPages($res["data"], 10, $result["page"], $res["page"]["total"]);
		$result["total"] = $res["page"]["total"];
		$data = $res["data"];
		foreach ($data as $key => &$item) {
			$item["createTime"] = date("Y-m-d H:i:s", $item["create_time"]);
			$item["dueTime"] = date("Y-m-d H:i:s", $item["due_time"]);
			$data[$key] = $item;
		}
		$result["count"] = $res["price"];
		$result["totalprice"] = $res["totalprice"];
		$result["data"] = $data;
		return $this->display($result);
	}
	/**
	 * 提现审核
	 */
	public function withdrawdeposits(\think\Request $request)
	{
		$result["titleList"] = ["ID", "姓名(公司名)", "金额", "类型", "操作人", "状态", "拒绝原因", "时间", "操作"];
		$result["seachList"] = [["type" => "text", "label" => "用户名", "name" => "user_nickname", "content" => ""], ["type" => "select", "label" => "提现方式", "name" => "status", "content" => "", "list" => [["label" => "", "value" => ""], ["label" => "待审核", "value" => "1"], ["label" => "通过", "value" => "2"], ["label" => "拒绝", "value" => "3"]]]];
		$page = 1;
		$limit = 10;
		$order = "id";
		$sort = "desc";
		if ($_GET) {
			foreach ($result["seachList"] as &$sl) {
				foreach ($_GET as $kk => $gg) {
					if ($sl["name"] == $kk) {
						$sl["content"] = $gg;
					}
				}
			}
			$page = $_GET["page"] ? $_GET["page"] : $page;
			$limit = $_GET["limit"] ? $_GET["limit"] : $limit;
			$order = $_GET["order"] ? $_GET["order"] : $order;
			$sort = $_GET["sort"] ? $_GET["sort"] : $sort;
			$request->user_nickname = $_GET["user_nickname"];
			$request->status = $_GET["status"];
		}
		$request->page = $page;
		$request->limit = $limit;
		$request->order = $order;
		$request->sort = $sort;
		$affiliate = controller("affiliate");
		$res = $affiliate->affiwithdrawrecord($request);
		$result["page"] = $page;
		$result["total"] = $res["total"];
		$result["size"] = \intval($result["total"] / 10) + ($result["total"] % 10 == 0 ? 0 : 1);
		for ($i = 1; $i <= $result["size"]; $i++) {
			$result["pageSize"][$i] = $i;
		}
		$data = $res["data"];
		foreach ($data as $key => &$item) {
			$item["createTime"] = date("Y-m-d H:i:s", $item["create_time"]);
			$data[$key] = $item;
		}
		$result["data"] = $data;
		return $this->display($result);
	}
	/**
	 * 发票列表
	 */
	public function receipt(\think\Request $request)
	{
		$result["navTabs"] = [["label" => "全部", "name" => ""], ["label" => "待审核", "name" => "Pending"], ["label" => "已驳回", "name" => "Reject"], ["label" => "已发出", "name" => "Send"], ["label" => "待支付", "name" => "Unpaid"]];
		$result["titleList"] = ["发票ID", "用户名", "发票抬头", "开具类型", "发票金额", "邮寄地址", "申请时间", "审核时间", "状态", "操作"];
		$page = 1;
		$limit = 10;
		$order = "id";
		$sort = "desc";
		$result["type"] = $_GET["type"];
		if ($_GET) {
			foreach ($result["seachList"] as &$sl) {
				foreach ($_GET as $kk => $gg) {
					if ($sl["name"] == $kk) {
						$sl["content"] = $gg;
					}
				}
			}
			$page = $_GET["page"] ? $_GET["page"] : $page;
			$limit = $_GET["limit"] ? $_GET["limit"] : $limit;
			$order = $_GET["order"] ? $_GET["order"] : $order;
			$sort = $_GET["sort"] ? $_GET["sort"] : $sort;
		}
		$request->page = $page;
		$request->limit = $limit;
		$request->order = $order;
		$request->sort = $sort;
		$request->status = $_GET["type"] ? $_GET["type"] : 0;
		$voucher = controller("voucher");
		$res = $voucher->getVoucherList();
		$result["page"] = $page;
		$result["total"] = $res["data"]["total"];
		$result["size"] = \intval($result["total"] / 10) + ($result["total"] % 10 == 0 ? 0 : 1);
		for ($i = 1; $i <= $result["size"]; $i++) {
			$result["pageSize"][$i] = $i;
		}
		$data = $res["data"]["voucher"];
		foreach ($data as $key => &$item) {
			$item["createTime"] = date("Y-m-d H:i:s", $item["create_time"]);
			$item["checkTime"] = $item["check_time"] ? date("Y-m-d H:i:s", $item["check_time"]) : $item["check_time"];
			$data[$key] = $item;
		}
		$result["data"] = $data;
		return $this->display($result);
	}
	/**
	 * 支付接口
	 */
	public function configgateways(\think\Request $request)
	{
		$result["titleList"] = ["ID", "插件名称", "标识", "描述", "作者", "版本", "状态", "操作"];
		$result["tip"] = "选择支付网关点击安装，填写接口相关配置信息后，用户在前台可选择支付方式进行结算付款。<a href='#'>查看帮助文档</a>";
		$request->moduleName = "gateways";
		$plugin = controller("Plugin");
		$res = $plugin->plIndex($request);
		$result["data"] = $res["data"];
		return $this->display($result);
	}
	/**
	 * 优惠码
	 */
	public function configpromotions(\think\Request $request)
	{
		$result["navTabs"] = [["label" => "激活的优惠码", "name" => "active"], ["label" => "过期的优惠码", "name" => "expired"], ["label" => "所有优惠码", "name" => "all"]];
		$result["titleList"] = ["ID", "优惠码", "类型", "价值", "循环优惠", "已使用次数/最大使用次数", "开始时间", "失效时间", "操作"];
		$result["tip"] = "添加优惠码可对不同商品进行对应折扣，并可设置优惠码到期时间及使用次数，便于管理。<a href='#'>帮助文档</a>";
		$result["btnText"] = "添加优惠码";
		$result["modalTitle"] = $result["btnText"];
		$result["page"] = $_GET["page"] ? $_GET["page"] : 1;
		$request->type = $result["type"] = $_GET["type"] ? $_GET["type"] : "active";
		$promoCode = controller("promoCode");
		$res = $promoCode->getList();
		$result["total"] = count($res["data"]);
		$result["size"] = \intval($result["total"] / 10) + ($result["total"] % 10 == 0 ? 0 : 1);
		for ($i = 1; $i <= $result["size"]; $i++) {
			$result["pageSize"][$i] = $i;
		}
		$data = $res["data"];
		$num = 0;
		foreach ($data as $key => &$item) {
			$item["startTime"] = $item["start_time"] == "-" ? $item["start_time"] : date("Y-m-d H:i:s", $item["start_time"]);
			$item["expirationTime"] = $item["expiration_time"] == "-" ? $item["expiration_time"] : date("Y-m-d H:i:s", $item["expiration_time"]);
			if (($result["page"] - 1) * 10 <= $key && $key < $result["page"] * 10) {
				$result["data"][$num] = $item;
				$num++;
			}
		}
		return $this->display($result);
	}
	/**
	 * 添加优惠码
	 */
	public function configpromotionsadd(\think\Request $request)
	{
		$result["tip"] = "在这里查看所有订单，生成新订单。<a href='#'>帮助文档</a>";
		$result["btnText"] = "添加新订单";
		$result["modalTitle"] = $result["btnText"];
		$result["page"] = $_GET["page"] ? $_GET["page"] : 1;
		return $this->display($result);
	}
	/**
	 * 货币配置
	 */
	public function configcurrencies(\think\Request $request)
	{
		$result["tip"] = "您可以通过不同的货币销售您的产品。客户可以在网站上选择他们合适的货币进行支付购买。";
		$result["titleList"] = ["ID", "货币代码", "前缀(例如：￥)", "后缀", "格式", "汇率", "操作"];
		$currency = controller("currency");
		$res = $currency->currencyList();
		$result["total"] = $res["total"];
		$result["totalPage"] = $res["totalPage"];
		$result["data"] = $res["currencies"];
		return $this->display($result);
	}
	/**
	 * 充值与财务
	 */
	public function configfund(\think\Request $request)
	{
		$result["navTabs"] = [["label" => "充值", "name" => "cz"], ["label" => "财务", "name" => "cw"]];
		$result["modalList"] = [["type" => "checkbox", "label" => "是否启用充值", "name" => "addfunds_enabled", "content" => "", "tip" => "选中择,客户可以在用户中心添加余额至账户中"], ["type" => "text", "label" => "最小金额", "name" => "addfunds_minimum", "content" => "", "tip" => "单笔充值的最小金额"], ["type" => "text", "label" => "最大金额", "name" => "addfunds_maximum", "content" => "", "tip" => "单笔充值的最大金额"], ["type" => "text", "label" => "最高余额", "name" => "addfunds_maximum_balance", "content" => "", "tip" => "输入客户可以添加进余额的最大金额"], ["type" => "checkbox", "label" => "需要已激活的订单", "name" => "addfunds_enabled", "content" => "", "tip" => "允许添加余额之前必须有一个已激活的订单(用于防止欺诈，意味着在允许添加余额之前管理员必须手动审核用户并批准订单)"]];
		$result["seleTab"] = $_GET["seleTab"] ? $_GET["seleTab"] : "cz";
		if ($result["seleTab"] == "cw") {
			$result["modalList"] = [["type" => "checkbox", "label" => "降级退款至余额", "name" => "upgrade_down_product_config", "content" => ""], ["type" => "checkbox", "label" => "起始账单号自定义", "name" => "allow_custom_invoice_id", "content" => ""]];
		}
		return $this->display($result);
	}
	/**
	 * 发票设置
	 */
	public function configreceipt(\think\Request $request)
	{
		$result["navTabs"] = [["label" => "费率设置", "name" => "fl"], ["label" => "快递管理", "name" => "kd"]];
		$result["modalList"] = [["type" => "checkbox", "label" => "发票管理", "name" => "voucher_manager", "content" => "", "tip" => "开启后启用发票管理功能"], ["type" => "text", "label" => "发票费率(%) ", "name" => "rate", "content" => "", "required" => true]];
		$result["seleTab"] = $_GET["seleTab"] ? $_GET["seleTab"] : "fl";
		if ($result["seleTab"] == "kd") {
			$result["modalList"] = [];
			$result["tip"] = "可在此处添加多个快递，用户开具发票时选择邮寄的快递信息";
			$result["titleList"] = ["ID", "快递名称", "快递价格", "操作"];
			$result["btnText"] = "添加快递";
			$result["modalTitle"] = $result["btnText"];
			$page = 1;
			$limit = 10;
			$order = "id";
			$sort = "desc";
			if ($_GET) {
				$page = $_GET["page"] ? $_GET["page"] : $page;
				$limit = $_GET["limit"] ? $_GET["limit"] : $limit;
				$order = $_GET["order"] ? $_GET["order"] : $order;
				$sort = $_GET["sort"] ? $_GET["sort"] : $sort;
				$request->user_nickname = $_GET["user_nickname"];
				$request->status = $_GET["status"];
			}
			$request->page = $page;
			$request->limit = $limit;
			$request->order = $order;
			$request->sort = $sort;
			$voucher = controller("voucher");
			$res = $voucher->getExpressList();
			$result["page"] = $page;
			$result["total"] = $res["data"]["total"];
			$result["size"] = \intval($result["total"] / 10) + ($result["total"] % 10 == 0 ? 0 : 1);
			for ($i = 1; $i <= $result["size"]; $i++) {
				$result["pageSize"][$i] = $i;
			}
			$data = $res["data"]["express"];
			foreach ($data as $key => &$item) {
				$item["createTime"] = date("Y-m-d H:i:s", $item["create_time"]);
				$data[$key] = $item;
			}
			$result["data"] = $data;
		}
		return $this->display($result);
	}
}