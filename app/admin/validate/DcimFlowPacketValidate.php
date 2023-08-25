<?php

namespace app\admin\validate;

class DcimFlowPacketValidate extends \think\Validate
{
	protected $rule = ["name" => "require|length:0,255", "capacity" => "require|number|min:1", "price" => "require|checkPrice:thinkphp", "status" => "require|in:0,1", "stock" => "number"];
	protected $message = ["name.require" => "名称必须", "name.length" => "名称长度不能超过255", "capacity.require" => "大小必须", "capacity.number" => "流量包大小格式错误", "capacity.min" => "流量包大小最小1GB", "price.require" => "价格必须", "price.checkPrice" => "价格错误", "stock.number" => "库存格式错误"];
	public function sceneEdit()
	{
		return $this->only(["name", "capacity", "price", "status", "stock"])->remove("name", "require")->remove("capacity", "require")->remove("price", "require")->remove("status", "require");
	}
	public function checkPrice($value, $rule, $data = [])
	{
		if (!is_numeric($value)) {
			return false;
		}
		if ($value < 0.01) {
			return false;
		}
		return true;
	}
}