<?php

namespace app\openapi\controller;

/**
 * @title 帮助中心
 * @description 接口说明
 */
class KnowledgebaseController extends \cmf\controller\HomeBaseController
{
	public function __construct()
	{
		$this->ViewModel = new \app\home\model\ViewModel();
	}
	public function knowledgebase(\think\Request $request)
	{
		if (trim($request->alias)) {
			$model = \think\Db::name("news_type")->field("id")->where("alias", trim($request->alias))->find();
			$request->cate = $model ? $model["id"] : $request->cate;
		}
		$param = $request->param();
		$page = $param["page"] >= 1 ? intval($param["page"]) : 1;
		$limit = $param["limit"] >= 1 ? intval($param["limit"]) : 20;
		$orderby = strval($param["orderby"]) ? strval($param["orderby"]) : "id";
		$sort = $param["sort"] ?? "DESC";
		$keywords = $param["keywords"] ?? "";
		$cate = isset($param["cate"]) ? intval($param["cate"]) : 0;
		$params["limit"] = $limit;
		if (!empty($keywords)) {
			$params["search"] = $keywords;
			$params["page"] = $page;
			$params["data"] = "Search";
			$helpList = $this->ViewModel->helpSearch($params);
		} else {
			if (!empty($cate)) {
				$params["html2"] = $cate;
				$params["html3"] = $page;
				$params["data"] = "ListCate";
				$helpList = $this->ViewModel->helpListCate($params);
			} else {
				$params["html2"] = $page;
				$helpList = $this->ViewModel->helpList($params);
			}
		}
		$helpCate = $this->ViewModel->helpCate();
		foreach ($helpCate as $k => $v) {
			unset($helpCate[$k]["parent_id"]);
			unset($helpCate[$k]["status"]);
			unset($helpCate[$k]["hidden"]);
			unset($helpCate[$k]["sort"]);
		}
		foreach ($helpList["list"] as $k => $v) {
			$helpList["list"][$k]["label"] = !empty($v["label"]) ? $v["label"] : "";
			unset($helpList["list"][$k]["parent_id"]);
			unset($helpList["list"][$k]["admin_id"]);
			unset($helpList["list"][$k]["create_date"]);
			unset($helpList["list"][$k]["update_date"]);
			unset($helpList["list"][$k]["push_date"]);
			unset($helpList["list"][$k]["hidden"]);
			unset($helpList["list"][$k]["sort"]);
		}
		$data["cate"] = $helpCate;
		$data["knowledgebase"] = $helpList["list"];
		$data["total"] = $helpList["count"];
		return json(["status" => 200, "data" => $data, "msg" => lang("SUCCESS MESSAGE")]);
	}
	public function knowledgebaseContent(\think\Request $request)
	{
		$param = $request->param();
		$params["html2"] = $param["id"];
		$data = $this->ViewModel->helpContent($params);
		$data["label"] = !empty($data["label"]) ? $data["label"] : "";
		unset($data["parent_id"]);
		unset($data["hidden"]);
		unset($data["sort"]);
		return json(["status" => 200, "data" => $data, "msg" => lang("SUCCESS MESSAGE")]);
	}
}