<?php

namespace app\common\model;

class DownloadsModel extends \think\Model
{
	protected $autoWriteTimestamp = false;
	public static function getCatesDownloadHome($cate_id)
	{
		$data = \think\Db::name("downloads")->field("id,category,type,title,description,downloads")->where("category", $cate_id)->where("hidden", 0)->select()->toArray();
		return $data ?: [];
	}
	public static function getAllowDownListHome($cate_id = 0, $uid = 0)
	{
		if (empty($cate_id)) {
			return [];
		}
		$downloads = \think\Db::name("downloads")->field("id,category,type,title,description,downloads,location,locationname,clientsonly,update_time,create_time")->where("hidden", 0)->where("category", $cate_id)->order("create_time DESC")->select()->toArray();
		foreach ($downloads as $key => $val) {
			if (VIEW_TEMPLATE_RETURN_ARRAY === true) {
				$downloads[$key]["down_link"] = "downloads?action=download&id=" . $val["id"] . "&cate_id=" . $cate_id;
			} else {
				$downloads[$key]["down_link"] = "download/product_file?id=" . $val["id"];
			}
			if ($uid && $val["clientsonly"] == 1) {
				$downloads[$key]["clientsonly"] = 0;
			}
			$downloads[$key]["update_time"] = date("Y-m-d H:i:s", $downloads[$key]["create_time"] ?: $downloads[$key]["update_time"]);
			unset($downloads[$key]["create_time"]);
		}
		return $downloads;
	}
	public static function getHotDownlistHome($uid)
	{
		$downloads = \think\Db::name("downloads")->field("id,category,type,title,description,downloads")->where("hidden", 0)->order("downloads DESC")->order("sort ASC")->limit(5)->where(function ($query) use($uid) {
			if (empty($uid)) {
				$query->where("clientsonly", 0);
			}
		})->select()->toArray();
		foreach ($downloads as $key => $val) {
			$downloads[$key]["down_link"] = "download/product_file?id=" . $val["id"];
		}
		return $downloads;
	}
	public static function getAllDownlistHome($uid)
	{
		$downloads = \think\Db::name("downloads")->field("id,category,type,title,description,downloads")->order("downloads DESC")->order("id DESC")->where(function ($query) use($uid) {
		})->select()->toArray();
		foreach ($downloads as $key => $val) {
			$downloads[$key]["down_link"] = "download/product_file?id=" . $val["id"];
		}
		return $downloads;
	}
	public static function getAssociatedfiles($pid)
	{
		$data = \think\Db::name("downloads")->alias("d")->field("d.id,d.title")->leftJoin("product_downloads pl", "pl.download_id=d.id")->where("pl.product_id", $pid)->select()->toArray();
		return $data ?: [];
	}
	public static function seachFileHome($search = "", $uid)
	{
		$downloads = \think\Db::name("downloads")->field("id,type,title,description,downloads")->where("title LIKE '%{$search}%'")->whereOr("description LIKE '%{$search}%'")->where(function ($query) use($uid) {
			if (empty($uid)) {
				$query->where("clientsonly", 0);
			}
		})->select()->toArray();
		foreach ($downloads as $key => $val) {
			$downloads[$key]["down_link"] = "download/product_file?id=" . $val["id"];
		}
		return $downloads ?: [];
	}
}