<?php

namespace app\admin\validate;

class ContractValidate extends \think\Validate
{
	protected $rule = ["name" => "require|max:50", "status" => "require|in:0,1", "notes" => "require|in:0,1,2", "force" => "require|in:0,1", "represent" => "max:50", "phonenumber" => "number|max:50", "remark" => "max:500", "content" => "require", "is_post" => "require|in:0,1", "nocheck" => "require|in:0,1", "inscribe_custom" => "require|in:0,1", "base" => "require|in:0,1", "express_company" => "max:255", "express_order" => "max:255", "email" => "max:255"];
	protected $message = ["name.require" => "{%CONTRACT_NAME_REQUIRE}", "name.max" => "{%CONTRACT_NAME_MAX}", "represent.require" => "{%CONTRACT_REPRESENT_REQUIRE}", "represent.max" => "{%CONTRACT_REPRESENT_MAX}", "phonenumber.require" => "{%CONTRACT_PHONENUMBER_REQUIRE}", "phonenumber.number" => "{%CONTRACT_PHONENUMBER_NUMBER}", "phonenumber.max" => "{%CONTRACT_PHONENUMBER_MAX}", "remark.max" => "{%CONTRACT_REMARK_MAX}", "content.require" => "{%CONTRACT_CONTENT_REQUIRE}"];
	protected $scene = ["tpl" => ["name", "status", "force", "represent", "phonenumber", "remark", "content", "suspended", "inscribe_custom", "base", "email"], "post" => ["is_post", "express_company", "express_order"]];
}