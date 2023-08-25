<?php

namespace app\home\validate;

class RechargeValidate extends \think\Validate
{
	/**
	 * 定义验证规则
	 * 格式：'字段名'	=>	['规则1','规则2'...]
	 *
	 * @var array
	 */
	protected $rule = ["payment|支付类型" => "alphaDash|require", "amount|充值金额" => "float|gt:0"];
	/**
	 * 定义错误信息
	 * 格式：'字段名.规则名'	=>	'错误信息'
	 *
	 * @var array
	 */
	protected $message = ["amount.gt" => "充值金额需大于0"];
}