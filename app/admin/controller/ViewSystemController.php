<?php

namespace app\admin\controller;

class ViewSystemController extends ViewAdminBaseController
{
	public function display($data)
	{
		$arr = preg_split("/\\//", $_SERVER["REDIRECT_URL"]);
		return $this->view($arr[2], $data);
	}
	public function addHelp(\think\Request $request)
	{
		$result["pageTitle"] = "添加帮助";
		$result["formList"] = [["type" => "text", "label" => "帮助标题", "name" => "title", "content" => "", "tip" => "请填写帮助标题", "required" => true], ["type" => "select", "label" => "分类", "name" => "sort", "content" => "", "list" => [["label" => "选项一", "value" => "1"], ["label" => "选项二", "value" => "2"], ["label" => "选项三", "value" => "3"]]], ["type" => "checkbox", "label" => "是否隐藏", "name" => "isShow", "content" => ""], ["type" => "date", "label" => "日期选择", "name" => "helpDate", "content" => ""], ["type" => "text", "label" => "标签", "name" => "helpLabel", "content" => "", "tip" => "请填写标签"], ["type" => "text", "label" => "描述", "name" => "description", "content" => "", "tip" => "请填写描述"], ["type" => "textarea", "label" => "文章内容", "name" => "content", "content" => ""]];
		$result["success"] = "保存更改";
		$result["cancel"] = "取消更改";
		$data = $_GET;
		if ($data) {
			foreach ($result["formList"] as &$item) {
				$content = $data[$item["name"]];
				$item["content"] = $content;
				if ($item["required"]) {
					if (!$content) {
						$result["errorMsg"] = "请填写" . $item["label"];
						return $this->display($result);
					}
					if ($item["name"] == "title" && $content != "飞飞") {
						$result["errorMsg"] = $item["label"] . "填写错误";
						return $this->display($result);
					}
				}
			}
			$result["errorMsg"] = "保存成功";
		}
		return $this->display($result);
	}
	public function permissionsmanagment(\think\Request $request)
	{
		$result["tip"] = "设置管理分组可以方便的调整每个管理员/客服在后台的权限";
		$result["data"] = "test";
		$result["btnText"] = "添加分组";
		$result["titleList"] = ["ID", "分组名称", "说明", "状态", "组成员", "操作"];
		$RbacManage = controller("Rbac");
		$res = $RbacManage->index();
		$result["datalist"] = $res["roles"];
		return $this->display($result);
	}
	public function smstemplateindex(\think\Request $request)
	{
		$result["data"] = "test";
		$result["btnText"] = "创建模版";
		$result["titleList"] = ["编号", "模板ID", "类型", "模板标题", "模板内容", "审核状态", "操作"];
		$result["seachList"] = [["type" => "text", "label" => "模板ID搜索", "name" => "template_id", "content" => ""], ["type" => "text", "label" => "模板标题搜索", "name" => "title", "content" => ""]];
		$ConfigMessageManage = controller("ConfigMessage");
		$configMobileRes = $ConfigMessageManage->configMobile();
		if ($configMobileRes["status"] === 200) {
			$sms_operator = $configMobileRes["msg_config"]["smg_operator"];
			$request->order = "id";
			$request->order_method = "asc";
			$request->page = 1;
			$request->limit = 10;
			$request->sms_operator = $sms_operator;
			$res = $ConfigMessageManage->templateList();
			$result["sms_operator"] = $_GET["sms_operator"] ? $_GET["sms_operator"] : $sms_operator;
			$result["total"] = $res["total"];
			$pageSize = \intval($res["total"] / 10) + ($res["total"] % 10 == 0 ? 0 : 1);
			for ($i = 1; $i <= $pageSize; $i++) {
				$res["pageSize"][$i - 1] = $i;
			}
			$request->sms_operator = $sms_operator;
			$ConfigMessageManage->updateTemStatus();
			$result["selectedOptions"] = $res["data"];
			$result["selectedOptionsJSON"] = json_encode($res["data"]);
			$result["datalist"] = $res["templates"];
		}
		return $this->display($result);
	}
}