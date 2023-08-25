<?php

namespace app\home\validate;

class VoucherValidate extends \think\Validate
{
	protected $rule = ["id" => "number", "issue_type" => "require|in:person,company", "title" => "require|max:50", "voucher_type" => "require|in:common,dedicated", "tax_id" => "require|max:100", "bank" => "max:100", "account" => "max:100", "address" => "max:100", "phone" => "max:100", "username" => "require|max:100", "province" => "require|max:100", "city" => "require|max:100", "region" => "require|max:100", "detail" => "require|max:100", "post" => "max:100", "default" => "require|in:0,1"];
	protected $message = ["title.require" => "请填写发票抬头", "tax_id.require" => "请填写税务登记号"];
	protected $scene = ["voucher_info_person" => ["id", "issue_type", "title"], "voucher_info_company" => ["id", "issue_type", "title", "voucher_type", "tax_id", "bank", "account", "address", "phone"], "voucher_post" => ["id", "username", "province", "city", "region", "detail", "phone", "post", "default"]];
}