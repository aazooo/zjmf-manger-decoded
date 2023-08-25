<?php

namespace app\common\logic;

class SimpleOrm
{
	private $str;
	private $arguments;
	public function __construct($str = "")
	{
		$this->str = $str;
	}
	public function __call($name, $arguments)
	{
		$this->str = $arguments[0];
		call_user_func($name, $this->str);
	}
	public function strlen()
	{
		$this->str = strlen($this->str);
		return $this;
	}
	public function trim()
	{
		$this->str = trim($this->str);
		return $this;
	}
	public function get()
	{
		return $this->str;
	}
}