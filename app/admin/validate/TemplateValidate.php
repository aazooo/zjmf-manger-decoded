<?php

namespace app\admin\validate;

class TemplateValidate extends \think\Validate
{
	protected $rule = ["name" => "require|max:100", "subject" => "require|max:100", "body" => "require|max:1000"];
	protected $message = ["name.require" => "模板名称必填", "name.max" => "模板名称不能超过100个字符", "subject.require" => "模板主题必填", "subject.max" => "模板主题不超过50个字符", "body.require" => "模板正文必填", "body.max" => "模板正文不超过1000个字符"];
	protected $scene = ["email" => ["name", "subject", "body"]];
}