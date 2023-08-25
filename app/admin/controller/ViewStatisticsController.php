<?php

namespace app\admin\controller;

class ViewStatisticsController extends ViewAdminBaseController
{
	public function display($data)
	{
		$arr = preg_split("/\\//", $_SERVER["REDIRECT_URL"]);
		return $this->view($arr[2], $data);
	}
	/**
	 * 年度收入统计
	 */
	public function annualstatistics(\think\Request $request)
	{
		$result["data"] = "test";
		return $this->display($result);
	}
	/**
	 * 新客户
	 */
	public function newcustomer(\think\Request $request)
	{
		$result["data"] = "test";
		return $this->display($result);
	}
	/**
	 * 产品收入
	 */
	public function productrevenue(\think\Request $request)
	{
		$result["data"] = "test";
		return $this->display($result);
	}
	/**
	 * 收入排名
	 */
	public function revenueranking(\think\Request $request)
	{
		$result["data"] = "test";
		return $this->display($result);
	}
}