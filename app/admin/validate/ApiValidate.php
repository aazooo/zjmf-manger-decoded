<?php

namespace app\admin\validate;

class ApiValidate extends \think\Validate
{
	protected $rule = ["username" => "require", "password" => "require", "ip" => "require"];
	protected $message = ["username.require" => "用户名必须", "password.require" => "密码必须", "ip.require" => "IP必须"];
}