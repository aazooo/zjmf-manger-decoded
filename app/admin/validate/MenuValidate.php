<?php

namespace app\admin\validate;

class MenuValidate extends \think\Validate
{
	/**
	 * 定义验证规则
	 * 格式：'字段名'	=>	['规则1','规则2'...]
	 *
	 * @var array
	 */
	protected $rule = ["name" => "require|unique:menu", "type" => "require|in:client,www"];
	/**
	 * 定义错误信息
	 * 格式：'字段名.规则名'	=>	'错误信息'
	 *
	 * @var array
	 */
	protected $message = ["name.require" => "菜单名称不能为空", "name.unique" => "菜单名称已存在", "type.require" => "菜单类型不能为空", "type.in" => "菜单类型参数错误"];
	public function sceneEdit()
	{
		return $this->only(["name"])->remove("name", "unique");
	}
}