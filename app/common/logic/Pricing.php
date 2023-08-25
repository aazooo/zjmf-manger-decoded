<?php

namespace app\common\logic;

class Pricing
{
	public function getUpdateOrInsertData($value)
	{
		$data = ["osetupfee" => round(floatval($value["osetupfee"]), 2), "hsetupfee" => round(floatval($value["hsetupfee"]), 2), "dsetupfee" => round(floatval($value["dsetupfee"]), 2), "ontrialfee" => round(floatval($value["ontrialfee"]), 2), "msetupfee" => round(floatval($value["msetupfee"]), 2), "qsetupfee" => round(floatval($value["qsetupfee"]), 2), "ssetupfee" => round(floatval($value["ssetupfee"]), 2), "asetupfee" => round(floatval($value["asetupfee"]), 2), "bsetupfee" => round(floatval($value["bsetupfee"]), 2), "tsetupfee" => round(floatval($value["tsetupfee"]), 2), "foursetupfee" => round(floatval($value["foursetupfee"]), 2), "fivesetupfee" => round(floatval($value["fivesetupfee"]), 2), "sixsetupfee" => round(floatval($value["sixsetupfee"]), 2), "sevensetupfee" => round(floatval($value["sevensetupfee"]), 2), "eightsetupfee" => round(floatval($value["eightsetupfee"]), 2), "ninesetupfee" => round(floatval($value["ninesetupfee"]), 2), "tensetupfee" => round(floatval($value["tensetupfee"]), 2), "onetime" => round(floatval($value["onetime"]), 2) >= 0 ? round(floatval($value["onetime"]), 2) : -1.0, "hour" => round(floatval($value["hour"]), 2) >= 0 ? round(floatval($value["hour"]), 2) : -1.0, "day" => round(floatval($value["day"]), 2) >= 0 ? round(floatval($value["day"]), 2) : -1.0, "ontrial" => round(floatval($value["ontrial"]), 2) >= 0 ? round(floatval($value["ontrial"]), 2) : -1.0, "monthly" => round(floatval($value["monthly"]), 2) >= 0 ? round(floatval($value["monthly"]), 2) : -1.0, "quarterly" => round(floatval($value["quarterly"]), 2) >= 0 ? round(floatval($value["quarterly"]), 2) : -1.0, "semiannually" => round(floatval($value["semiannually"]), 2) >= 0 ? round(floatval($value["semiannually"]), 2) : -1.0, "annually" => round(floatval($value["annually"]), 2) >= 0 ? round(floatval($value["annually"]), 2) : -1.0, "biennially" => round(floatval($value["biennially"]), 2) >= 0 ? round(floatval($value["biennially"]), 2) : -1.0, "triennially" => round(floatval($value["triennially"]), 2) >= 0 ? round(floatval($value["triennially"]), 2) : -1.0, "fourly" => round(floatval($value["fourly"]), 2) >= 0 ? round(floatval($value["fourly"]), 2) : -1.0, "fively" => round(floatval($value["fively"]), 2) >= 0 ? round(floatval($value["fively"]), 2) : -1.0, "sixly" => round(floatval($value["sixly"]), 2) >= 0 ? round(floatval($value["sixly"]), 2) : -1.0, "sevenly" => round(floatval($value["sevenly"]), 2) >= 0 ? round(floatval($value["sevenly"]), 2) : -1.0, "eightly" => round(floatval($value["eightly"]), 2) >= 0 ? round(floatval($value["eightly"]), 2) : -1.0, "ninely" => round(floatval($value["ninely"]), 2) >= 0 ? round(floatval($value["ninely"]), 2) : -1.0, "tenly" => round(floatval($value["tenly"]), 2) >= 0 ? round(floatval($value["tenly"]), 2) : -1.0];
		return $data;
	}
	public static function addToConfig($relid, $type = "configoptions", $currency = 1)
	{
		if (empty($relid)) {
			return ["status" => "error", "msg" => "缺少参数"];
		}
		$data = [];
		$data["type"] = $type;
		$data["currency"] = $currency;
		$data["relid"] = $relid;
		$data["osetupfee"] = 0.0;
		$data["hsetupfee"] = 0.0;
		$data["dsetupfee"] = 0.0;
		$data["ontrialfee"] = 0.0;
		$data["msetupfee"] = 0.0;
		$data["qsetupfee"] = 0.0;
		$data["ssetupfee"] = 0.0;
		$data["asetupfee"] = 0.0;
		$data["bsetupfee"] = 0.0;
		$data["tsetupfee"] = 0.0;
		$data["foursetupfee"] = 0.0;
		$data["fivesetupfee"] = 0.0;
		$data["sixsetupfee"] = 0.0;
		$data["sevensetupfee"] = 0.0;
		$data["eightsetupfee"] = 0.0;
		$data["ninesetupfee"] = 0.0;
		$data["tensetupfee"] = 0.0;
		$data["onetime"] = 0.0;
		$data["hour"] = 0.0;
		$data["day"] = 0.0;
		$data["ontrial"] = 0.0;
		$data["monthly"] = 0.0;
		$data["quarterly"] = 0.0;
		$data["semiannually"] = 0.0;
		$data["annually"] = 0.0;
		$data["biennially"] = 0.0;
		$data["triennially"] = 0.0;
		$data["fourly"] = 0.0;
		$data["fively"] = 0.0;
		$data["sixly"] = 0.0;
		$data["sevenly"] = 0.0;
		$data["eightly"] = 0.0;
		$data["ninely"] = 0.0;
		$data["tenly"] = 0.0;
		\think\Db::name("pricing")->insert($data);
		return "success";
	}
	public static function add($relid, $type = "product", $currency = 1)
	{
		if (empty($relid)) {
			return ["status" => "error", "msg" => "缺少参数"];
		}
		$data = [];
		$data["type"] = $type;
		$data["currency"] = $currency;
		$data["relid"] = $relid;
		$data["osetupfee"] = 0.0;
		$data["hsetupfee"] = 0.0;
		$data["dsetupfee"] = 0.0;
		$data["ontrialfee"] = 0.0;
		$data["msetupfee"] = 0.0;
		$data["qsetupfee"] = 0.0;
		$data["ssetupfee"] = 0.0;
		$data["asetupfee"] = 0.0;
		$data["bsetupfee"] = 0.0;
		$data["tsetupfee"] = 0.0;
		$data["foursetupfee"] = 0.0;
		$data["fivesetupfee"] = 0.0;
		$data["sixsetupfee"] = 0.0;
		$data["sevensetupfee"] = 0.0;
		$data["eightsetupfee"] = 0.0;
		$data["ninesetupfee"] = 0.0;
		$data["tensetupfee"] = 0.0;
		$data["onetime"] = -1.0;
		$data["hour"] = -1.0;
		$data["day"] = -1.0;
		$data["ontrial"] = -1.0;
		$data["monthly"] = -1.0;
		$data["quarterly"] = -1.0;
		$data["semiannually"] = -1.0;
		$data["annually"] = -1.0;
		$data["biennially"] = -1.0;
		$data["triennially"] = -1.0;
		$data["fourly"] = -1.0;
		$data["fively"] = -1.0;
		$data["sixly"] = -1.0;
		$data["sevenly"] = -1.0;
		$data["eightly"] = -1.0;
		$data["ninely"] = -1.0;
		$data["tenly"] = -1.0;
		\think\Db::name("pricing")->insert($data);
		return "success";
	}
	public function save($relid, $type = "product", $paramData = [])
	{
		if (empty($relid)) {
			return ["status" => "error", "msg" => "缺少参数"];
		}
		if (is_array($paramData) && !empty($paramData)) {
			foreach ($paramData as $key => $value) {
				$currency = $key;
				$update = $this->getUpdateOrInsertData($value);
				$pricing = \think\Db::name("pricing")->field("id")->where("type", $type)->where("relid", $relid)->where("currency", $currency)->find();
				if (!empty($pricing["id"])) {
					\think\Db::name("pricing")->where("id", $pricing["id"])->update($update);
				} else {
					$update["relid"] = $relid;
					$update["currency"] = $currency;
					$update["type"] = $type;
					\think\Db::name("pricing")->insert($update);
				}
			}
		}
		return ["status" => "success"];
	}
	public function getPricing($relid, $type, $field = "*", $except = false, $currency = "")
	{
		if (is_int($currency)) {
			$pricing_data = \think\Db::name("pricing")->field($field, $except)->where("relid", $relid)->where("type", $type)->where("currency", $currency)->select()->toArray();
		} else {
			$pricing_data = \think\Db::name("pricing")->field($field, $except)->where("relid", $relid)->where("type", $type)->select()->toArray();
		}
		return $pricing_data;
	}
	public function delete($relid, $type = "product")
	{
		\think\Db::name("pricing")->where(["type" => $type, "relid" => $relid])->delete();
		return ["status" => "success"];
	}
	public function calculatedPrice($hostid, $compute_cycle = "")
	{
		$host_data = \think\Db::name("host")->field("id,uid,productid,billingcycle,promoid")->where("id", $hostid)->find();
		$uid = $host_data["uid"];
		$currency = getUserCurrency($uid);
		$currencyid = $currency["id"];
		$pid = $host_data["productid"];
		$billingcycle = $host_data["billingcycle"];
		if ($compute_cycle) {
			$billingcycle = $compute_cycle;
		}
		if ($billingcycle == "free") {
			return 0.0;
		}
		$promoid = $host_data["promoid"];
		$price_type = config("price_type");
		$itself_price = $price_type[$billingcycle][0];
		$price_cycle = 0.0;
		$host_config_options = \think\Db::name("host_config_options")->where("relid", $hostid)->select()->toArray();
		$product_pricing = \think\Db::name("pricing")->where("type", "product")->where("relid", $pid)->where("currency", $currencyid)->find();
		if ($product_pricing[$billingcycle] < 0) {
			return json(["status" => 400, "msg" => "错误的购买周期"]);
		}
		if ($product_pricing[$itself_price] > 0) {
			$price_cycle += $product_pricing[$itself_price];
		}
		$configgroups = \think\Db::name("products")->alias("p")->field("pcg.id,pcg.name,pcg.description")->join("product_config_links pcl", "pcl.pid = p.id")->join("product_config_groups pcg", "pcg.id = pcl.gid")->where("p.id", $pid)->select()->toArray();
		$alloption = [];
		foreach ($configgroups as $ckey => $configgroup) {
			if (!empty($configgroup)) {
				$gid = $configgroup["id"];
				$options = \think\Db::name("product_config_options")->where("gid", $gid)->select()->toArray();
				foreach ($options as $okey => $option) {
					$cid = $option["id"];
					$option_type = $option["option_type"];
					$option_qty_minimum = $option["qty_minimum"];
					$option_qty_maximum = $option["qty_maximum"];
					$user_config_data = \think\Db::name("host_config_options")->where("relid", $hostid)->where("configid", $cid)->find();
					if (empty($user_config_data)) {
						continue;
					}
					$qty = $user_config_data["qty"];
					$optionid = $user_config_data["optionid"];
					$config_pricing = \think\Db::name("pricing")->where("type", "configoptions")->where("relid", $optionid)->where("currency", $currencyid)->find();
					if ($config_pricing[$itself_price] < 0) {
						continue;
					}
					if (judgeQuantity($option_type)) {
						if (judgeQuantityStage($option_type)) {
							$sum = quantityStagePrice($option["id"], $currency, $qty, $billingcycle);
							$price_cycle += $sum[0];
						} else {
							$price_cycle += $config_pricing[$itself_price] * $qty;
						}
					} else {
						$price_cycle += $config_pricing[$itself_price];
					}
				}
			}
		}
		return $price_cycle >= 0 ? $price_cycle : 0;
	}
}