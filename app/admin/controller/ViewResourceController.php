<?php

namespace app\admin\controller;

class ViewResourceController extends ViewAdminBaseController
{
	public function display($data)
	{
		$arr = preg_split("/\\//", $_SERVER["REDIRECT_URL"]);
		return $this->view($arr[2], $data);
	}
	/**
	 * 手动资源
	 */
	public function munualresource(\think\Request $request)
	{
		$param = $request->param();
		$result["navTabs"] = [["label" => "资源管理", "name" => "zy"], ["label" => "上游设置", "name" => "sy"]];
		$result["seleTab"] = $_GET["seleTab"] ? $_GET["seleTab"] : "zy";
		$result["tip"] = "详细统计收入、支出记录及总额。<a href='https://bbs.idcsmart.com/forum.php?mod=viewthread&tid=354&fromuid=39' target='_blank'>帮助文档</a>";
		if ($result["seleTab"] == "zy") {
			$result["btnText"] = "添加资源";
			$res = controller("upperReaches");
			$options = $res->index($request);
			$list = [["label" => "", "value" => ""]];
			foreach ($options["data"] as $op) {
				$option["label"] = $op["name"];
				$option["value"] = $op["id"];
				array_push($list, $option);
			}
			$result["seachList"] = [["type" => "text", "label" => "主IP", "name" => "in_ip", "content" => ""], ["type" => "select", "label" => "上游", "name" => "pid", "content" => "", "list" => $list]];
			$result["titleList"] = ["重装/破解", "电源", "IP", "配置", "用户名/密码", "上游", "成本", "关联客户（产品）", "到期时间", "操作"];
			$result["modalTitle"] = $result["btnText"];
			$result["page"] = $param["page"] ? $param["page"] : 1;
			$upperReaches = controller("upperReaches");
			$res = $upperReaches->upperIndex($request);
			$result["pageSize"] = $this->ajaxPages($res["data"], 10, $result["page"], $res["total"]);
			foreach ($res["data"] as &$item) {
				$item["names"] = str_replace("#/customer-view/product-innerpage", "/admin/clientsviewservices", $item["names"]);
				$item["nextduedate"] = $item["nextduedate"] ? date("Y-m-d", $item["nextduedate"]) : "-";
				$item["paid_time"] = $item["paid_time"] ? date("Y-m-d", $item["paid_time"]) : "-";
				$item["ipcount"] = count($item["ip"]);
			}
			$result["data"] = $res["data"];
			$result["total"] = $res["total"];
		} else {
			$result["btnText"] = "添加上游";
		}
		return $this->display($result);
	}
	/**
	 * 添加资源
	 */
	public function addOrEditresource(\think\Request $request)
	{
		$param = $request->param();
		$result["id"] = $param["id"];
		$res = controller("upperReaches");
		$options = $res->index($request);
		$list = [["label" => "", "value" => ""]];
		foreach ($options["data"] as $op) {
			$option["label"] = $op["name"];
			$option["value"] = $op["id"];
			array_push($list, $option);
		}
		$result["sylist"] = $list;
		$options2 = $res->addupperpage($request);
		$result["kzfslist"] = $options2;
		if (!empty($param["id"])) {
			$result["title"] = "编辑资源管理";
		} else {
			$result["title"] = "添加资源管理";
		}
		return $this->display($result);
	}
	/**
	 * 添加上游
	 */
	public function upStreamedit(\think\Request $request)
	{
		$result["data"] = "test";
		return $this->display($result);
	}
	/**
	 * API对接
	 */
	public function zjmfapi(\think\Request $request)
	{
		$result["data"] = "test";
		return $this->display($result);
	}
}