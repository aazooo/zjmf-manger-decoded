<?php

namespace app\common\logic;

class Currencies
{
	public function getCurrencies($field = "*", $currency = "")
	{
		$currencies = \think\Db::name("currencies")->field($field)->where(function (\think\db\Query $query) use($currency) {
			if (!empty($currency) && is_int($currency)) {
				$query->where("id", $currency);
			}
		})->select()->toArray();
		return $currencies;
	}
}