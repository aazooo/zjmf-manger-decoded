<?php

namespace itxq\apidoc;

/**
 * ApiDoc生成
 * Class ApiDoc
 * @package itxq\apidoc
 */
class ApiDoc
{
	/**
	 * @var array - 结构化的数组
	 */
	private $ApiTree = [];
	/**
	 * @var array - 要生成API的Class类名
	 */
	private $class = [];
	/**
	 * @var array - 忽略生成的类方法名
	 */
	private $filterMethod = ["__construct"];
	/**
	 * ApiDoc 构造函数.
	 * @param array $config - 配置信息
	 */
	public function __construct($config)
	{
		if (isset($config["class"])) {
			$this->class = array_merge($this->class, $config["class"]);
		}
		if (isset($config["filter_method"])) {
			$this->filterMethod = array_merge($this->filterMethod, $config["filter_method"]);
		}
	}
	/**
	 * 获取API文档数据
	 * @param int $type - 方法过滤，默认只获取 public类型 方法
	 * ReflectionMethod::IS_STATIC
	 * ReflectionMethod::IS_PUBLIC
	 * ReflectionMethod::IS_PROTECTED
	 * ReflectionMethod::IS_PRIVATE
	 * ReflectionMethod::IS_ABSTRACT
	 * ReflectionMethod::IS_FINAL
	 * @return array
	 */
	public function getApiDoc($type = \ReflectionMethod::IS_PUBLIC)
	{
		foreach ($this->class as $classItem) {
			$actionInfo = $this->_getActionComment($classItem, $type);
			if (count($actionInfo) >= 1) {
				$this->ApiTree[$classItem] = $this->_getClassComment($classItem);
				$this->ApiTree[$classItem]["action"] = $actionInfo;
			}
		}
		return $this->ApiTree;
	}
	/**
	 * 获取类的注释
	 * @param $class - 类名称(存在命名空间时要完整写入) eg: $class = 'itxq\\apidoc\\ApiDoc';
	 * @return array - 返回格式为数组（未获取到注释时返回空数组）
	 */
	private function _getClassComment($class)
	{
		try {
			$reflection = new \ReflectionClass($class);
			$classDocComment = $reflection->getDocComment();
		} catch (\Exception $exception) {
			return [];
		}
		$parse = new lib\ParseComment();
		return $parse->parseCommentToArray($classDocComment);
	}
	/**
	 * 获取指定类下方法的注释
	 * @param $class - 类名称(存在命名空间时要完整写入) eg: $class = 'itxq\\apidoc\\ApiDoc';
	 * @param int $type - 方法过滤，默认只获取 public类型 方法
	 * ReflectionMethod::IS_STATIC
	 * ReflectionMethod::IS_PUBLIC
	 * ReflectionMethod::IS_PROTECTED
	 * ReflectionMethod::IS_PRIVATE
	 * ReflectionMethod::IS_ABSTRACT
	 * ReflectionMethod::IS_FINAL
	 * @return array - 返回格式为数组（未获取到注释时返回空数组）
	 */
	private function _getActionComment($class, $type = \ReflectionMethod::IS_PUBLIC)
	{
		try {
			$reflection = new \ReflectionClass($class);
			$method = $reflection->getMethods($type);
		} catch (\Exception $exception) {
			return [];
		}
		$comments = [];
		foreach ($method as $action) {
			try {
				$parse = new lib\ParseComment();
				$actionComments = $parse->parseCommentToArray($action->getDocComment());
				if (count($actionComments) >= 1 && !in_array($action->name, $this->filterMethod)) {
					$comments[$action->name] = $actionComments;
				}
			} catch (\Exception $exception) {
				continue;
			}
		}
		return $comments;
	}
}