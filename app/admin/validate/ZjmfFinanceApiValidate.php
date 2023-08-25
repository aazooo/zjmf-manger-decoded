<?php

namespace app\admin\validate;

class ZjmfFinanceApiValidate extends \think\Validate
{
	protected $rule = ["name" => "require|unique:zjmf_finance_api", "hostname" => "require|checkHostname:thinkphp", "username" => "require", "password" => "require"];
	protected $message = ["name.require" => "名称不能为空", "name.unique" => "名称已存在", "hostname.require" => "接口地址不能为空", "hostname.checkHostname" => "接口地址必须以http://或https://开头", "username.require" => "用户名不能为空", "password.require" => "密码不能为空"];
	public function sceneEdit()
	{
		return $this->only(["name", "hostname", "username", "password"])->remove("name", "unique")->remove("hostname", "unique");
	}
	public function checkHostname($value)
	{
		if (strpos($value, "https://") !== 0 && strpos($value, "http://") !== 0) {
			return false;
		}
		return true;
	}
}