<?php

namespace app\common\logic;

class PaymentGateways extends \think\Model
{
	protected $table = "shd_payment_gateways";
	public function getActiveGatewayArr()
	{
		$gatewayArr = $this->getSetting();
		$activeGateway = [];
		$i = 0;
		foreach ($gatewayArr as $key => $gateway) {
			if ($gateway["visible"] == "on") {
				$activeGateway[$i]["gateway"] = $key;
				$activeGateway[$i]["name"] = $gateway["name"];
				$i++;
			}
		}
		return $activeGateway;
	}
	public function getSetting()
	{
		$data = self::all();
		$gatewayArr = [];
		foreach ($data as $key => $value) {
			$gateway = $value["gateway"];
			$gatewayArr[$gateway][$value["setting"]] = $value["value"];
		}
		return $gatewayArr;
	}
}