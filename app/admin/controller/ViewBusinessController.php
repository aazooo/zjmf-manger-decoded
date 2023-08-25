<?php

namespace app\admin\controller;

class ViewBusinessController extends ViewAdminBaseController
{
	public function display($data)
	{
		$arr = preg_split("/\\//", $_SERVER["REDIRECT_URL"]);
		return $this->view($arr[2], $data);
	}
	/**
	 * 产品订单
	 */
	public function orders(\think\Request $request)
	{
		$UserManage = controller("order");
		$searchPageRes = $UserManage->searchPage();
		$result["seachList"] = [["type" => "text", "label" => "订单ID", "name" => "id", "content" => ""], ["type" => "text", "label" => "客户", "name" => "username", "content" => ""], ["type" => "date", "label" => "开始时间", "name" => "start_time", "content" => ""], ["type" => "date", "label" => "结束时间", "name" => "endTime", "content" => ""], ["type" => "text", "label" => "金额", "name" => "amount", "content" => ""]];
		if ($searchPageRes["status"] == 200) {
			if ($searchPageRes["pay_status"]) {
				$list = [];
				foreach ($searchPageRes["pay_status"] as $key => $item) {
					if ($key === 0) {
						$list[$key]["value"] = $key == "0" ? "ling" : $key;
						$list[$key]["label"] = $item;
					} else {
						$list[$key]["value"] = $key;
						$list[$key]["label"] = $item["name"];
					}
				}
				array_unshift($list, ["value" => "", "label" => ""]);
				$result["seachList"][count($result["seachList"])] = ["type" => "select", "label" => "付款状态", "name" => "pay_status", "content" => "", "list" => $list];
			}
			if ($searchPageRes["list"]) {
				$list = [];
				foreach ($searchPageRes["list"] as $key => $item) {
					$list[$key]["value"] = $item["id"];
					$list[$key]["label"] = $item["user_nickname"];
				}
				array_unshift($list, ["value" => "", "label" => ""]);
				$result["seachList"][count($result["seachList"])] = ["type" => "select", "label" => "销售", "name" => "sale_id", "content" => "", "list" => $list];
			}
			if ($searchPageRes["type"]) {
				$list = [];
				foreach ($searchPageRes["type"] as $key => $item) {
					$list[$key]["value"] = $key;
					$list[$key]["label"] = $item;
				}
				array_unshift($list, ["value" => "", "label" => ""]);
				$result["seachList"][count($result["seachList"])] = ["type" => "select", "label" => "状态", "name" => "status", "content" => "", "list" => $list];
			}
		}
		$result["navTabs"] = [["label" => "全部", "name" => ""], ["label" => "待核验", "name" => "Pending"], ["label" => "已激活", "name" => "Active"], ["label" => "已取消", "name" => "Cancel"]];
		$result["titleList"] = ["ID", "客户名", "产品", "IP", "下单时间", "金额", "付款状态/付款方式", "状态", "提成/销售"];
		$result["type"] = $_GET["type"];
		$request->order = "id";
		$request->sort = "desc";
		$page = 1;
		$limit = 10;
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
			$request->id = $_GET["id"];
			$request->username = $_GET["username"];
			$request->sale_id = $_GET["sale_id"];
			$request->start_time = $_GET["start_time"];
			$request->endTime = $_GET["endTime"];
			$request->start_time = $_GET["startTime"];
			$request->status = $_GET["status"];
			$request->amount = $_GET["amount"];
			$request->pay_status = $_GET["pay_status"] == "ling" ? 0 : $_GET["pay_status"];
		}
		$res = $UserManage->index();
		$result["total"] = $res["count"];
		$pageSize = \intval($res["total"] / 10) + ($res["total"] % 10 == 0 ? 0 : 1);
		for ($i = 1; $i <= $pageSize; $i++) {
			$res["pageSize"][$i - 1] = $i;
		}
		$result["tip"] = "在这里查看所有订单，生成新订单。<a href='https://www.idcsmart.com/wiki_list/396.html' target='_blank'>帮助文档</a>";
		$result["btnText"] = "添加新订单";
		$result["modalTitle"] = $result["btnText"];
		$result["page"] = $_GET["page"] ? $_GET["page"] : 1;
		$result["price_total"] = $res["price_total"];
		$result["price_total_page"] = $res["price_total_page"];
		foreach ($res["list"] as &$item) {
			if ($item["create_time"]) {
				$item["create_time_format"] = date("Y-m-d H:i:s", $item["create_time"]);
			} else {
				$item["create_time_format"] = "-";
			}
		}
		$tichengRes = $UserManage->indexPost();
		if ($tichengRes["status"] == 200 && count($tichengRes["list"])) {
			foreach ($res["list"] as &$item1) {
				foreach ($tichengRes["list"] as &$item2) {
					if ($item1["id"] === $item2["id"]) {
						$item1["loading"] = false;
						$item1["sum"] = $item2["sum"];
						$item1["sum1"] = $item2["sum1"];
						$item1["sum2"] = $item2["sum2"];
						$item1["flag"] = $item2["flag"];
						$item1["ladder"] = $item2["ladder"];
					}
				}
			}
		}
		$result["datalist"] = $res["list"];
		$result["dataJSON"] = json_encode($res["list"]);
		return $this->display($result);
	}
	/**
	 * 流量包订单
	 */
	public function trafficorder(\think\Request $request)
	{
		$result["tip"] = "查看流量包订单。<a href='https://www.idcsmart.com/wiki_list/395.html' target='_blank'>帮助文档</a>";
		$result["page"] = $_GET["page"] ? $_GET["page"] : 1;
		$result["titleList"] = ["ID", "客户名", "产品", "时间", "金额", "付款状态", "支付方式", "操作"];
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
			$orderby = $_GET["orderby"] ? $_GET["orderby"] : $orderby;
			$sort = $_GET["sort"] ? $_GET["sort"] : $sort;
			$request->search = $_GET["search"];
		}
		$request->orderby = "id";
		$DcimManage = controller("dcim");
		$_GET["sort"] = $_GET["sort"] ? $_GET["sort"] : "desc";
		$_GET["orderby"] = $_GET["orderby"] ? $_GET["orderby"] : "id";
		$res = $DcimManage->listBuyRecord();
		$result["total"] = $res["data"]["sum"];
		$pageSize = \intval($res["data"]["sum"] / 10) + ($res["data"]["sum"] % 10 == 0 ? 0 : 1);
		for ($i = 1; $i <= $pageSize; $i++) {
			$res["pageSize"][$i - 1] = $i;
		}
		foreach ($res["data"]["list"] as &$item) {
			if ($item["create_time"]) {
				$item["create_time_format"] = date("Y-m-d H:i:s", $item["create_time"]);
			} else {
				$item["create_time_format"] = "-";
			}
		}
		$result["datalist"] = $res["data"]["list"];
		$result["search"] = $_GET["search"] ? $_GET["search"] : "";
		return $this->display($result);
	}
	/**
	 * 业务列表页
	 */
	public function productlist(\think\Request $request)
	{
		$HostManage = controller("host");
		$result["tip"] = "系统中所有销售的产品，包括已删除或暂停的产品。<a href='https://www.idcsmart.com/wiki_list/394.html' target='_blank'>帮助文档</a>";
		$result["modalTitle"] = $result["btnText"];
		$result["page"] = $_GET["page"] ? $_GET["page"] : 1;
		$result["titleList"] = ["ID", "客户", "产品名称(主机名)", "IP", "类型", "购买时间", "到期时间", "周期", "价格", "状态", "销售"];
		$result["seachList"] = [];
		$serchres = $HostManage->getTimetype($request);
		if ($serchres["status"] == 200) {
			if ($serchres["data"]["gateway_list"]) {
				$list = [];
				foreach ($serchres["data"]["gateway_list"] as $key => $item) {
					$list[$key]["value"] = $key;
					$list[$key]["label"] = $item;
				}
				array_unshift($list, ["value" => "", "label" => ""]);
				$result["seachList"][count($result["seachList"])] = ["type" => "select", "label" => "支付方式", "name" => "payment", "content" => "", "list" => $list];
			} else {
				$list = [];
				array_unshift($list, ["value" => "", "label" => ""]);
				$result["seachList"][count($result["seachList"])] = ["type" => "select", "label" => "支付方式", "name" => "payment", "content" => "", "list" => $list];
			}
			if ($serchres["data"]["product_type"]) {
				$list = [];
				foreach ($serchres["data"]["product_type"] as $key => $item) {
					$list[$key]["value"] = $key;
					$list[$key]["label"] = $item;
				}
				array_unshift($list, ["value" => "", "label" => ""]);
				$result["seachList"][count($result["seachList"])] = ["type" => "select", "label" => "产品类型", "name" => "product_type", "content" => "", "list" => $list];
			} else {
				$list = [];
				array_unshift($list, ["value" => "", "label" => ""]);
				$result["seachList"][count($result["seachList"])] = ["type" => "select", "label" => "产品类型", "name" => "product_type", "content" => "", "list" => $list];
			}
			if ($serchres["data"]["domainstatus"]) {
				$list = [];
				foreach ($serchres["data"]["domainstatus"] as $key => $item) {
					$list[$key]["value"] = $key;
					$list[$key]["label"] = $item;
				}
				array_unshift($list, ["value" => "", "label" => ""]);
				$result["seachList"][count($result["seachList"])] = ["type" => "select", "label" => "主机状态", "name" => "domainstatus", "content" => "", "list" => $list];
			} else {
				$list = [];
				array_unshift($list, ["value" => "", "label" => ""]);
				$result["seachList"][count($result["seachList"])] = ["type" => "select", "label" => "主机状态", "name" => "domainstatus", "content" => "", "list" => $list];
			}
			if ($serchres["data"]["billingcycle"]) {
				$list = [];
				foreach ($serchres["data"]["billingcycle"] as $key => $item) {
					$list[$key]["value"] = $key;
					$list[$key]["label"] = $item;
				}
				array_unshift($list, ["value" => "", "label" => ""]);
				$result["seachList"][count($result["seachList"])] = ["type" => "select", "label" => "付款周期", "name" => "billingcycle", "content" => "", "list" => $list];
			} else {
				$list = [];
				array_unshift($list, ["value" => "", "label" => ""]);
				$result["seachList"][count($result["seachList"])] = ["type" => "select", "label" => "付款周期", "name" => "billingcycle", "content" => "", "list" => $list];
			}
			$result["seachList"][count($result["seachList"])] = ["type" => "text", "label" => "主机名", "name" => "domain", "content" => ""];
			$result["seachList"][count($result["seachList"])] = ["type" => "text", "label" => "ip", "name" => "ip", "content" => ""];
			$result["seachList"][count($result["seachList"])] = ["type" => "text", "label" => "客户", "name" => "username", "content" => ""];
		}
		$order = "id";
		$sort = "DESC";
		$page = 1;
		$pagecount = 10;
		if ($_GET) {
			foreach ($result["seachList"] as &$sl) {
				foreach ($_GET as $kk => $gg) {
					if ($sl["name"] == $kk) {
						$sl["content"] = $gg;
					}
				}
			}
			$request->order = $_GET["order"] ? $_GET["order"] : $order;
			$request->sort = $_GET["sort"] ? $_GET["sort"] : $sort;
			$request->domainstatus = $_GET["type"] ? $_GET["type"] : "All";
		}
		$request->page = $_GET["page"] ? $_GET["page"] : $page;
		$request->pagecount = $_GET["pagecount"] ? $_GET["pagecount"] : $pagecount;
		$res = $HostManage->getList($request);
		$result["total"] = $res["data"]["pagination"]["count"];
		$pageSize = \intval($res["data"]["pagination"]["count"] / $pagecount) + ($res["data"]["pagination"]["count"] % $pagecount == 0 ? 0 : 1);
		for ($i = 1; $i <= $pageSize; $i++) {
			$result["pageSize"][$i - 1] = $i;
		}
		foreach ($res["data"]["list"] as &$item) {
			if ($item["regdate"]) {
				$item["regdate_format"] = date("Y-m-d H:i:s", $item["regdate"]);
			} else {
				$item["regdate_format"] = "-";
			}
			if ($item["nextduedate"]) {
				$item["nextduedate_format"] = date("Y-m-d H:i:s", $item["nextduedate"]);
			} else {
				$item["nextduedate_format"] = "-";
			}
		}
		$result["type"] = $_GET["type"] ? $_GET["type"] : "All";
		$result["datalist"] = $res["data"]["list"];
		$result["page_total"] = $res["data"]["page_total"];
		$result["currenttotal"] = $res["data"]["total"];
		return $this->display($result);
	}
	public function cancelrequests(\think\Request $request)
	{
		$UserManage = controller("UserManage");
		$result["tip"] = "查看取消的订单，添加客户端取消原因。<a href='https://www.idcsmart.com/wiki_list/393.html' target='_blank'>帮助文档</a>";
		$result["btnText"] = "取消原因管理";
		$result["modalTitle"] = $result["btnText"];
		$result["page"] = $_GET["page"] ? $_GET["page"] : 1;
		$result["titleList"] = ["用户(公司)", "请求时间", "产品(主机名)", "IP", "类型(立即、到期)", "原因", "删除时间", "状态", "操作"];
		$order = "id";
		$sort = "desc";
		$page = 1;
		$limit = 10;
		if ($_GET) {
			foreach ($result["seachList"] as &$sl) {
				foreach ($_GET as $kk => $gg) {
					if ($sl["name"] == $kk) {
						$sl["content"] = $gg;
					}
				}
			}
			$request->order = $_GET["order"] ? $_GET["order"] : $order;
			$request->sort = $_GET["sort"] ? $_GET["sort"] : $sort;
			$request->domainstatus = $_GET["type"] ? $_GET["type"] : "";
		}
		$request->page = $_GET["page"] ? $_GET["page"] : $page;
		$request->limit = $_GET["limit"] ? $_GET["limit"] : $limit;
		$res = $UserManage->requestCancelList();
		$result["total"] = $res["data"]["total"];
		$pageSize = \intval($res["data"]["total"] / $limit) + ($res["data"]["total"] % $limit == 0 ? 0 : 1);
		for ($i = 1; $i <= $pageSize; $i++) {
			$result["pageSize"][$i - 1] = $i;
		}
		foreach ($res["data"]["list"] as &$item) {
			if ($item["create_time"]) {
				$item["create_time_format"] = date("Y-m-d H:i:s", $item["create_time"]);
			} else {
				$item["create_time_format"] = "-";
			}
			if ($item["nextduedate"]) {
				$item["nextduedate_format"] = date("Y-m-d H:i:s", $item["nextduedate"]);
			} else {
				$item["nextduedate_format"] = "-";
			}
		}
		$result["datalist"] = $res["data"]["list"];
		return $this->display($result);
	}
	public function orderdetail(\think\Request $request)
	{
		$_GET["id"];
		$result["titleList"] = ["ID", "条目", "描述", "付款周期", "金额", "状态", "付款状态", "操作"];
		$result["btnText"] = "添加规则";
		$OrderManage = controller("Order");
		$res = $OrderManage->read($_GET["id"]);
		if ($res["status"] == 200) {
			if ($res["data"]["create_time"]) {
				$res["data"]["create_time_format"] = date("Y-m-d H:i:s", $res["data"]["create_time"]);
			} else {
				$res["data"]["create_time_format"] = "-";
			}
			$result["detail"] = $res;
			$result["detailJSON"] = json_encode($result["detail"]);
		} else {
			$result["detail"] = null;
		}
		return $this->display($result);
	}
}