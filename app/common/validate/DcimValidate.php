<?php

namespace app\common\validate;

class DcimValidate extends \think\Validate
{
	protected $rule = ["os" => "require|number", "action" => "in:0,1", "password" => "require", "port" => "require|between:0,65535", "part_type" => "in:0,1", "disk" => "number", "check_disk_size" => "in:0,1"];
	protected $message = ["os.require" => "操作系统必须", "os.number" => "操作系统错误", "action.in" => "分区错误", "password.require" => "密码必须", "port.require" => "端口必须", "port.between" => "端口错误", "part_type.in" => "分区类型错误", "disk.number" => "磁盘错误", "check_disk_size.in" => "参数错误"];
}