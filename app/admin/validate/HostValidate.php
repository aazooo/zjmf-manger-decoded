<?php

namespace app\admin\validate;

class HostValidate extends \think\Validate
{
	protected $rule = ["hostid" => "require|number", "oldproductid" => "require|number", "productid" => "require|number", "regdate" => "require|number", "firstpaymentamount" => "float", "serverid" => "number", "amount" => "float", "oldnextduedate" => "require|number", "nextduedate" => "require|number", "termination_date" => "number", "billingcycle" => "require|in:onetime,hour,day,ontrial,monthly,quarterly,semiannually,annually,biennially,triennially,fourly,fively,sixly,sevenly,eightly,ninely,tenly", "payment" => "require", "domainstatus" => "require|in:Pending,Active,Completed,Suspended,Terminated,Cancelled,Fraud", "promoid" => "number", "dedicatedip" => "ip", "overideautosuspend" => "in:0,1", "auto_terminate_end_cycle" => "in:1,0", "configoption" => "array", "customfield" => "array"];
	protected $message = ["hostid.require" => "主机id不能为空", "hostid.number" => "主机id必须为数字", "regdate.require" => "开通时间不能为空", "firstpaymentamount.float" => "首付金额错误", "amount.float" => "续费金额错误", "oldnextduedate.require" => "到期时间不能为空", "oldnextduedate.number" => "到期时间需要为时间戳", "nextduedate.require" => "到期时间不能为空", "nextduedate.number" => "到期时间需要为时间戳", "termination_date.number" => "终止日期错误", "billingcycle.require" => "付款周期必填", "billingcycle.in" => "付款周期错误", "payment.require" => "支付方式不能为空", "domainstatus.require" => "主机状态不能为空", "domainstatus.in" => "主机状态错误", "promoid.number" => "优惠码错误", "dedicatedip.ip" => "独立ip格式错误"];
}