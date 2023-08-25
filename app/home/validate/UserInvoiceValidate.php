<?php

namespace app\home\validate;

class UserInvoiceValidate extends \think\Validate
{
	/**
	 * 定义验证规则
	 * 格式：'字段名'	=>	['规则1','规则2'...]
	 * @var array
	 */
	protected $rule = ["page" => "integer", "size" => "between:1,50", "order" => "alphaDash|in:id,client_id,ordernum,status,pay_time,create_time,amount,payment,promo_code,invoiceid", "sort" => "alpha|in:desc,asc", "status" => "alphaDash", "create_time" => "integer", "due_time" => "integer", "payment_type" => "alpha|max:20", "notes" => "alphaDash|max:200", "uid" => "integer"];
	/**
	 * 定义错误信息
	 * 格式：'字段名.规则名'	=>	'错误信息'
	 *
	 * @var array
	 */
	protected $message = [];
}