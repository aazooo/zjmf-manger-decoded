<?php

namespace app\admin\validate;

class OrderValidate extends \think\Validate
{
	/**
	 * 定义验证规则
	 * 格式：'字段名'    =>    ['规则1','规则2'...]
	 *
	 * @var array
	 */
	protected $rule = ["page" => "integer", "limit" => "between:1,200", "order" => "alphaDash|in:id,client_id,ordernum,status,pay_time,create_time,amount,payment,promo_code,invoiceid", "sort" => "alpha|in:desc,asc", "status" => "alpha|in:Pending,Active,Completed,Suspend,Terminated,Cancelled,Fraud", "notes" => "alphaDash|max:200"];
	/**
	 * 定义错误信息
	 * 格式：'字段名.规则名'    =>    '错误信息'
	 *
	 * @var array
	 */
	protected $message = [];
	public function sceneCreate()
	{
		return $this->only(["uid", "ordernum", "payment", "status", "amount", "promo_code", "promo_value", "invoiceid"])->append("uid", "require|integer")->append("payment", "require|alphaDash")->append("status", "require|alpha|in:Pending,Active,Completed,Suspend,Terminated,Cancelled,Fraud")->append("amount", "require|float|length:1,10")->append("promo_code", "max:100")->append("invoiceid", "require|integer|max:100");
	}
}