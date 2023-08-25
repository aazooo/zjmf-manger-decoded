<?php

namespace app\admin\validate;

class NavValidate extends \think\Validate
{
	/**
	 * 定义验证规则
	 * 格式：'字段名'	=>	['规则1','规则2'...]
	 *
	 * @var array
	 */
	protected $rule = ["name" => "require", "nav_type" => "in:0,1,2"];
	/**
	 * 定义错误信息
	 * 格式：'字段名.规则名'	=>	'错误信息'
	 *
	 * @var array
	 */
	protected $message = ["name.require" => "导航名称不能为空", "type.in" => "导航类型参数错误"];
	public function sceneModify()
	{
	}
}