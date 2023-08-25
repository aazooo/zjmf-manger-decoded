<?php

namespace app\admin\controller;

class ViewController extends ViewAdminBaseController
{
	public function index(\think\Request $request)
	{
		if (empty($request->html)) {
			$request->html = "index";
		}
		$view_tpl_file = view_tpl_file($request->html);
		$param = $request->param();
		$paramsDefaultData = ["setting" => "网站设置数据（默认输出）", "userInfo" => "登录用户信息（默认输出，为空是未登录）"];
		$paramsData = ["newsCate" => "获取新闻分类（无参数）", "newsList" => "获取新闻列表（无参数）", "newsListCate" => "获取新闻分类列表（无参数）", "newsSearch" => "获取新闻搜索（无参数）", "newsContent" => "获取新闻内容（无参数）", "helpCate" => "获取帮助分类（无参数）", "helpList" => "获取帮助列表（无参数）", "helpListCate" => "获取帮助分类列表（无参数）", "helpSearch" => "获取帮助搜索（无参数）", "helpContent" => "获取帮助内容（无参数）", "firstGroups" => "产品一级分类（参数格式firstGroups[fid:1|fid:2]输出ID=1和ID=2的一级分类，firstGroups输出所有一级分类）", "secondGroups" => "产品二级分类（参数格式secondGroups[sid:1|sid:2]输出ID=1和ID=2的二级分类，secondGroups输出所有二级分类）", "product" => "获取产品（参数格式product[pid:1|pid:3]输出ID=1和ID=3的产品,参数不能为空", "productGroups" => "获取二级分类ID下产品（参数格式productGroups[sid:1]输出产品分类ID=1关联的产品,参数不能为空"];
		$paramsData = array_merge($paramsDefaultData, $paramsData);
		$tagDataArray = $this->viewOutData($view_tpl_file, $paramsData);
		$tagDataArray = array_merge($paramsDefaultData, $tagDataArray);
		$paramsData404 = ["newsList", "newsListCate", "newsSearch", "helpList", "helpListCate", "helpSearch"];
		$paramsData404 = array_flip($paramsData404);
		$r404 = 0;
		$return["viewName"] = $request->html;
		$return["Themes"] = cmf_get_root() . "/" . adminAddress() . "/themes/" . adminTheme();
		$_SESSION["view_tpl_data"] = $return;
		$_SESSION["paramsData"] = $paramsData;
		return $this->webview($request->html, $return);
	}
	public function webview($tplName, $data)
	{
		$view = new \think\View();
		$view->init("Think");
		return $view->fetch($tplName, $data);
	}
}