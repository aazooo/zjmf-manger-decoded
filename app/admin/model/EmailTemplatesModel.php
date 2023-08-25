<?php

namespace app\admin\model;

class EmailTemplatesModel extends \think\Model
{
	public function getGroupInfo()
	{
		$results = $this->field("id,type,name")->select()->toArray();
		foreach ($results as $key => $value) {
			$elist[$key] = array_map(function ($v) {
				return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
			}, $value);
		}
		$arr = [];
		foreach ($elist as $k => $v) {
			if (isset($arr[$v["type"]])) {
				array_push($arr[$v["type"]], $v);
			} else {
				$arr[$v["type"]][0] = $v;
			}
		}
		return $arr;
	}
	public function getEmailTemplates($type = "")
	{
		$templates = $this->field("id,type,name,name_en")->where("type", $type)->where("disabled", 0)->order("id", "asc")->select()->toArray();
		return $templates ?: [];
	}
	public function getTemplateList($type, $language = "", $field = "*")
	{
		if (!$type) {
			return [];
		}
		$language = $language ?? get_system_lang();
		$templates = $this->field($field)->where("type", $type)->where("language", $language)->where("disabled", 0)->select()->toArray();
		return $templates ?? [];
	}
}