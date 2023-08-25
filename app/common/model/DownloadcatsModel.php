<?php

namespace app\common\model;

class DownloadcatsModel extends \think\Model
{
	protected $autoWriteTimestamp = false;
	public static function getCatesHome($cate_id)
	{
		$cate_data = \think\Db::name("downloadcats")->field("id,name,parentid,description")->where("parentid", $cate_id)->where("hidden", 0)->select()->toArray();
		return $cate_data;
	}
	public static function getAllCatesHome()
	{
		$cate_data = \think\Db::name("downloadcats")->field("id,name,parentid,description")->where("hidden", 0)->order("sort", asc)->select()->toArray();
		return $cate_data;
	}
}