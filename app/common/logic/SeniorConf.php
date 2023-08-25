<?php

namespace app\common\logic;

class SeniorConf
{
	private $relation = ["seq" => 1, "sneq" => 2];
	private $relation_text = ["seq" => " =只允许=", "sneq" => " =只允许="];
	/**
	 * @title 获取所有开启的高级配置-因/果 对应数据
	 * @param $product_id 产品id
	 * @return array|array[]|\array[][]
	 */
	private function getAllConf($product_id)
	{
		$SeniorConfModel = new \app\common\model\SeniorConfModel();
		return $SeniorConfModel->getProductUseConfLinksList($product_id);
	}
	/**
	 * @title 检测所有配置项是否通过
	 * @param $product_id 产品id
	 * @param $conf 配置项组 结构为 $conf['configoption'][配置项id] = 配置项值;
	 * @return bool|string|null
	 */
	public function checkConf($product_id, $conf)
	{
		$response = $this->getAllConf($product_id);
		if (empty($response)) {
			return false;
		}
		foreach ($response as $k => $res) {
			$result_value = $conf[$res["config_id"]];
			$res_condition = $this->checkOne($res, $result_value, $this->relation[$res["relation"]]);
			if ($res_condition["final"]) {
				$res_r = $res["result"][0];
				if (is_array($res_r) && count($res_r) > 0 && $res_r["config_id"] > 0) {
					$sub_id = true;
					foreach ($res_r["sub_id"] as $sub_id_k => $sub_id_v) {
						if (empty($sub_id_k)) {
							$sub_id = false;
						}
					}
					if ($sub_id) {
						$result_value_r = $conf[$res_r["config_id"]];
						$res_result = $this->checkOne($res_r, $result_value_r, $this->relation[$res_r["relation"]]);
						if (!$res_result["final"]) {
							return $res_condition["describe"] . $this->relation_text[$res_r["relation"]] . $res_result["describe"];
						}
					}
				}
			}
		}
		return null;
	}
	/**
	 * @title 检验传入值是否通过条件
	 * @param $condition 条件
	 * @param $result_value 传入值
	 * @param $relation 匹配类型
	 * @return array
	 */
	private function checkOne($condition, $result_value, $relation)
	{
		$final = false;
		$condition_info = $condition["sub_id"];
		$condition_info_copy = $condition_info;
		$condition_one = array_pop($condition_info_copy);
		$final_sum = array_sum(array_values($condition_one));
		$describe = "";
		$SeniorConfModel = new \app\common\model\SeniorConfModel();
		$config = $SeniorConfModel->getProductConfOne($condition["config_id"]);
		$config_name = array_pop(explode("|", $config["option_name"]));
		$sub_ids = array_keys($condition_info);
		$sub_names = $SeniorConfModel->getProductUseConfSubNames($sub_ids);
		array_walk($sub_names, function (&$sub_names_v) {
			$sub_names_v = array_pop(explode("|", $sub_names_v));
		});
		$sub_name = implode(",", $sub_names);
		switch ($relation) {
			case 1:
				if ($final_sum) {
					$describe = "【" . $config_name . "】在" . $condition_one["qty_minimum"] . "~" . $condition_one["qty_maximum"];
					$final = $condition_one["qty_minimum"] <= $result_value && $result_value <= $condition_one["qty_maximum"] ? true : false;
				} else {
					$describe = "【" . $config_name . "】在" . $sub_name;
					$final = in_array($result_value, array_keys($condition_info));
				}
				break;
			case 2:
				if ($final_sum) {
					$describe = "【" . $config_name . "】不在" . $condition_one["qty_minimum"] . "~" . $condition_one["qty_maximum"];
					$final = $result_value < $condition_one["qty_minimum"] && $condition_one["qty_maximum"] < $result_value ? true : false;
				} else {
					$describe = "【" . $config_name . "】不在" . $sub_name;
					$final = !in_array($result_value, array_keys($condition_info));
				}
				break;
		}
		return ["final" => $final, "describe" => $describe];
	}
	/**
	 * @title 获取所有开启的高级配置-因/果 交换后对应数据
	 * @param $product_id 产品id
	 * @return array|array[]|\array[][]
	 */
	private function getAllConfExchange($product_id)
	{
		$SeniorConfModel = new \app\common\model\SeniorConfModel();
		return $SeniorConfModel->getProductUseConfLinksListExchange($product_id);
	}
	/**
	 * @title 过滤可用单独配置项（使用场景：其他项无法修改，只能变更一项时）--用于系统重装【未支持不明确范围的拖动条类型配置】
	 * @param $product_id
	 * @param $conf
	 * @param null $check_id
	 * @param array $data
	 * @return bool|null
	 */
	public function aloneCheckConf($product_id, $conf, $check_id = null, &$data = [])
	{
		$response = $this->getAllConf($product_id);
		$responseExchange = $this->getAllConfExchange($product_id);
		if (empty($response)) {
			return false;
		}
		foreach ($response as $k => $res) {
			$result_value = $conf[$res["config_id"]];
			$res_condition = $this->aloneCheckOne($res, $result_value, $this->relation[$res["relation"]]);
			if ($res_condition["final"]) {
				$res_r = $res["result"][0];
				$result_value_r = $conf[$res_r["config_id"]];
				$res_result = $this->aloneCheckOne($res_r, $result_value_r, $this->relation[$res_r["relation"]], $check_id, $data);
			}
		}
		foreach ($responseExchange as $k => $res) {
			$result_value = $conf[$res["config_id"]];
			$res_condition = $this->aloneCheckOne($res, $result_value, $this->relation[$res["relation"]]);
			if ($res_condition["final"]) {
				$res_r = $res["result"][0];
				$result_value_r = $conf[$res_r["config_id"]];
				$res_result = $this->aloneCheckOne($res_r, $result_value_r, $this->relation[$res_r["relation"]], $check_id, $data);
			}
		}
		return null;
	}
	/**
	 * @title 检验传入值是否通过条件 - 单项配置
	 * @param $condition 条件
	 * @param $result_value 结果值
	 * @param $relation 匹配类型
	 * @return array
	 */
	private function aloneCheckOne($condition, $result_value, $relation, $check_id = null, &$data = [])
	{
		$final = false;
		$condition_info = $condition["sub_id"];
		$condition_info_copy = $condition_info;
		$condition_one = array_pop($condition_info_copy);
		$final_sum = array_sum(array_values($condition_one));
		$self = $check_id == $condition["config_id"] ? true : false;
		switch ($relation) {
			case 1:
				if ($final_sum) {
					$final = $condition_one["qty_minimum"] <= $result_value && $result_value <= $condition_one["qty_maximum"] ? true : false;
				} else {
					$final = in_array($result_value, array_keys($condition_info));
					if ($self) {
						$data = array_values(array_unique(array_intersect($data, array_keys($condition_info))));
					}
				}
				break;
			case 2:
				if ($final_sum) {
					$final = $result_value < $condition_one["qty_minimum"] || $condition_one["qty_maximum"] < $result_value ? true : false;
				} else {
					$final = !in_array($result_value, array_keys($condition_info));
					if ($self) {
						$data = array_values(array_unique(array_diff($data, array_keys($condition_info))));
					}
				}
				break;
		}
		return ["final" => $final];
	}
}