<?php

namespace app\home\validate;

class UserValidate extends \think\Validate
{
	/**
	 * 定义验证规则
	 * 格式：'字段名'	=>	['规则1','规则2'...]
	 *
	 * @var array
	 */
	protected $rule = ["username" => "require|max:20", "sex" => "max:1", "profession" => "max:20", "signature" => "max:20", "companyname" => "max:20", "email" => "email", "country" => "max:30", "address1" => "max:50", "address2" => "max:50", "province" => "max:30", "city" => "max:30", "region" => "max:30", "postcode" => "number|between:1,999999", "phone_code" => "number", "currency" => "number", "defaultgateway" => "alphaDash|max:200", "notes" => "alphaDash|max:200", "groupid" => "number", "status" => "in:0,1,2", "language" => "alphaDash|max:200", "know_us" => "alphaDash|max:200", "qq" => "max:20", "api_password" => "max:32|alphaDash|min:6"];
	/**
	 * 定义错误信息
	 * 格式：'字段名.规则名'	=>	'错误信息'
	 *
	 * @var array
	 */
	protected $message = ["username.require" => "用户名必填", "api_password.alphaDash" => "api秘钥只能是汉字、字母、数字和下划线_及破折号-", "api_password.max" => "api密钥不超过32个字符", "api_password.min" => "api密钥至少6个字符"];
	protected $scene = ["api" => ["api_password"]];
}