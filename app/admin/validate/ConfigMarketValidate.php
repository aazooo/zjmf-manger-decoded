<?php

namespace app\admin\validate;

class ConfigMarketValidate extends \think\Validate
{
	protected $rule = ["shd_zjmf_finance" => "require|in:1,0", "shd_zjmf_cloud" => "require|in:1,0", "shd_zjmf_dcim" => "require|in:1,0", "shd_allow_withdraw" => "require|in:1,0", "allow_withdraw_bank" => "require|in:1,0", "allow_withdraw_alipay" => "require|in:1,0", "allow_withdraw_bank_BOC" => "require|in:1,0", "allow_withdraw_bank_ICBC" => "require|in:1,0", "allow_withdraw_bank_ABC" => "require|in:1,0", "allow_withdraw_bank_CCB" => "require|in:1,0", "allow_withdraw_bank_PSBC" => "require|in:1,0", "minimum_withdrawal_amount" => "require|float", "withdrawal_fee" => "require|float", "name" => "require|max:50", "desc" => "require|max:255", "banner" => "require", "start_time" => "require", "end_time" => "require"];
	protected $message = ["name.require" => "名称必填", "name.max" => "名称不超过50个字符", "desc.require" => "描述必填", "desc.max" => "描述不超过255个字符", "banner.require" => "banner图必选", "start_time.require" => "开始时间必填", "end_time.require" => "结束时间必填"];
	protected $scene = ["app_manage" => ["shd_zjmf_finance", "shd_zjmf_cloud", "shd_zjmf_dcim"], "withdraw_manage" => ["shd_allow_withdraw", "allow_withdraw_bank", "allow_withdraw_alipay", "allow_withdraw_bank_BOC", "allow_withdraw_bank_ICBC", "allow_withdraw_bank_ABC", "allow_withdraw_bank_CCB", "allow_withdraw_bank_PSBC", "minimum_withdrawal_amount", "withdrawal_fee"], "activity_banner" => ["name", "desc", "banner", "start_time", "end_time"]];
}