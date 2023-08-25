<?php

namespace app\admin\validate;

class AccountValidate extends \think\Validate
{
	/**
	 * 定义验证规则
	 * 格式：'字段名'	=>	['规则1','规则2'...]
	 *
	 * @var array
	 */
	protected $rule = ["page" => "integer", "limit" => "between:1,30", "sort" => "alpha|in:desc,asc", "currency" => "alpha|length:3", "gateway" => "chsDash|max:50", "description" => "max:300", "amount_in" => "float", "fees" => "float", "amount_out" => "float", "uid" => "integer", "refund" => "integer|in:0,1"];
	/**
	 * 定义错误信息
	 * 格式：'字段名.规则名'	=>	'错误信息'
	 *
	 * @var array
	 */
	protected $message = [];
	protected $scene = ["save" => ["name"]];
	public function sceneSave()
	{
		return $this->append("pay_time", "require")->append("uid", "require|integer");
	}
}