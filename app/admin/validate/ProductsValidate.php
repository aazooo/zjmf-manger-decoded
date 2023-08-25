<?php

namespace app\admin\validate;

class ProductsValidate extends \think\Validate
{
	protected $rule = ["id" => "require|number|checkId", "type" => "require|getProductTypeString", "gid" => "require|number|checkGid:thinkphp", "name" => "require|max:100", "welcomeemail" => "number", "hidden" => "in:1,0", "retired" => "in:1,0", "stock_control" => "in:1,0", "is_featured" => "in:1,0", "allow_qty" => "in:1,0", "qty" => "number", "prorata_billing" => "in:1,0", "prorata_date" => "number|between:1,31", "prorata_charge_next_month" => "number|between:1,31", "pay_type" => "require|in:free,onetime,recurring,day,hour", "pay_hour_status" => "in:1,0|checkPayHourCycle:thinkphp", "pay_hour_cycle" => "number", "pay_day_status" => "in:1,0|checkPayDayCycle:thinkphp", "pay_day_cycle" => "number", "pay_ontrial_status" => "in:1,0|checkPayOntrialHour:thinkphp", "pay_ontrial_cycle" => "number", "payontrial_condition" => "array|checkPayontrialCondition:thinkphp", "pay_method" => "require|in:prepayment,postpaid", "server_type" => "checkServerType:thinkphp", "packageconfigoption" => "array", "auto_setup" => "in:on,payment,order", "recurring_cycles" => "number", "auto_terminate_days" => "number", "auto_terminate_email" => "number", "config_options_upgrade" => "in:1,0", "upgradeemail" => "number", "configoptionlinks" => "array|checkConfigoptionLinks:thinkphp", "upgradepackages" => "array|checkUpgradePackages", "affiliateonetime" => "in:1,0", "host_rule_len_num" => "number|between:4,200", "host_show" => "in:0,1", "host_modify" => "in:0,1", "host_prefix" => "max:100|min:1|alphaDash", "host_rule_upper" => "in:0,1", "host_rule_lower" => "in:0,1", "password_rule_len_num" => "number|between:6,20", "host_rule_num" => "in:0,1", "host_rule_len" => "in:0,1", "password_show" => "in:0,1", "password_modify" => "in:0,1", "password_rule_len" => "in:0,1"];
	protected $message = ["id.require" => "产品ID不能为空", "host_prefix.alphaDash" => "主机名前缀只能是字母和数字，下划线_及破折号-", "host_prefix.min" => "主机名前缀不能小于1个字母", "host_prefix.max" => "主机名前缀不能大于10个字母", "password_rule_len_num.between" => "密码长度在6-20之间", "password_rule_len_num.number" => "密码长度必须是数字", "host_rule_len_num.number" => "主机名长度必须是数字", "host_rule_len_num.between" => "主机名长度在4-200之间", "id.number" => "产品ID必须为数字", "type.require" => "产品类型不能为空", "type.in" => "产品类型错误", "gid.require" => "关联产品组信息不能为空", "gid.number" => "产品组信息必须为数字", "name.require" => "产品名不能为空", "name.max" => "产品名最大不超过100个字符", "welcomeemail.number" => "开通邮件必须为数字", "hidden.in" => "产品隐藏必须是0或者1", "retired.in" => "产品下架必须是0或者1", "stock_control.in" => "库存控制开关必须是0或者1", "is_featured.in" => "特性必须是0或者1", "allow_qty.in" => "允许购买多个开关必须是0或者1", "qty.number" => "产品库存必须为数字", "prorata_billing.in" => "自定义结算日期开关必须是0或者1", "prorata_date.number" => "结算日期必须为数字", "prorata_date.between" => "结算日期范围必须在1-31日", "prorata_charge_next_month.number" => "下月结算日期必须为数字", "prorata_charge_next_month.between" => "下月结算日期范围必须在1-31日", "pay_type.in" => "付款周期错误", "pay_hour_status.in" => "按小时计费开关必须是0或者1", "pay_hour_cycle.number" => "按小时计费周期必须为数字", "pay_day_status.in" => "按天计费开关必须是0或者1", "pay_day_cycle.number" => "按天计费周期必须为数字", "pay_ontrial_status.in" => "试用开关必须是0或者1", "pay_ontrial_cycle.number" => "试用小时数量必须为数字", "pay_ontrial_condition.array" => "试用条件必须为一维数组", "pay_method.require" => "付费方式为必填", "pay_method.in" => "付费方式只支持prepayment(预付费),postpaid(后付费)", "server_group.number" => "服务器组必须为数字", "packageconfigoption.array" => "产品模块配置必须为数组", "auto_setup.in" => "模块自动化支持0,on,payment,order", "recurring_cycles.number" => "循环周期限制数量必须为数量", "auto_terminate_days.number" => "自动删除/固定周期必须为数字", "auto_terminate_email.number" => "删除邮件模板配置必须为数字", "config_options_upgrade.accepted" => "可配置选项升级开关必须是yes、on或者1", "upgradeemail.number" => "升级邮件模板配置必须为数字", "configoptionlinks.array" => "可配置选项关联数据必须为数组", "upgradepackages.array" => "可更改套餐数据必须为数组"];
	protected function getProductTypeString($value)
	{
		if (!in_array($value, array_keys(config("product_type")))) {
			return "产品类型只能是" . getProductTypeString();
		}
		return true;
	}
	protected function checkId($id)
	{
		$data = \think\Db::name("products")->where("id", $id)->select()->toArray();
		if (empty($data)) {
			return "该产品未找到";
		}
		return true;
	}
	protected function checkGid($value)
	{
		$groupData = \think\Db::name("product_groups")->where("id", $value)->select()->toArray();
		return !empty($groupData) ? true : "该产品组未找到";
	}
	protected function checkPayontrialCondition($array)
	{
		foreach ($array as $key => $value) {
			if (in_array($value, ["realname", "wechat", "phone", "email"])) {
				continue;
			} else {
				return "试用条件数据传递错误";
			}
		}
		return true;
	}
	protected function checkServerType($value)
	{
		$provision = new \app\common\logic\Provision();
		$modules = $provision->getModules();
		$modulesArr = array_column($modules, "value");
		if (in_array($value, $modulesArr)) {
			return true;
		}
		return "该模块不存在";
	}
	protected function checkServerGroup($value)
	{
		if ($value == 0) {
			return true;
		} else {
			$servergroup = \think\Db::name("server_groups")->where("id", $value)->select()->toArray();
			if (!empty($servergroup)) {
				return true;
			}
			return "该服务器组不存在";
		}
	}
	protected function checkConfigoptionLinks($configOptionArr)
	{
		foreach ($configOptionArr as $key => $value) {
			if (!is_numeric($value)) {
				return "关联可配置选项错误";
			}
		}
		return true;
	}
	protected function checkUpgradePackages($productArr)
	{
		foreach ($productArr as $item) {
			if (!is_numeric($item)) {
				return "可升级产品选项错误";
			}
		}
		return true;
	}
	protected function checkPayHourCycle($value, $rule, $data)
	{
		if (!empty($value) && empty($data["pay_hour_cycle"])) {
			return "未传入按小时计费周期";
		}
		return true;
	}
	protected function checkPayDayCycle($value, $rule, $data)
	{
		if (!empty($value) && empty($data["pay_day_cycle"])) {
			return "未传入按天计费周期";
		}
		return true;
	}
	protected function checkPayOntrialHour($value, $rule, $data)
	{
		return true;
	}
}