<?php

namespace app\common\model;

class SeniorConfModel
{
	public function getProductUseConfLinksList($product_id)
	{
		$use_conf_ids = \think\Db::name("product_config_options")->alias("pco")->leftJoin("product_config_links pcl", "pcl.gid = pco.gid")->where("pcl.pid", $product_id)->where("pco.hidden", 0)->column("pco.id");
		return $this->getProductUseConfLinksMap($use_conf_ids);
	}
	public function getProductUseConfLinksMap($config_id)
	{
		$res_data = \think\Db::name("product_config_options_links")->field("id,config_id,relation,sub_id")->whereIn("config_id", $config_id)->where("type", "condition")->where("relation_id", 0)->withAttr("sub_id", function ($value) {
			return json_decode($value, true);
		})->select()->toArray();
		foreach ($res_data as &$res) {
			$result = \think\Db::name("product_config_options_links")->field("id,config_id,relation,sub_id")->where("relation_id", $res["id"])->where("type", "result")->withAttr("sub_id", function ($value) {
				return json_decode($value, true);
			})->select()->toArray();
			$res["result"] = $result ?: [];
		}
		return $res_data ?: [];
	}
	public function getProductUseConfLinksListExchange($product_id)
	{
		$use_conf_ids = \think\Db::name("product_config_options")->alias("pco")->leftJoin("product_config_links pcl", "pcl.gid = pco.gid")->where("pcl.pid", $product_id)->where("pco.hidden", 0)->column("pco.id");
		return $this->getProductUseConfLinksMapExchange($use_conf_ids);
	}
	public function getProductUseConfLinksMapExchange($config_id)
	{
		$res_data = \think\Db::name("product_config_options_links")->field("id,config_id,relation,sub_id,relation_id")->whereIn("config_id", $config_id)->where("type", "result")->withAttr("sub_id", function ($value) {
			return json_decode($value, true);
		})->select()->toArray();
		foreach ($res_data as &$res) {
			$result = \think\Db::name("product_config_options_links")->field("id,config_id,relation,sub_id")->where("id", $res["relation_id"])->where("type", "condition")->withAttr("sub_id", function ($value) {
				return json_decode($value, true);
			})->select()->toArray();
			$res["result"] = $result ?: [];
		}
		return $res_data ?: [];
	}
	public function getProductConfOne($config_id)
	{
		return \think\Db::name("product_config_options")->where("id", $config_id)->find();
	}
	public function getProductUseConfSubNames($sub_ids)
	{
		return \think\Db::name("product_config_options_sub")->whereIn("id", $sub_ids)->column("option_name");
	}
}