<?php

namespace app\admin\validate;

class DcimServerValidate extends \think\Validate
{
	protected $rule = ["name" => "require|length:0,255", "hostname" => "require", "port" => "number|between:0,65535", "secure" => "in:0,1", "disabled" => "in:0,1", "reinstall_times" => "number", "buy_times" => "in:0,1", "reinstall_price" => "checkPrice:thinkphp", "bill_type" => "in:month,last_30days", "traffic" => "in:on,off", "kvm" => "in:on,off", "ikvm" => "in:on,off", "bmc" => "in:on,off", "reinstall" => "in:on,off", "reboot" => "in:on,off", "on" => "in:on,off", "off" => "in:on,off", "novnc" => "in:on,off", "rescue" => "in:on,off", "crack_pass" => "in:on,off", "user_prefix" => "length:0,32"];
	protected $message = ["name.require" => "名称必须", "name.length" => "名称不能超过255", "hostname.require" => "地址必须", "port.number" => "端口格式错误", "port.between" => "端口格式错误", "secure.in" => "参数错误", "disabled.in" => "参数错误", "reinstall_times.number" => "重装次数格式错误", "buy_times.in" => "参数错误", "reinstall_price.checkPrice" => "价格错误", "traffic.in" => "参数错误", "kvm.in" => "参数错误", "ikvm.in" => "参数错误", "bmc.in" => "参数错误", "reinstall.in" => "参数错误", "reboot.in" => "参数错误", "on.in" => "参数错误", "off.in" => "参数错误", "novnc.in" => "参数错误", "rescue.in" => "参数错误", "crack_pass.in" => "参数错误", "user_prefix.length" => "用户前缀不能超过32个字"];
	public function checkPrice($value, $rule, $data = [])
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