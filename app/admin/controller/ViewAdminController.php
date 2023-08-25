<?php

namespace app\admin\controller;

class ViewAdminController extends ViewAdminBaseController
{
	public function index(\think\Request $request)
	{
		$data["Title"] = "首页";
		return $this->view("index", $data);
	}
}