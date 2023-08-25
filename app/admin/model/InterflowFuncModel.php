<?php

namespace app\admin\model;

class InterflowFuncModel extends \think\Model
{
	protected $autoWriteTimestamp = true;
	protected $createTime = "create_time";
	protected $dateFormat = "Y/m/d H:i";
	protected $readonly = ["create_time"];
	public function getAllFunc()
	{
		$all_func = $this->column("keyword_id", "func_id");
		return $all_func ?: [];
	}
}