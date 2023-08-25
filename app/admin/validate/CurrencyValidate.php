<?php

namespace app\admin\validate;

class CurrencyValidate extends \think\Validate
{
	protected $rule = ["code" => "require|max:50", "prefix" => "max:50", "suffix" => "max:50", "rate" => "float"];
	protected $message = ["code.max" => "{%CURRENCY_CODE_MAX}", "prefix.max" => "{%CURRENCY_PREFIX_MAX}", "suffix.max" => "{%CURRENCY_SUFFIX_MAX}", "rate.float" => "{%RATE_MUST_NUMBER}"];
	protected $scene = ["add_currency" => ["code", "prefix", "suffix", "rate"]];
}