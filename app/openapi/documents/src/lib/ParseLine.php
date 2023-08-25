<?php

namespace itxq\apidoc\lib;

/**
 * 按行解析注释参数
 * Class ParseLine
 * @package itxq\apidoc\lib
 */
class ParseLine
{
	/**
	 * 解析 title|url
	 * @param $line
	 * @return array
	 */
	public function parseLineTitle($line)
	{
		return ["type" => isset($line[0]) ? $line[0] : "", "content" => isset($line[1]) ? $line[1] : ""];
	}
	/**
	 * 解析 param
	 * @param $line
	 * @return array
	 */
	public function parseLineParam($line)
	{
		return ["type" => isset($line[0]) ? $line[0] : "", "param_type" => isset($line[1]) ? $line[1] : "", "param_name" => isset($line[2]) ? $line[2] : "", "param_title" => isset($line[3]) ? $line[3] : "", "param_default" => isset($line[4]) ? $line[4] : "", "param_require" => isset($line[5]) ? $line[5] : ""];
	}
	/**
	 * 解析 code
	 * @param $line
	 * @return array
	 */
	public function parseLineCode($line)
	{
		return ["type" => isset($line[0]) ? $line[0] : "", "code" => isset($line[1]) ? $line[1] : "", "content" => isset($line[2]) ? $line[2] : ""];
	}
	/**
	 * 解析 return
	 * @param $line
	 * @return array
	 */
	public function parseLineReturn($line)
	{
		return ["type" => isset($line[0]) ? $line[0] : "", "return_type" => isset($line[1]) ? $line[1] : "", "return_name" => isset($line[2]) ? $line[2] : "", "return_title" => isset($line[3]) ? $line[3] : ""];
	}
}