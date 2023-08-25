<?php

Lang::load(APP_PATH . "admin/lang/zh-cn.php");
/**
 * 获取插件列表
 * @param .type int 插件类型1:原插件,2:支付网关,3其他模块
 * @return \think\response\Json
 */
function get_modules($type = "plugin")
{
	$model = new \app\admin\model\PluginModel();
	$plugins = $model->getList($type);
	return $plugins;
}
/**
 * 获取订单统一流水号
 */
function get_order_random_num()
{
	return round(microtime(true), 4) * 10000 . mt_rand(1000, 9999);
}
/**
 * 获取国家配置信息
 * @param bool $order 是否排序
 * @param string $sort_key 排序字段
 * @param string $sort_value 升降序：SORT_DESC,SORT_ASC
 */
function getCountryConfig($order = false, $sort_key = "sort", $sort_value = "SORT_DESC")
{
	$country = config("country.country");
	if ($order) {
		array_multisort(array_column($country, $sort_key), SORT_DESC, $country);
	}
	return $country;
}
function zjmf_public_decrypt($encryptData, $public_key)
{
	$crypted = "";
	foreach (str_split(base64_decode($encryptData), 256) as $chunk) {
		openssl_public_decrypt($chunk, $decryptData, $public_key);
		$crypted .= $decryptData;
	}
	return $crypted;
}