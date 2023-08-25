<?php

namespace app\home\validate;

class InterflowValidate extends \think\Validate
{
	protected $rule = ["i_type" => "require", "i_account" => "require|max:32", "security_verify" => "in:on,off", "security_code" => "alphaDash"];
	protected $message = ["i_type.require" => "类型不为空", "i_account.max" => "账号支持最大长度32", "security_verify.in" => "敏感开关只能为on / off", "security_code.alphaDash" => "敏感验证码只能为字母和数字，下划线_及破折号-"];
	protected $scene = [];
}