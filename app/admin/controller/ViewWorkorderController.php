<?php

namespace app\admin\controller;

class ViewWorkorderController extends ViewAdminBaseController
{
	public function display($data)
	{
		$arr = preg_split("/\\//", $_SERVER["REDIRECT_URL"]);
		return $this->view($arr[2], $data);
	}
	/**
	 * 工单列表
	 */
	public function supportticket(\think\Request $request)
	{
		$param = $request->param();
		$result["titleList"] = ["ID", "主题", "提交人", "状态", "处理人", "部门", "提交时间", "上次回复"];
		$result["tip"] = "与客户沟通的所有工单记录，不同管理员只能访问自己所在部门的工单。";
		$result["btnText"] = "新建工单";
		$result["navTabs"] = [["label" => "待处理", "name" => "1"], ["label" => "已回复", "name" => "2"], ["label" => "等待中", "name" => "5"], ["label" => "关闭", "name" => "4"], ["label" => "全部", "name" => "0"]];
		$result["seachList"] = [["type" => "text", "label" => "客户", "name" => "username", "content" => ""], ["type" => "select", "label" => "部门", "name" => "dptid", "content" => ""], ["type" => "select", "label" => "状态", "name" => "status", "content" => ""], ["type" => "select", "label" => "优先级", "name" => "priority", "content" => "", "list" => [["label" => "", "value" => ""], ["label" => "高", "value" => "high"], ["label" => "中", "value" => "medium"], ["label" => "低", "value" => "low"]]], ["type" => "text", "label" => "主题/内容", "name" => "content", "content" => ""], ["type" => "text", "label" => "工单编号", "name" => "tid", "content" => ""]];
		$result["modalTitle"] = $result["btnText"];
		$result["page"] = $param["page"] ? $param["page"] : 1;
		$result["status"] = $param["status"];
		$Public = controller("Public");
		$Departmentres = $Public->getTicketDepartment();
		if ($Departmentres["data"]) {
			$list = [];
			foreach ($Departmentres["data"] as $key => $item) {
				$list[$key]["value"] = $item["id"];
				$list[$key]["label"] = $item["name"];
			}
			array_unshift($list, ["value" => "", "label" => ""]);
			$result["seachList"][1]["list"] = $list;
		}
		$_GET["limit"] = 10;
		$_GET["status"] = 0;
		$Ticket = controller("Ticket");
		$res = $Ticket->getList();
		$result["ticket_status"] = $res["data"]["ticket_status"];
		if ($result["ticket_status"]) {
			$list = [];
			foreach ($result["ticket_status"] as $key => $item) {
				$list[$key]["value"] = $item["id"];
				$list[$key]["label"] = $item["title"];
			}
			array_unshift($list, ["value" => "0", "label" => "全部"]);
			$result["seachList"][2]["list"] = $list;
		}
		$result["total"] = $res["data"]["sum"];
		$result["pageSize"] = $this->ajaxPages($res["data"]["sum"], 10, $result["page"], $res["data"]["sum"]);
		$result["data"] = $res["data"]["list"];
		$result["dataJSON"] = json_encode($res["data"]["list"]);
		foreach ($result["data"] as &$item) {
			$item["create_time_formart"] = date("Y-m-d H:i:s", $item["create_time"]);
			$item["last_reply_time_formart"] = date("Y-m-d H:i:s", $item["last_reply_time"]);
		}
		return $this->display($result);
	}
	/**
	 * 工单统计
	 */
	public function supportstatistics(\think\Request $request)
	{
		$param = $request->param();
		$result["titleList"] = ["处理人", "处理工单数", "总分", "1分", "2分", "3分", "4分", "5分"];
		$result["tip"] = "统计各工单处理人处理的工单数量及评分情况，默认统计当前月数据，可通过查询获取更多信息";
		$_GET["limit"] = 10;
		$result["page"] = $param["page"] ? $param["page"] : 1;
		$Ticket = controller("Ticket");
		$res = $Ticket->ticketStatistics();
		$result["total"] = $res["data"]["sum"];
		$result["pageSize"] = $this->ajaxPages($result["total"], 10, $result["page"], $result["total"]);
		$result["data"] = $res["data"]["list"];
		return $this->display($result);
	}
	/**
	 * 工单部门
	 */
	public function configticketdepartments(\think\Request $request)
	{
		$param = $request->param();
		$result["titleList"] = ["部门名称", "描述", "是否隐藏", "自动回复", "操作"];
		$result["btnText"] = "添加新部门";
		$result["tip"] = "这是您配置的支持工单部门。您输入的邮件地址将用于检测发送到该部门，所有该部门的邮件也将用此邮件地址发出。邮件管道（Email Piping）允许通过邮件回复或开启工单，并可以使用以下方式之一设置。";
		$TicketDepartment = controller("TicketDepartment");
		$res = $TicketDepartment->getList();
		$result["data"] = $res["data"];
		return $this->display($result);
	}
	/**
	 * 添加新部门
	 */
	public function configticketdepartmentsadd(\think\Request $request)
	{
		$TicketDepartment = controller("TicketDepartment");
		if ($_GET["id"]) {
			$result["tip"] = "编辑部门";
			$detailRes = $TicketDepartment->getDetail($_GET["id"]);
			if ($detailRes["status"] === 200 && $detailRes["data"]) {
				$result["editData"] = $detailRes["data"];
			}
		} else {
			$result["editData"] = null;
			$result["tip"] = "添加新部门";
		}
		$res = $TicketDepartment->addPage();
		$list = [];
		if ($res["status"] === 200 && $res["data"]["zjmf_finance_api"]) {
			foreach ($res["data"]["zjmf_finance_api"] as $key => $item) {
				$list[$key] = $item["id"];
			}
		}
		$request->id = $list;
		$Common = controller("Common");
		$res2 = $Common->getUpstreamTicketDepartmentList();
		$result["financeOptions"] = $res["data"]["zjmf_finance_api"];
		$result["departments"] = $res2["upstream_ticket_departments"];
		foreach ($result["financeOptions"] as $key => &$item) {
			foreach ($result["departments"] as $key2 => $item2) {
				if ($item["id"] === $key2) {
					$item["departments"] = $item2["data"]["data"];
				}
			}
		}
		$result["financeOptionsJSon"] = json_encode($result["financeOptions"]);
		$result["editDataJSON"] = json_encode($result["editData"]);
		return $this->display($result);
	}
	/**
	 * 工单状态
	 */
	public function configticketstatuses(\think\Request $request)
	{
		$param = $request->param();
		$result["titleList"] = ["ID", "标题", "排序", "操作"];
		$result["btnText"] = "添加";
		$result["tip"] = "在此处您可以自定义工单状态。无法删除或重命名 5 种默认状态：开启中、已回复、客户回复、已关闭、等待中。";
		$TicketStatus = controller("TicketStatus");
		$res = $TicketStatus->getList();
		$result["data"] = $res["data"];
		return $this->display($result);
	}
	/**
	 * 工单传递
	 */
	public function configticketpass(\think\Request $request)
	{
		$result["titleList"] = ["部门", "产品", "屏蔽关键字", "自动回复", "操作"];
		$result["btnText"] = "添加规则";
		$TicketDeliver = controller("TicketDeliver");
		$res = $TicketDeliver->getList();
		$result["data"] = $res["data"];
		$result["editData"] = json_encode($res["data"]);
		foreach ($result["data"] as &$item) {
			$item["departmentsStr"] = "";
			$item["productsStr"] = "";
			if ($item["departments"]) {
				foreach ($item["departments"] as &$item2) {
					$item["departmentsStr"] = $item["departmentsStr"] . $item2["name"];
				}
				foreach ($item["products"] as &$item2) {
					$item["productsStr"] = $item["productsStr"] . $item2["name"];
				}
			}
		}
		return $this->display($result);
	}
}