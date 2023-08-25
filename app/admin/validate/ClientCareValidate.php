<?php

namespace app\admin\validate;

class ClientCareValidate extends \think\Validate
{
	protected $rule = ["name" => "require|max:50", "ids" => "require", "method" => "require", "time" => "integer", "range_type" => "require|in:0,1", "status" => "require|in:0,1", "mailtemp_id" => "integer", "message_id" => "integer"];
	protected $message = ["name.require" => "{%CLIENT_CARE_NAME_REQUIRE}", "name.max" => "{%CLIENT_CARE_NAME_MAX}", "ids.require" => "{%CLIENT_CARE_PRODUCT_REQUIRE}", "method.require" => "{%CLIENT_CARE_METHOD_REQUIRE}", "time.integer" => "{%CLIENT_CARE_TIME_NUMBER}"];
	protected $scene = ["create_care_product" => ["name", "ids", "method", "time", "range_type", "status", "mailtemp_id", "message_id"], "create_care_register" => ["name", "method", "time", "range_type", "status", "mailtemp_id", "message_id"]];
}