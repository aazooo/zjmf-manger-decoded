<?php

namespace itxq\apidoc\lib;

/**
 * 工具类
 * Class Tools
 * @package itxq\apidoc\lib
 */
class Tools
{
	/**
	 * 下划线命名转驼峰命名
	 * @param $str - 下划线命名字符串
	 * @param $isFirst - 是否为大驼峰（即首字母也大写）
	 * @return mixed
	 */
	public static function underlineToHump($str, $isFirst = false)
	{
		$str = preg_replace_callback("/([\\-\\_]+([a-z]{1}))/i", function ($matches) {
			return strtoupper($matches[2]);
		}, $str);
		if ($isFirst) {
			$str = ucfirst($str);
		}
		return $str;
	}
	/**
	 * 驼峰命名转下划线命名
	 * @param $str
	 * @return mixed
	 */
	public static function humpToUnderline($str)
	{
		$str = preg_replace_callback("/([A-Z]{1})/", function ($matches) {
			return "_" . strtolower($matches[0]);
		}, $str);
		$str = preg_replace("/^\\_/", "", $str);
		return $str;
	}
	/**
	 * 获取数组、对象下标对应值，不存在时返回指定的默认值
	 * @param string|integer $name - 下标（键名）
	 * @param array|object $data - 原始数组/对象
	 * @param mixed $default - 指定默认值
	 * @return mixed
	 */
	public static function getSubValue($name, $data, $default = "")
	{
		if (is_object($data)) {
			$value = isset($data->{$name}) ? $data->{$name} : $default;
		} else {
			if (is_array($data)) {
				$value = isset($data[$name]) ? $data[$name] : $default;
			} else {
				$value = $default;
			}
		}
		return $value;
	}
	/**
	 * 文件下载
	 * @param string - $docHtml - API文档HTML内容
	 */
	public static function downloadFile($docHtml)
	{
		set_time_limit(0);
		header("Content-type: application/octet-stream");
		header("Accept-Ranges: bytes");
		header("Content-Disposition: attachment; filename=api-doc_" . date("Y-m-d") . ".html");
		echo $docHtml;
		exit;
	}
}