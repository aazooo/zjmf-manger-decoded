<?php

namespace app\admin\validate;

class ConfigOptionsValidate extends \think\Validate
{
	protected $rule = ["option_name" => "require|max:50", "option_type" => "require", "qty_minimum" => "number", "qty_maximum" => "number|egt:qty_minimum", "notes" => "max:5000"];
	protected $message = ["option_name.require" => "{%CONFIG_OPITON_NAME_REQUIRE}", "option_name.max" => "{%CONFIG_OPITON_NAME_MAX}", "option_type.require" => "{%CONFIG_OPITON_TYPE_REQUIRE}", "option_type.number" => "{%CONFIG_OPITON_TYPE_NUMBER}", "qty_minimum.number" => "{%CONFIG_QTY_MINIMUM_NUMBER}", "qty_maximum.number" => "{%CONFIG_QTY_MAXIMUM_NUMBER}", "qty_maximum.egt" => "{%CONFIG_QTY_MAXIMUM_EGT_QTY_MINIMUM}", "notes.max" => "备注不超过5000个字符"];
	protected $scene = ["add_config_option" => ["option_name", "option_type", "notes"], "add_config_option_sub" => ["option_name"], "config_option" => ["option_name", "option_type", "notes"], "config_suboption" => ["qty_minimum", "qty_maximum"]];
}