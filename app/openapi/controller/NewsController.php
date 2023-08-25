<?php

namespace app\openapi\controller;

/**
 * @title 新闻
 * @description 接口说明
 */
class NewsController extends \cmf\controller\HomeBaseController
{
	public function __construct()
	{
		$this->ViewModel = new \app\home\model\ViewModel();
	}
	public function news(\think\Request $request)
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
			$newsList = $this->ViewModel->newsSearch($params);
		} else {
			if (!empty($cate)) {
				$params["html2"] = $cate;
				$params["html3"] = $page;
				$params["data"] = "ListCate";
				$newsList = $this->ViewModel->newsListCate($params);
			} else {
				$params["html2"] = $page;
				$newsList = $this->ViewModel->newsList($params);
			}
		}
		$newsCate = $this->ViewModel->newsCate();
		foreach ($newsCate as $k => $v) {
			unset($newsCate[$k]["parent_id"]);
			unset($newsCate[$k]["status"]);
			unset($newsCate[$k]["hidden"]);
			unset($newsCate[$k]["sort"]);
		}
		foreach ($newsList["list"] as $k => $v) {
			$newsList["list"][$k]["label"] = !empty($v["label"]) ? $v["label"] : "";
			unset($newsList["list"][$k]["parent_id"]);
			unset($newsList["list"][$k]["admin_id"]);
			unset($newsList["list"][$k]["create_date"]);
			unset($newsList["list"][$k]["update_date"]);
			unset($newsList["list"][$k]["push_date"]);
			unset($newsList["list"][$k]["hidden"]);
			unset($newsList["list"][$k]["sort"]);
		}
		$data["cate"] = $newsCate;
		$data["news"] = $newsList["list"];
		$data["total"] = $newsList["count"];
		return json(["status" => 200, "data" => $data, "msg" => lang("SUCCESS MESSAGE")]);
	}
	public function newsContent(\think\Request $request)
	{
		$param = $request->param();
		$params["html2"] = $param["id"];
		$data = $this->ViewModel->newsContent($params);
		$data["label"] = !empty($data["label"]) ? $data["label"] : "";
		unset($data["parent_id"]);
		unset($data["hidden"]);
		unset($data["sort"]);
		return json(["status" => 200, "data" => $data, "msg" => lang("SUCCESS MESSAGE")]);
	}
}