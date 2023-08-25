<?php

namespace app\admin\validate;

class InvoiceValidate extends \think\Validate
{
	/**
	 * 定义验证规则
	 * 格式：'字段名'    =>    ['规则1','规则2'...]
	 * @var array
	 */
	protected $rule = ["page" => "integer", "limit" => "between:1,50", "order" => "in:id,status,create_time,due_time,subtotal,credit,invoiceid,paid_time,amount,payment", "sort" => "alpha|in:desc,asc", "status" => "alpha|in:Paid,Unpaid,Cancelled,Refunded,Draft,Overdue,Collections", "create_time" => "integer", "due_time" => "integer", "payment_type" => "alpha|max:20", "notes" => "alphaDash|max:200", "trans_id" => "alphaDash|max:200", "pay_time" => "integer", "amount" => "float|egt:fees", "fees" => "float|egt:0", "email" => "in:0,1", "invoice_num" => "alphaDash|max:200", "taxrate" => "float|egt:0|elt:1", "taxrate2" => "float|egt:0|elt:1"];
	/**
	 * 定义错误信息
	 * 格式：'字段名.规则名'    =>    '错误信息'
	 *
	 * @var array
	 */
	protected $message = [];
	protected $scene = ["pay" => ["pay_time", "trans_is", "amount", "fees", "email"], "option" => ["create_time", "invoice_num", "due_time", "taxrate", "taxrate2", "status"], "notes" => ["notes"]];
	public function sceneCreate()
	{
		return $this->only(["uid", "payment", "promo_code", "status", "amount", "promo_value", "invoiceid"])->append("uid", "require|integer")->append("payment", "require|alphaDash")->append("promo_code", "max:100")->append("status", "require|alpha|in:Pending,Active,Completed,Suspend,Terminated,Cancelled,Fraud")->append("amount", "require|float|length:1,10")->append("invoiceid", "require|integer|max:100");
	}
}