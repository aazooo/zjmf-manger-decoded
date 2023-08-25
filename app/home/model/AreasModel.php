<?php

namespace app\home\model;

class AreasModel extends \think\Model
{
	protected $pk = "area_id";
	/**
	 *  获取地区列表
	 */
	public function listQuery($pid = 0)
	{
		$pid = $pid > 0 ? $pid : \intval(input("pid"));
		return $this->where(["show" => 1, "data_flag" => 1, "pid" => $pid])->field("area_id,name,pid")->order("sort desc")->select();
	}
}