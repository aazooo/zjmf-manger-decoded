<?php

namespace app\admin\validate;

class NewsTypeValidate extends \think\Validate
{
	protected $rule = ["parent_id" => "number", "title" => "require|length:1,30", "sort" => "number", "status" => "number|between:0,10"];
	protected $message = ["parent_id.number" => "管理员分类必须为分类id", "title.require" => "标题不能为空", "title.length" => "标题长度在1到15个字", "sort.number" => "排序必须为数字", "status.number" => "状态必须为数字", "status.between" => "状态在0-10之间"];
}