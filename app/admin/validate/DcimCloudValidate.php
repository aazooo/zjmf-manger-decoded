<?php

namespace app\admin\validate;

class DcimCloudValidate extends \think\Validate
{
	protected $rule = ["name" => "require|length:0,255", "hostname" => "require", "port" => "number|between:0,65535", "secure" => "in:0,1", "disabled" => "in:0,1", "username" => "length:0,256", "password" => "length:0,256", "reinstall_times" => "number", "buy_times" => "in:0,1", "reinstall_price" => "checkPrice:thinkphp", "user_prefix" => "length:0,32", "account_type" => "in:admin,agent"];
	protected $message = ["name.require" => "名称必须", "name.length" => "名称不能超过255", "hostname.require" => "地址必须", "port.number" => "端口格式错误", "port.between" => "端口格式错误", "secure.in" => "参数错误", "disabled.in" => "参数错误", "reinstall_times.number" => "重装次数格式错误", "buy_times.in" => "参数错误", "reinstall_price.checkPrice" => "价格错误", "user_prefix.length" => "用户前缀不能超过32个字", "account_type.in" => "账号类型参数错误"];
	public function sceneServer()
	{
		return $this->only(["name", "hostname", "port", "secure", "disabled", "reinstall_times", "buy_times", "reinstall_price", "user_prefix", "account_type"]);
	}
	public function checkPrice($value)
	{
		if (!is_numeric($value)) {
			return false;
		}
		if ($value < 0) {
			return false;
		}
		return true;
	}
}