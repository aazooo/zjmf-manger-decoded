<?php

namespace app\admin\validate;

class AdminValidate extends \think\Validate
{
	protected $rule = ["username" => "require|max:24", "password" => "require|min:6|max:32"];
	protected $message = ["username.require" => "管理员用户名不能为空", "username.max" => "管理员用户名不超过24个字符", "password.require" => "密码不能为空", "password.max" => "密码不能超过32个字符", "password.min" => "密码不能小于6个字符"];
}