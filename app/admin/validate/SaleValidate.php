<?php

namespace app\admin\validate;

class SaleValidate extends \think\Validate
{
	protected $rule = ["group_name" => "require|max:50", "bates" => "require", "updategrade" => "require", "is_renew" => "require"];
	protected $message = ["group_name.max" => "{%SALE_GROUPNAME_MAX}"];
	protected $scene = ["sale" => ["group_name", "bates", "updategrade", "is_renew"]];
}