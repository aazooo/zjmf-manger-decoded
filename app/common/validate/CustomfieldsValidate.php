<?php

namespace app\common\validate;

class CustomfieldsValidate extends \think\Validate
{
	protected $rule = ["addfieldtype" => "in:text,link,password,dropdown,tickbox,textarea", "addcustomfielddesc" => "chsDash", "addadminonly" => "in:0,1", "addrequired" => "in:0,1", "addshoworder" => "in:0,1", "addsortorder" => "number", "showdetail" => "in:0,1", "customfieldname" => "array|checkCustomfieldname:thinkphp", "customfieldtype" => "array|checkCustomfieldtype:thinkphp", "customfielddesc" => "array|checkCustomfielddesc:thinkphp", "customfieldoptions" => "array|checkCustomfieldoptions:thinkphp", "customfieldregexpr" => "array", "customadminonly" => "array|checkCustomadminonly:thinkphp", "customrequired" => "array|checkCustomrequired:thinkphp", "customshoworder" => "array|checkCustomshoworder:thinkphp", "customsortorder" => "array|checkCustomsortorder:thinkphp", "customshowdetail" => "array|checkCustomshowdetail:thinkphp"];
	protected $message = ["addfieldtype.in" => "新增自定义字段类型只能是text,link,password,dropdown,tickbox,textarea", "addcustomfielddesc.chsDash" => "新增自定义字段描述只能是汉字、字母、数字和下划线_及破折号-", "addsortorder.number" => "新增自定义字段排序必须为数字", "customfieldname.array" => "自定义字段名称必须为数组", "customfieldtype.array" => "自定义字段类型必须为数组", "customfielddesc.array" => "自定义字段描述必须为数组", "customfieldoptions.array" => "自定义字段选项必须为数组", "customfieldregexpr.array" => "自定义字段正则必须为数组", "customadminonly.array" => "自定义字段仅管理员开关必须为数组", "customrequired.array" => "自定义字段必填开关必须为数组", "customshoworder.array" => "自定义字段在订单/账单上显示开关须为数组", "customsortorder.array" => "自定义字段排序必须为数组", "customshowdetail.array" => "自定义字段在产品内页显示必须为数组"];
	protected function checkCustomfieldname($array)
	{
		foreach ($array as $value) {
			if (!empty($value)) {
				$status = preg_match("/^[\\x{4e00}-\\x{9fa5}a-zA-Z0-9\\_\\-]+\$/u", \strval($value));
				if (!$status) {
					return "自定义字段名称只能是汉字、字母、数字和下划线_及破折号-";
				}
			}
		}
		return true;
	}
	protected function checkCustomfieldtype($array)
	{
		foreach ($array as $item) {
			if (!empty($item)) {
				if (!in_array($item, ["text", "link", "password", "dropdown", "tickbox", "textarea"])) {
					return "自定义字段类型错误";
				}
			}
		}
		return true;
	}
	protected function checkCustomfielddesc($array)
	{
		foreach ($array as $value) {
			if (!empty($value)) {
				$status = preg_match("/^[\\x{4e00}-\\x{9fa5}a-zA-Z0-9\\_\\-]+\$/u", \strval($value));
				if (!$status) {
					return "自定义字段描述只能是汉字、字母、数字和下划线_及破折号-";
				}
			}
		}
		return true;
	}
	protected function checkCustomfieldoptions($array)
	{
		foreach ($array as $value) {
			if (!empty($value)) {
				$arr = explode(",", $value);
				foreach ($arr as $v) {
					$status = preg_match("/^[\\x{4e00}-\\x{9fa5}a-zA-Z0-9]+\$/u", \strval($v));
					if (!$status) {
						return "自定义字段下拉选项只能是汉字、字母和数字";
					}
				}
			}
		}
		return true;
	}
	protected function checkCustomadminonly($array)
	{
		foreach ($array as $item) {
			if (!empty($item)) {
				if (!in_array($item, ["1", "on", "yes"])) {
					return "自定义字段仅管理员开关必须是yes、on或者1";
				}
			}
		}
		return true;
	}
	protected function checkCustomrequired($array)
	{
		foreach ($array as $item) {
			if (!empty($item)) {
				if (!in_array($item, ["1", "on", "yes"])) {
					return "自定义字段必填开关必须是yes、on或者1";
				}
			}
		}
		return true;
	}
	protected function checkCustomshoworder($array)
	{
		foreach ($array as $item) {
			if (!empty($item)) {
				if (!in_array($item, ["1", "on", "yes"])) {
					return "自定义字段在订单上显示开关必须是yes、on或者1";
				}
			}
		}
		return true;
	}
	protected function checkCustomshowinvoice($array)
	{
		foreach ($array as $item) {
			if (!empty($item)) {
				if (!in_array($item, ["1", "on", "yes"])) {
					return "自定义字段在账单上显示开关必须是yes、on或者1";
				}
			}
		}
		return true;
	}
	protected function checkCustomsortorder($array)
	{
		foreach ($array as $item) {
			if (!empty($item)) {
				if (!is_numeric($item)) {
					return "自定义字段排序必须是数字";
				}
			}
		}
		return true;
	}
	protected function checkCustomshowdetail($array)
	{
		foreach ($array as $item) {
			if (!empty($item)) {
				if (!in_array($item, ["1", "on", "yes"])) {
					return "自定义字段在产品内页显示显示开关必须是yes、on或者1";
				}
			}
		}
		return true;
	}
}