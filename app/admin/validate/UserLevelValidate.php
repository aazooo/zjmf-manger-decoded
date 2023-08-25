<?php

namespace app\admin\validate;

class UserLevelValidate extends \think\Validate
{
	protected $rule = ["id" => "require|checkId", "level_name" => "require|max:50", "expense_min" => "float|egt:0", "expense_max" => "float|egt:expense_min", "buy_num_min" => "number|egt:0", "buy_num_max" => "number|egt:buy_num_min", "login_times_min" => "number|egt:0", "login_times_max" => "number|egt:login_times_min", "last_login_times_min" => "number|egt:0", "last_login_times_max" => "number|egt:last_login_times_min", "last_login_times_day" => "number|egt:0", "renew_times_min" => "number|egt:0", "renew_times_max" => "number|egt:renew_times_min", "last_renew_times_min" => "number|egt:0", "last_renew_times_max" => "number|egt:last_renew_times_max", "last_renew_times_day" => "number|egt:0"];
	protected $message = ["id.require" => "规则ID不能为空", "id.checkId" => "规则不存在", "level_name.require" => "请填写客户等级", "level_name.max" => "客户等级不超过50个字符"];
	protected $scene = ["edit" => ["id", "level_name", "expense_min", "expense_min", "expense_max", "buy_num_min", "buy_num_max", "login_times_min", "login_times_max", "last_login_times_min", "last_login_times_max", "last_login_times_day", "renew_times_min", "renew_times_max", "last_renew_times_min", "last_renew_times_max", "last_renew_times_day"], "create" => ["level_name", "expense_min", "expense_min", "expense_max", "buy_num_min", "buy_num_max", "login_times_min", "login_times_max", "last_login_times_min", "last_login_times_max", "last_login_times_day", "renew_times_min", "renew_times_max", "last_renew_times_min", "last_renew_times_max", "last_renew_times_day"]];
	protected function checkId($value)
	{
		$tmp = \think\Db::name("clients_level_rule")->where("id", intval($value))->find();
		if (empty($tmp)) {
			return false;
		} else {
			return true;
		}
	}
}